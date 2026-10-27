<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once 'config/config.php';

// Очистка строки от нечитаемых символов
function cleanName($str) {
    if (empty($str)) return 'Участник';
    // Пытаемся преобразовать в UTF-8
    $str = mb_convert_encoding($str, 'UTF-8', 'auto');
    // Если после преобразования строка пуста или содержит только непечатаемые символы
    if (preg_match('/^[\p{C}\p{Z}]+$/u', $str)) {
        return 'Участник';
    }
    // Оставляем только буквы, пробелы, дефисы и точки
    $str = preg_replace('/[^\p{L}\s\-\.]/u', '', $str);
    // Если после фильтрации осталось меньше 2 символов – заменяем
    if (mb_strlen($str) < 2) {
        return 'Участник';
    }
    return trim($str);
}

if (!$is_logged_in) {
    header('Location: login.php');
    exit;
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if (!$course_id) die('Курс не указан');

$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();
if (!$course) die('Курс не найден');

$course_name = cleanName($course['course_name']);

$stmt = $pdo->prepare("SELECT progress_percent FROM user_progress WHERE user_id = ? AND course_id = ?");
$stmt->execute([$user_id, $course_id]);
$progress = $stmt->fetchColumn();

if ($progress < 100) {
    header("Location: courses.php?course_id=$course_id&error=not_completed");
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.full_name 
    FROM course_teachers ct
    JOIN users u ON ct.teacher_id = u.id
    WHERE ct.course_id = ?
    LIMIT 1
");
$stmt->execute([$course_id]);
$teacher = $stmt->fetch();
$teacher_name = $teacher ? cleanName($teacher['full_name']) : 'Не назначен';

$user_name = cleanName($user_name);

$cert_dir = 'certificates/';
if (!is_dir($cert_dir)) mkdir($cert_dir, 0777, true);

$stmt = $pdo->prepare("SELECT * FROM certificates WHERE user_id = ? AND course_id = ?");
$stmt->execute([$user_id, $course_id]);
$existing = $stmt->fetch();

if ($existing) {
    $certificate_code = $existing['certificate_code'];
    $issue_date = date('d.m.Y', strtotime($existing['issued_at']));
    $filepath = $existing['file_path'];
} else {
    $certificate_code = strtoupper(substr(md5($user_id . $course_id . time()), 0, 16));
    $issue_date = date('d.m.Y');
    $filename = 'certificate_' . $user_id . '_' . $course_id . '.html';
    $filepath = $cert_dir . $filename;
}

$certificate_html = generateCertificateHTML($user_name, $course_name, $certificate_code, $issue_date, $teacher_name);

if (!$existing) {
    file_put_contents($filepath, $certificate_html, LOCK_EX);
    $stmt = $pdo->prepare("INSERT INTO certificates (user_id, course_id, certificate_code, file_path) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $course_id, $certificate_code, $filepath]);
} else {
    if (!file_exists($filepath)) {
        file_put_contents($filepath, $certificate_html, LOCK_EX);
    }
}

function generateCertificateHTML($user_name, $course_name, $code, $date, $teacher_name) {
    return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Сертификат - CodeLeans</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Times New Roman', Georgia, serif; background: #f0f0f0; display:flex; justify-content:center; align-items:center; min-height:100vh; padding:20px; }
        .certificate { width:900px; min-height:650px; background:linear-gradient(135deg,#fff8e7 0%,#fff 100%); border:15px double #d4af37; border-radius:20px; position:relative; padding:40px; text-align:center; box-shadow:0 20px 40px rgba(0,0,0,0.2); }
        .certificate:before { content:""; position:absolute; top:10px; left:10px; right:10px; bottom:10px; border:1px solid #d4af37; border-radius:10px; pointer-events:none; }
        .logo { display:flex; flex-direction:column; align-items:center; margin-bottom:20px; }
        .logo i { font-size:48px; color:#3b82f6; }
        .logo-text { font-size:24px; font-weight:bold; color:#1e293b; letter-spacing:2px; margin-top:5px; }
        h1 { font-size:48px; color:#d4af37; letter-spacing:8px; margin-bottom:20px; text-transform:uppercase; display:inline-block; padding:0 30px; }
        .subtitle { font-size:18px; color:#666; margin-bottom:30px; font-style:italic; }
        .recipient { font-size:36px; color:#1e293b; margin:30px 0; padding:10px; border-bottom:2px solid #d4af37; display:inline-block; }
        .course-name { font-size:28px; color:#3b82f6; margin:20px 0; font-weight:bold; display:block; }
        .description { font-size:18px; color:#555; margin:30px 0; line-height:1.6; max-width:80%; margin-left:auto; margin-right:auto; }
        .date { font-size:14px; color:#666; margin:20px 0; }
        .code { font-size:12px; color:#999; margin-top:20px; font-family:monospace; }
        .signature { margin-top:40px; display:flex; justify-content:space-between; padding:0 50px; }
        .sign-line { text-align:center; }
        .sign-line .line { width:200px; height:1px; background:#333; margin-bottom:5px; }
        .sign-line .name { font-size:14px; color:#1e293b; font-weight:bold; }
        .sign-line .title { font-size:12px; color:#666; margin-bottom:8px; }
        @media print { body { background:white; padding:0; } .certificate { box-shadow:none; width:100%; height:auto; } }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="certificate">
        <div class="logo">
            <i class="fas fa-code"></i>
            <div class="logo-text">CodeLeans</div>
        </div>
        <h1>СЕРТИФИКАТ</h1>
        <div class="subtitle">Настоящим подтверждается, что</div>
        <div class="recipient">$user_name</div>
        <div class="description">
            успешно завершил(а) полный курс<br>
            <span class="course-name">$course_name</span>
        </div>
        <div class="date">Дата выдачи: $date</div>
        <div class="code">Код сертификата: $code</div>
        <div class="signature">
            <div class="sign-line">
                <div class="title">Руководитель курса</div>
                <div class="line"></div>
                <div class="name">$teacher_name</div>
            </div>
            <div class="sign-line">
                <div class="title">Директор CodeLeans</div>
                <div class="line"></div>
                <div class="name">Фадеев Виктор Юрьевич</div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сертификат - <?= htmlspecialchars($course_name) ?></title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { background:#f0f0f0; font-family:'Inter',sans-serif; }
        .cert-container { max-width:1000px; margin:40px auto; padding:20px; }
        .cert-actions { text-align:center; margin-bottom:30px; }
        .btn { display:inline-block; padding:12px 24px; margin:0 10px; border-radius:8px; text-decoration:none; font-weight:600; transition:all 0.3s; cursor:pointer; border:none; }
        .btn-secondary { background:#64748b; color:white; }
        .btn-secondary:hover { background:#475569; }
        .btn-success { background:#10b981; color:white; }
        .btn-success:hover { background:#059669; }
        .certificate-frame { background:white; border-radius:20px; padding:20px; box-shadow:0 10px 30px rgba(0,0,0,0.2); min-height:700px; }
        .certificate-content { width:100%; min-height:650px; background:white; border-radius:10px; }
        .congrats { text-align:center; margin-bottom:30px; }
        .congrats h1 { color:#1e293b; font-size:32px; margin-bottom:10px; }
        .congrats p { color:#64748b; font-size:18px; }
        .congrats i { font-size:48px; color:#f59e0b; margin-bottom:20px; }
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size) ?>">
    <div class="cert-container">
        <div class="congrats">
            <i class="fas fa-trophy"></i>
            <h1>Поздравляем, <?= htmlspecialchars($user_name) ?>!</h1>
            <p>Вы успешно завершили курс «<?= htmlspecialchars($course_name) ?>»</p>
        </div>
        <div class="cert-actions">
            <a href="courses.php?course_id=<?= $course_id ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Вернуться к курсу
            </a>
            <button class="btn btn-success" id="printBtn">
                <i class="fas fa-print"></i> Распечатать
            </button>
            <button class="btn btn-success" id="downloadBtn">
                <i class="fas fa-download"></i> Скачать PDF
            </button>
        </div>
        <div class="certificate-frame">
            <div class="certificate-content">
                <?= $certificate_html ?>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('printBtn')?.addEventListener('click', function() {
            var printContent = document.querySelector('.certificate-content').innerHTML;
            var printWindow = window.open('', '_blank');
            printWindow.document.write('<!DOCTYPE html><html><head><title>Сертификат - CodeLeans</title><meta charset="utf-8"><style>* { margin:0; padding:0; box-sizing:border-box; } body { font-family: "Times New Roman", serif; }</style></head><body>' + printContent + '</body></html>');
            printWindow.document.close();
            printWindow.print();
        });
        document.getElementById('downloadBtn')?.addEventListener('click', function() {
            var element = document.querySelector('.certificate-content');
            html2pdf().set({
                margin: [0.5, 0.5, 0.5, 0.5],
                filename: 'certificate_<?= preg_replace('/[^a-zA-Z0-9]/', '_', $course_name) ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, letterRendering: true },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'landscape' }
            }).from(element).save();
        });
    </script>
</body>
</html>