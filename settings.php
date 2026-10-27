<?php
require_once 'config/config.php';

if (!$is_logged_in) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Получаем текущие данные пользователя
$stmt = $pdo->prepare("SELECT full_name, email, theme, font_size, avatar FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();

// Разбиваем full_name на имя и фамилию (если есть)
$name_parts = explode(' ', $user_data['full_name'], 2);
$first_name = $name_parts[0] ?? '';
$last_name = $name_parts[1] ?? '';

// Обработка сохранения настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = $_POST['theme'] ?? 'light';
    $font_size = $_POST['font_size'] ?? 'medium';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Валидация
    if (empty($first_name)) {
        $error = 'Введите имя.';
    } elseif (empty($email)) {
        $error = 'Введите email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email.';
    } else {
        // Проверяем, не занят ли email другим пользователем
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error = 'Этот email уже используется другим пользователем.';
        } else {
            // Собираем полное имя
            $full_name = trim($first_name . ' ' . $last_name);
            if (empty($full_name)) {
                $error = 'Имя не может быть пустым.';
            } else {
                // Обновляем данные в БД
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, theme = ?, font_size = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $theme, $font_size, $user_id]);

                // Обновляем сессию
                $_SESSION['user_name'] = $full_name;
                $_SESSION['user_email'] = $email;
                $_SESSION['theme'] = $theme;
                $_SESSION['font_size'] = $font_size;

                // Обработка загрузки аватара
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
                    finfo_close($finfo);
                    
                    if (in_array($mime, $allowed)) {
                        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                        $filename = 'avatar_' . $user_id . '_' . uniqid() . '.' . $ext;
                        $upload_dir = 'uploads/avatars/';
                        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                        $destination = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
                            // Удаляем старый аватар
                            $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
                            $stmt->execute([$user_id]);
                            $old = $stmt->fetchColumn();
                            if ($old && file_exists($old)) unlink($old);
                            
                            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                            $stmt->execute([$destination, $user_id]);
                            $_SESSION['avatar'] = $destination;
                        }
                    } else {
                        $error = 'Неподдерживаемый формат изображения.';
                    }
                }

                if (empty($error)) {
                    $message = 'Настройки успешно сохранены.';
                    // Обновляем данные для отображения в форме
                    $user_data['full_name'] = $full_name;
                    $user_data['email'] = $email;
                    $name_parts = explode(' ', $full_name, 2);
                    $first_name = $name_parts[0] ?? '';
                    $last_name = $name_parts[1] ?? '';
                }
            }
        }
    }
}

