<?php
require_once 'config/config.php';

if (!$is_logged_in || !$is_teacher) {
    header('Location: index.php');
    exit;
}

$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$error = '';
$success = '';

// Обработка отправки сообщения с файлом
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    $to_user_id = (int)$_POST['to_user_id'];
    $file_path = null;
    $file_type = null;

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/quicktime', 'application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['file']['tmp_name']);
        finfo_close($finfo);
        if (in_array($mime, $allowed)) {
            $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            if (strpos($mime, 'image/') === 0) $subdir = 'images';
            elseif (strpos($mime, 'video/') === 0) $subdir = 'videos';
            else $subdir = 'files';
            $upload_dir = 'uploads/private/' . $subdir . '/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $destination = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
                $file_path = $destination;
                $file_type = $mime;
            } else $error = 'Ошибка сохранения файла';
        } else $error = 'Неподдерживаемый тип файла';
    }

    if ($to_user_id && (!empty($message) || $file_path)) {
        $stmt = $pdo->prepare("INSERT INTO private_messages (from_user_id, to_user_id, message, file_path, file_type, is_read) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$user_id, $to_user_id, $message, $file_path, $file_type]);
        header("Location: teacher_messages.php?user_id=$to_user_id");
        exit;
    } else $error = 'Введите текст или выберите файл';
}

// Получаем учеников, с которыми есть переписка (роль student = 1)
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        u.id, u.full_name, u.avatar,
        (SELECT COUNT(*) FROM private_messages WHERE from_user_id = u.id AND to_user_id = ? AND is_read = 0) as unread_count
    FROM private_messages pm
    JOIN users u ON (pm.from_user_id = u.id OR pm.to_user_id = u.id)
    WHERE (pm.from_user_id = ? OR pm.to_user_id = ?)
      AND u.id != ?
      AND EXISTS (SELECT 1 FROM user_roles ur WHERE ur.user_id = u.id AND ur.role_id = 1)
    ORDER BY pm.created_at DESC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$students = $stmt->fetchAll();

if ($selected_user_id == 0 && count($students) > 0) {
    $selected_user_id = $students[0]['id'];
}

// Получаем сообщения с выбранным учеником
$messages = [];
if ($selected_user_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM private_messages 
        WHERE (from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?)
        ORDER BY created_at ASC
    ");
    $stmt->execute([$user_id, $selected_user_id, $selected_user_id, $user_id]);
    $messages = $stmt->fetchAll();

    // Помечаем входящие от ученика как прочитанные
    $stmt = $pdo->prepare("UPDATE private_messages SET is_read = 1 WHERE to_user_id = ? AND from_user_id = ? AND is_read = 0");
    $stmt->execute([$user_id, $selected_user_id]);
}

$student_name = '';
if ($selected_user_id) {
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$selected_user_id]);
    $student_name = $stmt->fetchColumn();
}

