<?php
require_once 'config/config.php';

if (!$is_logged_in) {
    header('Location: login.php');
    exit;
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

if (!$course_id) {
    die('Курс не указан');
}

// Получаем информацию о курсе
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();
if (!$course) {
    die('Курс не найден');
}

// Получаем список преподавателей курса
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name 
    FROM course_teachers ct
    JOIN users u ON ct.teacher_id = u.id
    WHERE ct.course_id = ?
");
$stmt->execute([$course_id]);
$teachers = $stmt->fetchAll();

if (empty($teachers)) {
    die('К этому курсу ещё не привязан преподаватель.');
}

// Если teacher_id не передан, выбираем первого преподавателя
if (!$teacher_id) {
    $teacher_id = $teachers[0]['id'];
} else {
    $valid = false;
    foreach ($teachers as $t) {
        if ($t['id'] == $teacher_id) {
            $valid = true;
            break;
        }
    }
    if (!$valid) {
        die('Некорректный преподаватель');
    }
}

// Имя преподавателя
$teacher_name = '';
foreach ($teachers as $t) {
    if ($t['id'] == $teacher_id) {
        $teacher_name = $t['full_name'];
        break;
    }
}

// Обработка отправки сообщения
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    $file = $_FILES['file'] ?? null;

    $file_path = null;
    $file_type = null;

    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        if ($file['size'] > 10 * 1024 * 1024) {
            $error = 'Файл слишком большой (макс. 10MB)';
        } else {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (in_array($mime, $allowed_types)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $ext;
                $subdir = strpos($mime, 'image/') === 0 ? 'images' : 'videos';
                $upload_dir = 'uploads/private/' . $subdir . '/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $destination = $upload_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $file_path = $destination;
                    $file_type = $mime;
                } else {
                    $error = 'Ошибка при сохранении файла';
                }
            } else {
                $error = 'Неподдерживаемый тип файла. Разрешены только изображения и видео.';
            }
        }
    }

    if (empty($error) && (!empty($message) || $file_path)) {
        $stmt = $pdo->prepare("INSERT INTO private_messages (from_user_id, to_user_id, message, file_path, file_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $teacher_id, $message, $file_path, $file_type]);
    }

    header("Location: private_chat.php?course_id=$course_id&teacher_id=$teacher_id" . ($error ? "&error=" . urlencode($error) : ""));
    exit;
}

// Получаем все сообщения между пользователем и преподавателем
$stmt = $pdo->prepare("
    SELECT * FROM private_messages 
    WHERE (from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?)
    ORDER BY created_at ASC
");
$stmt->execute([$user_id, $teacher_id, $teacher_id, $user_id]);
$messages = $stmt->fetchAll();

// Отмечаем входящие сообщения как прочитанные
$stmt = $pdo->prepare("UPDATE private_messages SET is_read = 1 WHERE to_user_id = ? AND from_user_id = ? AND is_read = 0");
$stmt->execute([$user_id, $teacher_id]);

$current_theme = $_SESSION['theme'] ?? 'light';
$current_font_size = $_SESSION['font_size'] ?? 'medium';
$avatar = $_SESSION['avatar'] ?? null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный чат с преподавателем | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .chat-container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .messages-list { background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 20px; height: 500px; overflow-y: auto; display: flex; flex-direction: column; }
        .message { background: white; border-radius: 12px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); max-width: 80%; align-self: flex-start; }
        .message.own { background: #e0f2fe; align-self: flex-end; border: 1px solid #3b82f6; }
        .message-header { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 14px; color: #64748b; }
        .message-body p { margin: 0 0 10px 0; color: #1e293b; }
        .message-body img, .message-body video { max-width: 100%; border-radius: 8px; }
        .chat-form { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .chat-form textarea { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: 'Inter', sans-serif; margin-bottom: 10px; resize: vertical; }
        .chat-form input[type="file"] { margin-bottom: 10px; }
        .chat-form button { background: #3b82f6; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; margin: 20px 0; color: #3b82f6; text-decoration: none; font-weight: 500; }
        .teacher-selector { background: #f1f5f9; border-radius: 12px; padding: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        body.theme-dark .messages-list { background: #1e293b; }
        body.theme-dark .message { background: #334155; }
        body.theme-dark .message.own { background: #1e3a5f; }
        body.theme-dark .chat-form { background: #1e293b; }
        body.theme-dark .chat-form textarea { background: #0f172a; border-color: #475569; color: #e2e8f0; }
        body.theme-dark .teacher-selector { background: #0f172a; color: #e2e8f0; }
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size) ?>">
<div class="container">
    <a href="courses.php?course_id=<?= $course_id ?>" class="back-link"><i class="fas fa-arrow-left"></i> Вернуться к темам</a>

    <div class="chat-container">
        <h2>Личный чат с преподавателем</h2>

        <?php if (count($teachers) > 1): ?>
            <div class="teacher-selector">
                <label>Выберите преподавателя:</label>
                <form method="get" action="">
                    <input type="hidden" name="course_id" value="<?= $course_id ?>">
                    <select name="teacher_id">
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $t['id'] == $teacher_id ? 'selected' : '' ?>><?= htmlspecialchars($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Перейти</button>
                </form>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 15px;"><strong>Преподаватель:</strong> <?= htmlspecialchars($teacher_name) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <div class="messages-list" id="messagesList">
            <?php foreach ($messages as $msg): 
                $is_own = ($msg['from_user_id'] == $user_id);
            ?>
                <div class="message <?= $is_own ? 'own' : '' ?>">
                    <div class="message-header">
                        <strong><?= $is_own ? 'Вы' : htmlspecialchars($teacher_name) ?></strong>
                        <small><?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?></small>
                    </div>
                    <div class="message-body">
                        <?php if ($msg['message']): ?>
                            <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                        <?php endif; ?>
                        <?php if ($msg['file_path'] && file_exists($msg['file_path'])): ?>
                            <?php if (strpos($msg['file_type'], 'image/') === 0): ?>
                                <img src="<?= htmlspecialchars($msg['file_path']) ?>" style="max-width: 300px;">
                            <?php elseif (strpos($msg['file_type'], 'video/') === 0): ?>
                                <video controls style="max-width: 400px;">
                                    <source src="<?= htmlspecialchars($msg['file_path']) ?>" type="<?= $msg['file_type'] ?>">
                                </video>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="post" enctype="multipart/form-data" class="chat-form" id="chatForm">
            <textarea name="message" placeholder="Введите сообщение..." id="messageInput"></textarea>
            <input type="file" name="file" accept="image/*,video/*">
            <button type="submit">Отправить</button>
        </form>
    </div>
</div>

<script>
    function loadMessages() {
        fetch('private_chat_ajax.php?course_id=<?= $course_id ?>&teacher_id=<?= $teacher_id ?>')
            .then(response => response.text())
            .then(html => {
                document.getElementById('messagesList').innerHTML = html;
                document.getElementById('messagesList').scrollTop = document.getElementById('messagesList').scrollHeight;
            });
    }
    setInterval(loadMessages, 3000);
    window.onload = () => {
        const msgs = document.getElementById('messagesList');
        msgs.scrollTop = msgs.scrollHeight;
    };
    document.getElementById('chatForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch(window.location.href, { method: 'POST', body: formData }).then(() => {
            document.getElementById('messageInput').value = '';
            document.querySelector('input[type="file"]').value = '';
            loadMessages();
        });
    });
</script>
</body>
</html>