// Текущие настройки (используем обновлённые данные из сессии или БД)
$current_theme = $_SESSION['theme'] ?? $user_data['theme'] ?? 'light';
$current_font_size = $_SESSION['font_size'] ?? $user_data['font_size'] ?? 'medium';
$avatar = $_SESSION['avatar'] ?? $user_data['avatar'] ?? null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки профиля - CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body.theme-dark {
            background-color: #0f172a;
            color: #e2e8f0;
        }
        body.theme-dark .header,
        body.theme-dark .footer,
        body.theme-dark .settings-container,
        body.theme-dark .modal-content {
            background-color: #1e293b;
            border-color: #334155;
            color: #cbd5e1;
        }
        body.theme-dark .profile-btn,
        body.theme-dark .about-btn {
            background-color: #334155;
            color: #e2e8f0;
        }
        body.font-small { font-size: 14px; }
        body.font-medium { font-size: 16px; }
        body.font-large { font-size: 18px; }
        
        .settings-container {
            max-width: 700px;
            margin: 40px auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .settings-container h1 {
            font-size: 28px;
            color: #1e293b;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }
        body.theme-dark .form-group label {
            color: #e2e8f0;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            background: white;
        }
        body.theme-dark .form-group input,
        body.theme-dark .form-group select {
            background: #334155;
            border-color: #475569;
            color: #e2e8f0;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .current-avatar {
            margin-bottom: 15px;
            text-align: center;
        }
        .current-avatar img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3b82f6;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: #64748b;
            color: white;
        }
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 20px 0;
            color: #3b82f6;
            text-decoration: none;
        }
        /* Шапка и подвал */
        .header { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 30px; }
        .logo { display: flex; align-items: center; gap: 12px; }
        .logo i { font-size: 32px; color: #3b82f6; }
        .logo h1 { font-size: 28px; font-weight: 700; color: #1e293b; }
        .profile-section { position: relative; }
        .profile-btn { display: flex; align-items: center; gap: 10px; background: white; border: 1px solid #e2e8f0; border-radius: 50px; padding: 10px 20px; cursor: pointer; }
        .profile-dropdown { position: absolute; top: 100%; right: 0; width: 280px; background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin-top: 10px; display: none; z-index: 100; }
        .profile-dropdown.show { display: block; }
        .user-info { display: flex; gap: 12px; padding: 16px; }
        .user-avatar { width: 48px; height: 48px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; }
        .dropdown-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #475569; text-decoration: none; }
        .dropdown-item:hover { background: #f8fafc; }
        .footer { padding: 40px 0; border-top: 1px solid #e2e8f0; text-align: center; margin-top: 60px; }
        .footer-logo { display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 24px; margin-bottom: 20px; }
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index:1000; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 20px; width: 90%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; padding: 20px 25px; border-bottom: 1px solid #e2e8f0; }
        .modal-close { background: none; border: none; font-size: 28px; cursor: pointer; }
        .modal-body { padding: 30px 25px; }
        .about-info { text-align: center; }
        .about-icon { width: 80px; height: 80px; background: linear-gradient(135deg,#3b82f6,#2563eb); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .about-icon i { font-size: 40px; color: white; }
        .highlight { background: #f8fafc; padding: 12px 20px; border-radius: 40px; display: inline-block; margin: 10px 0; }
        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size) ?>">
<div class="container">
    <!-- ШАПКА (как в index.php) -->
    <header class="header">
        <div class="logo">
            <i class="fas fa-code"></i>
            <h1>CodeLeans</h1>
        </div>
        <div class="header-actions">
            <div class="profile-section">
                <button class="profile-btn" id="profileBtn">
                    <?php if ($avatar && file_exists($avatar)): ?>
                        <img src="<?= htmlspecialchars($avatar) ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;margin-right:5px;">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($_SESSION['user_name'] ?? $user_name) ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="profile-dropdown" id="profileDropdown">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php if ($avatar && file_exists($avatar)): ?>
                                <img src="<?= htmlspecialchars($avatar) ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="user-details">
                            <h3><?= htmlspecialchars($_SESSION['user_name'] ?? $user_name) ?></h3>
                            <p><?= htmlspecialchars($_SESSION['user_email'] ?? $user_email) ?></p>
                            <p><small>Роль: <?php echo getUserRolesDisplay($user_roles); ?></small></p>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="profile.php" class="dropdown-item"><i class="fas fa-user-circle"></i> Мой профиль</a>
                    <a href="settings.php" class="dropdown-item"><i class="fas fa-sliders-h"></i> Настройки</a>
                    <?php
                    $menu_courses = $pdo->query("SELECT id, course_name FROM courses")->fetchAll();
                    foreach ($menu_courses as $c): ?>
                        <a href="courses.php?course_id=<?= $c['id'] ?>" class="dropdown-item"><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($c['course_name']) ?> курс</a>
                    <?php endforeach; ?>
                    <?php if ($is_teacher): ?>
                        <a href="create_course.php" class="dropdown-item"><i class="fas fa-plus-circle"></i> Создать курс</a>
                    <?php endif; ?>
                    <a href="logout.php" class="dropdown-item logout"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Модальное окно О нас -->
    <div class="modal" id="aboutModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-info-circle"></i> О проекте CodeLeans</h2>
                <button class="modal-close" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="about-info">
                    <div class="about-icon"><i class="fas fa-graduation-cap"></i></div>
                    <p>Данное пособие не является коммерческим проектом и не предназначено для дальнейшей продажи.</p>
                    <p>Создано в рамках курсового проекта под руководством</p>
                    <p class="highlight"><i class="fas fa-user-tie"></i> <strong>Согомонян Инны Эдуардовны</strong></p>
                    <div class="divider" style="width:50px;height:2px;background:#3b82f6;margin:20px auto;"></div>
                    <p>Разработчик: студент 35 группы</p>
                    <p class="highlight"><i class="fas fa-user"></i> <strong>Фадеев Виктор Юрьевич</strong></p>
                    <p class="date"><i class="fas fa-calendar-alt"></i> © 2025 CodeLeans</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ОСНОВНОЙ КОНТЕНТ НАСТРОЕК -->
    <div class="settings-container">
        <h1><i class="fas fa-sliders-h"></i> Настройки профиля</h1>
        
        <?php if ($message): ?>
            <div class="success-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data">
            <!-- Личные данные -->
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Имя</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($first_name) ?>" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Фамилия</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($last_name) ?>">
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user_data['email']) ?>" required>
            </div>

            <!-- Настройки внешнего вида -->
            <div class="form-group">
                <label><i class="fas fa-text-height"></i> Размер шрифта</label>
                <select name="font_size">
                    <option value="small" <?= $current_font_size === 'small' ? 'selected' : '' ?>>Маленький</option>
                    <option value="medium" <?= $current_font_size === 'medium' ? 'selected' : '' ?>>Средний</option>
                    <option value="large" <?= $current_font_size === 'large' ? 'selected' : '' ?>>Большой</option>
                </select>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-camera"></i> Аватар</label>
                <div class="current-avatar">
                    <?php if ($avatar && file_exists($avatar)): ?>
                        <img src="<?= htmlspecialchars($avatar) ?>" alt="Текущий аватар">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="font-size: 80px; color: #3b82f6;"></i>
                    <?php endif; ?>
                </div>
                <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                <small style="display: block; margin-top: 8px; color: #64748b;">
                    Рекомендуемый размер: 200x200px. JPEG, PNG, GIF, WEBP.
                </small>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Сохранить настройки
            </button>
            <a href="profile.php" class="btn btn-secondary" style="margin-left: 10px;">
                <i class="fas fa-arrow-left"></i> Вернуться в профиль
            </a>
        </form>
    </div>

    <!-- ПОДВАЛ -->
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
    // Выпадающее меню профиля
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
    // Модальное окно "О нас"
    const aboutBtn = document.getElementById('aboutBtn');
    const aboutModal = document.getElementById('aboutModal');
    const closeModal = document.getElementById('closeModal');
    if (aboutBtn && aboutModal && closeModal) {
        aboutBtn.addEventListener('click', () => {
            aboutModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
        const closeAboutModal = () => {
            aboutModal.classList.remove('show');
            document.body.style.overflow = '';
        };
        closeModal.addEventListener('click', closeAboutModal);
        aboutModal.addEventListener('click', (e) => {
            if (e.target === aboutModal) closeAboutModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && aboutModal.classList.contains('show')) closeAboutModal();
        });
    }
</script>
</body>
</html>