$current_theme = $_SESSION['theme'] ?? 'light';
$current_font_size = $_SESSION['font_size'] ?? 'medium';
$avatar = $_SESSION['avatar'] ?? null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сообщения от учеников | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Стили мессенджера (увеличенная версия) */
        .messenger-container {
            display: flex;
            max-width: 1400px;
            margin: 30px auto;
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            height: calc(100vh - 220px);
            min-height: 600px;
        }
        .sidebar {
            width: 320px;
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 700;
            font-size: 18px;
            background: white;
        }
        .students-list {
            flex: 1;
            overflow-y: auto;
        }
        .student-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            text-decoration: none;
            color: inherit;
            border-bottom: 1px solid #e2e8f0;
            transition: 0.2s;
        }
        .student-item:hover { background: #f1f5f9; }
        .student-item.active {
            background: #e0f2fe;
            border-left: 4px solid #3b82f6;
        }
        .student-avatar {
            width: 48px;
            height: 48px;
            background: #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        .student-info {
            flex: 1;
        }
        .student-name {
            font-weight: 600;
            display: flex;
            justify-content: space-between;
        }
        .unread-badge {
            background: #ef4444;
            color: white;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 12px;
        }
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #f9fafb;
        }
        .chat-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 700;
            background: white;
        }
        .messages-list {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .message {
            max-width: 70%;
            padding: 12px 18px;
            border-radius: 20px;
            word-wrap: break-word;
        }
        .message.outgoing {
            background: #3b82f6;
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 6px;
        }
        .message.incoming {
            background: white;
            border: 1px solid #e2e8f0;
            align-self: flex-start;
            border-bottom-left-radius: 6px;
        }
        .message-body p { margin: 0 0 8px 0; }
        .message-body img, .message-body video {
            max-width: 100%;
            border-radius: 12px;
            margin-top: 8px;
        }
        .file-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(0,0,0,0.05);
            padding: 6px 12px;
            border-radius: 20px;
            text-decoration: none;
            color: inherit;
        }
        .message-time {
            font-size: 10px;
            margin-top: 6px;
            text-align: right;
            opacity: 0.6;
        }
        .chat-form {
            padding: 20px 25px;
            border-top: 1px solid #e2e8f0;
            background: white;
        }
        .form-row {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .message-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 28px;
            font-family: 'Inter', sans-serif;
            resize: none;
            height: 48px;
        }
        .file-label {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 28px;
            padding: 10px 18px;
            cursor: pointer;
        }
        .send-btn {
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 28px;
            padding: 0 24px;
            height: 48px;
            cursor: pointer;
            font-weight: 600;
        }
        .selected-file { font-size: 12px; color: #64748b; margin-top: 8px; }
        .empty-state {
            text-align: center;
            padding: 60px;
            color: #64748b;
        }
        /* Тёмная тема */
        body.theme-dark .messenger-container,
        body.theme-dark .sidebar,
        body.theme-dark .chat-area,
        body.theme-dark .chat-header,
        body.theme-dark .chat-form,
        body.theme-dark .sidebar-header {
            background: #1e293b;
            border-color: #334155;
        }
        body.theme-dark .student-item { border-color: #334155; }
        body.theme-dark .student-item:hover { background: #334155; }
        body.theme-dark .student-item.active { background: #0f172a; }
        body.theme-dark .student-name { color: #e2e8f0; }
        body.theme-dark .message.incoming {
            background: #334155;
            border-color: #475569;
            color: #e2e8f0;
        }
        body.theme-dark .messages-list { background: #0f172a; }
        body.theme-dark .message-input,
        body.theme-dark .file-label {
            background: #0f172a;
            border-color: #475569;
            color: #e2e8f0;
        }
        @media (max-width: 768px) {
            .messenger-container { flex-direction: column; height: auto; }
            .sidebar { width: 100%; max-height: 300px; }
            .message { max-width: 85%; }
        }
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size) ?>">
<div class="container">
    <!-- Шапка (краткая, но можно расширить) -->
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

    <!-- Ссылка назад -->
    <a href="index.php" class="back-link" style="display: inline-flex; align-items: center; gap: 8px; margin: 20px 0; color: #3b82f6; text-decoration: none;">
        <i class="fas fa-arrow-left"></i> На главную
    </a>

    <div class="messenger-container">
        <!-- Боковая панель -->
        <div class="sidebar">
            <div class="sidebar-header"><i class="fas fa-users"></i> Ученики</div>
            <div class="students-list">
                <?php if (empty($students)): ?>
                    <div class="empty-state">Нет диалогов с учениками</div>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <a href="teacher_messages.php?user_id=<?= $student['id'] ?>" class="student-item <?= ($selected_user_id == $student['id']) ? 'active' : '' ?>">
                            <div class="student-avatar"><?= mb_substr($student['full_name'], 0, 1) ?></div>
                            <div class="student-info">
                                <div class="student-name">
                                    <?= htmlspecialchars($student['full_name']) ?>
                                    <?php if ($student['unread_count'] > 0): ?>
                                        <span class="unread-badge"><?= $student['unread_count'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Чат -->
        <div class="chat-area">
            <?php if ($selected_user_id && $student_name): ?>
                <div class="chat-header"><i class="fas fa-comment-dots"></i> Чат с <?= htmlspecialchars($student_name) ?></div>
                <div class="messages-list" id="messagesList">
                    <?php foreach ($messages as $msg):
                        $is_teacher = ($msg['from_user_id'] == $user_id);
                    ?>
                        <div class="message <?= $is_teacher ? 'outgoing' : 'incoming' ?>">
                            <div class="message-body">
                                <?php if ($msg['message']): ?>
                                    <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                                <?php endif; ?>
                                <?php if ($msg['file_path'] && file_exists($msg['file_path'])): ?>
                                    <?php if (strpos($msg['file_type'], 'image/') === 0): ?>
                                        <img src="<?= htmlspecialchars($msg['file_path']) ?>">
                                    <?php elseif (strpos($msg['file_type'], 'video/') === 0): ?>
                                        <video controls><source src="<?= htmlspecialchars($msg['file_path']) ?>" type="<?= $msg['file_type'] ?>"></video>
                                    <?php else: ?>
                                        <a href="<?= htmlspecialchars($msg['file_path']) ?>" target="_blank" class="file-link"><i class="fas fa-paperclip"></i> Скачать файл</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="message-time"><?= date('H:i d.m.Y', strtotime($msg['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form method="post" enctype="multipart/form-data" class="chat-form" id="chatForm">
                    <input type="hidden" name="to_user_id" value="<?= $selected_user_id ?>">
                    <div class="form-row">
                        <textarea name="message" class="message-input" placeholder="Введите сообщение..." rows="1"></textarea>
                        <label class="file-label"><i class="fas fa-paperclip"></i><input type="file" name="file" style="display: none;" id="fileInput"></label>
                        <button type="submit" class="send-btn"><i class="fas fa-paper-plane"></i> Отправить</button>
                    </div>
                    <div id="selectedFileName" class="selected-file"></div>
                </form>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-comment" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>Выберите ученика из списка</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Подвал -->
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
    // Скролл вниз
    const msgList = document.getElementById('messagesList');
    if (msgList) msgList.scrollTop = msgList.scrollHeight;

    // Отображение выбранного файла
    const fileInput = document.getElementById('fileInput');
    const fileNameSpan = document.getElementById('selectedFileName');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            fileNameSpan.innerHTML = this.files[0] ? `<i class="fas fa-file"></i> ${this.files[0].name}` : '';
        });
    }

    // Автообновление чата
    function refreshChat() {
        const userId = <?= json_encode($selected_user_id) ?>;
        if (!userId) return;
        fetch('ajax_get_messages.php?with_user=' + userId)
            .then(res => res.text())
            .then(html => { if (msgList) { msgList.innerHTML = html; msgList.scrollTop = msgList.scrollHeight; } })
            .catch(console.error);
    }
    setInterval(refreshChat, 5000);
</script>
</body>
</html>