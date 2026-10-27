<?php
require_once 'config/config.php';

if (!$is_logged_in) {
    header('Location: login.php');
    exit;
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if (!$course_id) {
    die('Курс не указан');
}

// Если преподаватель, проверяем закрепление
if ($is_teacher) {
    $stmt = $pdo->prepare("SELECT 1 FROM course_teachers WHERE course_id = ? AND teacher_id = ?");
    $stmt->execute([$course_id, $user_id]);
    if (!$stmt->fetchColumn()) {
        die('Доступ запрещён: вы не закреплены за этим курсом.');
    }
}

// Получаем информацию о курсе
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();
if (!$course) {
    die('Курс не найден');
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
                $upload_dir = 'uploads/' . $subdir . '/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
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
        $stmt = $pdo->prepare("INSERT INTO chat_messages (course_id, user_id, message, file_path, file_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$course_id, $user_id, $message, $file_path, $file_type]);
    }

    header("Location: chat.php?course_id=$course_id" . ($error ? "&error=" . urlencode($error) : ""));
    exit;
}

// Упрощённый запрос без подзапроса на роли
$stmt = $pdo->prepare("
    SELECT m.*, u.full_name
    FROM chat_messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.course_id = ?
    ORDER BY m.created_at ASC
");
$stmt->execute([$course_id]);
$messages = $stmt->fetchAll();

// Для отладки: раскомментируйте, чтобы увидеть количество сообщений
// var_dump(count($messages)); exit;

$current_theme = $_SESSION['theme'] ?? 'light';
$current_font_size = $_SESSION['font_size'] ?? 'medium';
$avatar = $_SESSION['avatar'] ?? null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чат курса <?php echo htmlspecialchars($course['course_name']); ?> | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        .chat-container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .messages-list { background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 20px; height: 500px; overflow-y: auto; display: flex; flex-direction: column; }
        .message { background: white; border-radius: 12px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); max-width: 80%; align-self: flex-start; }
        .message.teacher { background: #e0f2fe; align-self: flex-end; border: 1px solid #3b82f6; }
        .message.admin { background: #fef9c3; border: 1px solid #eab308; }
        .message-header { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 14px; color: #64748b; }
        .message-body p { margin: 0 0 10px 0; color: #1e293b; }
        .message-body img, .message-body video { max-width: 100%; border-radius: 8px; }
        .chat-form { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .chat-form textarea { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: 'Inter', sans-serif; margin-bottom: 10px; resize: vertical; }
        .chat-form input[type="file"] { margin-bottom: 10px; }
        .chat-form button { background: #3b82f6; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.3s; }
        .chat-form button:hover { background: #2563eb; }
        .error-message { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 8px; margin-bottom: 15px; }
        body.theme-dark .messages-list { background: #1e293b; }
        body.theme-dark .message { background: #334155; color: #e2e8f0; }
        body.theme-dark .message.teacher { background: #1e3a5f; }
        body.theme-dark .message.admin { background: #422800; }
        body.theme-dark .message-body p { color: #e2e8f0; }
        body.theme-dark .chat-form { background: #1e293b; }
        body.theme-dark .chat-form textarea { background: #0f172a; border-color: #475569; color: #e2e8f0; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; margin: 20px 0; color: #3b82f6; text-decoration: none; font-weight: 500; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 30px; }
        .logo { display: flex; align-items: center; gap: 10px; }
        .logo i { font-size: 28px; color: #3b82f6; }
        .logo h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
        .profile-section { position: relative; }
        .profile-btn { display: flex; align-items: center; gap: 8px; padding: 8px 16px; background: white; border: 1px solid #e2e8f0; border-radius: 40px; cursor: pointer; }
        .profile-dropdown { position: absolute; top: 100%; right: 0; width: 280px; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.3s; z-index: 100; }
        .profile-dropdown.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .footer { padding: 40px 0; border-top: 1px solid #e2e8f0; text-align: center; margin-top: 60px; }
        .footer-logo { display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 20px; margin-bottom: 15px; }
        .footer-info p { margin: 5px 0; color: #64748b; font-size: 14px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 20px; width: 90%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; padding: 20px 25px; border-bottom: 1px solid #e2e8f0; }
        .modal-close { background: none; border: none; font-size: 28px; cursor: pointer; }
        .modal-body { padding: 30px 25px; }
        .about-info { text-align: center; }
        .about-icon { width: 80px; height: 80px; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .about-icon i { font-size: 40px; color: white; }
        .highlight { background: #f8fafc; padding: 12px 20px; border-radius: 40px; display: inline-block; margin: 10px 0; }
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size) ?>">
<div class="container">
    <!-- Шапка (упрощённо, но можно скопировать из index.php) -->
    <header class="header">
        <div class="logo"><i class="fas fa-code"></i><h1>CodeLeans</h1></div>
        <div class="profile-section">
            <button class="profile-btn" id="profileBtn">
                <?php if ($avatar && file_exists($avatar)): ?>
                    <img src="<?= htmlspecialchars($avatar) ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;margin-right:5px;">
                <?php else: ?>
                    <i class="fas fa-user-circle"></i>
                <?php endif; ?>
                <span><?php echo htmlspecialchars($user_name); ?></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="profile-dropdown" id="profileDropdown">
                <div class="user-info"><!-- содержимое по желанию --></div>
                <div class="dropdown-divider"></div>
                <a href="profile.php" class="dropdown-item"><i class="fas fa-user-cog"></i> Мой профиль</a>
                <a href="settings.php" class="dropdown-item"><i class="fas fa-sliders-h"></i> Настройки</a>
                <a href="logout.php" class="dropdown-item logout"><i class="fas fa-sign-out-alt"></i> Выйти</a>
            </div>
        </div>
    </header>

    <!-- Модальное окно О нас (упрощённо) -->
    <div class="modal" id="aboutModal">...</div>

    <a href="courses.php?course_id=<?= $course_id ?>" class="back-link"><i class="fas fa-arrow-left"></i> Вернуться к курсу</a>

    <div class="chat-container">
        <h2>Чат курса: <?php echo htmlspecialchars($course['course_name']); ?></h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <div class="messages-list" id="messagesList">
            <?php foreach ($messages as $msg): ?>
                <div class="message">
                    <div class="message-header">
                        <strong><?php echo htmlspecialchars($msg['full_name']); ?></strong>
                        <small><?php echo date('d.m.Y H:i', strtotime($msg['created_at'])); ?></small>
                    </div>
                    <div class="message-body">
                        <?php if ($msg['message']): ?>
                            <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                        <?php endif; ?>
                        <?php if ($msg['file_path'] && file_exists($msg['file_path'])): ?>
                            <?php if (strpos($msg['file_type'], 'image/') === 0): ?>
                                <img src="<?php echo htmlspecialchars($msg['file_path']); ?>" style="max-width: 300px;">
                            <?php elseif (strpos($msg['file_type'], 'video/') === 0): ?>
                                <video controls style="max-width: 400px;">
                                    <source src="<?php echo htmlspecialchars($msg['file_path']); ?>" type="<?php echo $msg['file_type']; ?>">
                                </video>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="post" enctype="multipart/form-data" class="chat-form" id="chatForm">
            <textarea name="message" placeholder="Введите сообщение..."></textarea>
            <input type="file" name="file" accept="image/*,video/*">
            <button type="submit">Отправить</button>
        </form>
    </div>

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
    // Профиль выпадающее меню
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

    // Функция обновления сообщений (AJAX)
    function loadMessages() {
        fetch('chat_ajax.php?course_id=<?= $course_id ?>')
            .then(response => response.text())
            .then(html => {
                document.getElementById('messagesList').innerHTML = html;
                // Прокрутка вниз
                const list = document.getElementById('messagesList');
                list.scrollTop = list.scrollHeight;
            })
            .catch(err => console.error('Ошибка загрузки сообщений:', err));
    }

    // Автообновление каждые 5 секунд
    setInterval(loadMessages, 5000);
    // Прокрутка вниз при загрузке
    window.onload = function() {
        const list = document.getElementById('messagesList');
        if (list) list.scrollTop = list.scrollHeight;
    };
</script>
</body>
</html>