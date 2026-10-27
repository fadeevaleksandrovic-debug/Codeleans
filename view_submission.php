<?php
require_once 'config/config.php';

if (!$is_logged_in || !$is_teacher) {
    header('Location: index.php');
    exit;
}

$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
$user_id_student = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$assignment_id || !$user_id_student) {
    die('Недостаточно параметров.');
}

// Получаем информацию о задании
$stmt = $pdo->prepare("
    SELECT a.*, t.topic_name, t.course_id, c.course_name
    FROM assignments a
    JOIN topics t ON a.topic_id = t.id
    JOIN courses c ON t.course_id = c.id
    WHERE a.id = ?
");
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch();
if (!$assignment) {
    die('Задание не найдено.');
}

// Проверяем, что преподаватель имеет доступ к этому курсу
$check = $pdo->prepare("SELECT 1 FROM course_teachers WHERE course_id = ? AND teacher_id = ?");
$check->execute([$assignment['course_id'], $user_id]);
if (!$check->fetchColumn()) {
    die('Доступ запрещён.');
}

// Получаем данные студента
$stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt->execute([$user_id_student]);
$student = $stmt->fetch();
if (!$student) {
    die('Студент не найден.');
}

// Получаем последнюю попытку студента по этому заданию
$stmt = $pdo->prepare("
    SELECT * FROM assignment_progress
    WHERE assignment_id = ? AND user_id = ?
    ORDER BY last_submitted_at DESC LIMIT 1
");
$stmt->execute([$assignment_id, $user_id_student]);
$submission = $stmt->fetch();

if (!$submission) {
    die('Студент ещё не выполнял это задание.');
}

$current_theme = $_SESSION['theme'] ?? 'light';
$current_font_size = $_SESSION['font_size'] ?? 'medium';
$avatar = $_SESSION['avatar'] ?? null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Просмотр решения | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 30px; }
        .logo { display: flex; align-items: center; gap: 10px; }
        .logo i { font-size: 28px; color: #3b82f6; }
        .logo h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; margin: 20px 0; color: #3b82f6; text-decoration: none; font-weight: 500; }
        .card { background: white; border-radius: 16px; padding: 25px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card h2 { margin-top: 0; color: #1e293b; }
        .info-grid { display: grid; grid-template-columns: auto 1fr; gap: 10px 30px; margin: 15px 0; }
        .info-grid .label { font-weight: 600; color: #475569; }
        .code-block { background: #1e1e1e; color: #e2e8f0; padding: 20px; border-radius: 10px; overflow-x: auto; font-family: 'JetBrains Mono', monospace; font-size: 14px; white-space: pre-wrap; word-break: break-all; margin: 15px 0; }
        .status-badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-weight: 600; }
        .status-correct { background: #d1fae5; color: #065f46; }
        .status-wrong { background: #fee2e2; color: #dc2626; }
        .footer { padding: 40px 0; border-top: 1px solid #e2e8f0; text-align: center; margin-top: 60px; }
        body.theme-dark .card { background: #1e293b; border-color: #334155; }
        body.theme-dark .card h2 { color: #e2e8f0; }
        body.theme-dark .info-grid .label { color: #94a3b8; }
        body.theme-dark .info-grid span { color: #cbd5e1; }
        body.theme-dark .logo h1 { color: #e2e8f0; }
        body.theme-dark .status-correct { background: #0f3a2a; color: #a7f3d0; }
        body.theme-dark .status-wrong { background: #3a1a1a; color: #fca5a5; }
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size) ?>">
<div class="container">
    <header class="header">
        <div class="logo"><i class="fas fa-code"></i><h1>CodeLeans</h1></div>
        <div class="profile-section">
            <button class="profile-btn" id="profileBtn">
                <?php if ($avatar && file_exists($avatar)): ?>
                    <img src="<?= htmlspecialchars($avatar) ?>" style="width:32px;height:32px;border-radius:50%;">
                <?php else: ?>
                    <i class="fas fa-user-circle"></i>
                <?php endif; ?>
                <span><?= htmlspecialchars($user_name) ?></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="profile-dropdown" id="profileDropdown">...</div>
        </div>
    </header>

    <a href="teacher_assignments.php?course_id=<?= $assignment['course_id'] ?>" class="back-link">
        <i class="fas fa-arrow-left"></i> Вернуться к списку заданий
    </a>

    <h1><i class="fas fa-eye"></i> Просмотр решения</h1>

    <div class="card">
        <h2><?= htmlspecialchars($assignment['title']) ?></h2>
        <div class="info-grid">
            <span class="label">Курс:</span>
            <span><?= htmlspecialchars($assignment['course_name']) ?></span>
            <span class="label">Тема:</span>
            <span><?= htmlspecialchars($assignment['topic_name']) ?></span>
            <span class="label">Студент:</span>
            <span><?= htmlspecialchars($student['full_name']) ?> (<?= htmlspecialchars($student['email']) ?>)</span>
            <span class="label">Статус:</span>
            <span>
                <span class="status-badge <?= $submission['is_correct'] ? 'status-correct' : 'status-wrong' ?>">
                    <?= $submission['is_correct'] ? '✅ Выполнено верно' : '❌ Неправильно' ?>
                </span>
            </span>
            <span class="label">Попыток:</span>
            <span><?= $submission['attempts'] ?></span>
            <span class="label">Дата отправки:</span>
            <span><?= date('d.m.Y H:i:s', strtotime($submission['last_submitted_at'])) ?></span>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-code"></i> Код студента</h2>
        <div class="code-block"><?= htmlspecialchars($submission['user_code']) ?></div>
    </div>

    <?php if ($assignment['expected_output']): ?>
    <div class="card">
        <h2><i class="fas fa-bullseye"></i> Ожидаемый результат</h2>
        <div class="code-block" style="background: #0f172a; color: #10b981;"><?= htmlspecialchars($assignment['expected_output']) ?></div>
    </div>
    <?php endif; ?>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo"><i class="fas fa-code"></i><span>CodeLeans</span></div>
            <div class="footer-info">
                <p>Пособие выполнено в рамках курсового проекта</p>
                <p>Разработчик: Фадеев Виктор Юрьевич</p>
                <p>© 2026 Все права защищены</p>
            </div>
        </div>
    </footer>
</div>
<script>
    // Управление выпадающим меню профиля
    document.addEventListener('DOMContentLoaded', function() {
        const profileBtn = document.getElementById('profileBtn');
        const profileDropdown = document.getElementById('profileDropdown');
        if (profileBtn && profileDropdown) {
            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('show');
            });
            document.addEventListener('click', function() {
                profileDropdown.classList.remove('show');
            });
            profileDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });
</script>
</body>
</html>