<?php
require_once 'config/config.php';

// Запрещаем кеширование
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$topic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$topic_id) {
    die('Тема не указана');
}

// Получаем тему вместе с названием курса
$stmt = $pdo->prepare("SELECT t.*, c.course_name, c.id as course_id FROM topics t JOIN courses c ON t.course_id = c.id WHERE t.id = ?");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch();
if (!$topic) {
    die('Тема не найдена');
}

// Проверяем, есть ли задание для этой темы
$stmt = $pdo->prepare("SELECT id FROM assignments WHERE topic_id = ?");
$stmt->execute([$topic_id]);
$has_assignment = $stmt->fetch();

// Проверяем, пройдена ли тема пользователем
$is_completed = false;
$assignment_completed = false;
if ($is_logged_in) {
    $stmt = $pdo->prepare("SELECT is_completed FROM topic_progress WHERE user_id = ? AND topic_id = ?");
    $stmt->execute([$user_id, $topic_id]);
    $is_completed = (bool)$stmt->fetchColumn();
    
    // Проверяем, выполнено ли задание
    if ($has_assignment) {
        $stmt = $pdo->prepare("SELECT is_correct FROM assignment_submissions WHERE user_id = ? AND assignment_id = ?");
        $stmt->execute([$user_id, $has_assignment['id']]);
        $assignment_completed = (bool)$stmt->fetchColumn();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo htmlspecialchars($topic['topic_name']); ?> | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        .topic-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .topic-header {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
        }
        .topic-header h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        .topic-meta {
            display: flex;
            gap: 20px;
            font-size: 16px;
            opacity: 0.9;
        }
        .topic-image {
            margin-bottom: 25px;
            text-align: center;
        }
        .topic-image img {
            max-width: 100%;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .content-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .guest-message {
            background: #fef9c3;
            color: #854d0e;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-complete {
            background: #10b981;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-complete:hover {
            background: #059669;
        }
        .completed-message {
            background: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .home-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 20px 0;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        .home-link:hover {
            text-decoration: underline;
        }
        
        /* Стили для кнопки "Выполнить задание" */
        .assignment-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: linear-gradient(135deg, #f3e8ff, #e9d5ff);
            border-radius: 16px;
        }
        
        .btn-assignment {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            padding: 16px 36px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 18px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
            border: none;
            cursor: pointer;
        }
        
        .btn-assignment:hover {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
        }
        
        .btn-assignment:active {
            transform: translateY(0);
        }
        
        .btn-assignment i {
            font-size: 20px;
        }
        
        .assignment-completed-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #10b981;
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 15px;
        }
        
        .assignment-completed-badge i {
            font-size: 16px;
        }
        
        .assignment-info {
            margin-top: 15px;
            font-size: 14px;
            color: #6d28d9;
        }
        
        /* Стили для модального окна и профиля */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background-color: white;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
        }
        .modal-header h2 {
            margin: 0;
            font-size: 22px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .modal-header h2 i {
            color: #3b82f6;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            color: #64748b;
            cursor: pointer;
            transition: color 0.3s ease;
            line-height: 1;
        }
        .modal-close:hover {
            color: #dc2626;
        }
        .modal-body {
            padding: 30px 25px;
        }
        .about-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 20px;
        }
        .about-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        .about-icon i {
            font-size: 40px;
            color: white;
        }
        .about-text {
            color: #475569;
            font-size: 16px;
            line-height: 1.6;
        }
        .about-text p {
            margin: 10px 0;
        }
        .about-text .highlight {
            font-size: 18px;
            color: #1e293b;
            background-color: #f8fafc;
            padding: 12px 20px;
            border-radius: 40px;
            display: inline-block;
            margin: 10px 0;
        }
        .about-text .highlight i {
            color: #3b82f6;
            margin-right: 8px;
        }
        .about-text .divider {
            width: 50px;
            height: 2px;
            background: linear-gradient(to right, transparent, #3b82f6, transparent);
            margin: 20px auto;
        }
        .about-text .date {
            color: #94a3b8;
            font-size: 14px;
            margin-top: 20px;
        }
        
        /* Стили для выпадающего меню профиля */
        .profile-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 280px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 100;
        }
        .profile-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .user-info {
            display: flex;
            gap: 12px;
            padding: 16px;
        }
        .user-avatar {
            width: 48px;
            height: 48px;
            background-color: #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        .user-details h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .user-details p {
            font-size: 14px;
            color: #64748b;
            margin: 0;
        }
        .dropdown-divider {
            height: 1px;
            background-color: #e2e8f0;
            margin: 8px 0;
        }
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #475569;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .dropdown-item:hover {
            background-color: #f8fafc;
        }
        .dropdown-item i {
            width: 20px;
            color: #3b82f6;
        }
        .dropdown-item.logout {
            color: #dc2626;
            border-top: 1px solid #e2e8f0;
            margin-top: 8px;
        }
        .dropdown-item.logout i {
            color: #dc2626;
        }
        
        /* Стили для шапки */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo i {
            font-size: 28px;
            color: #3b82f6;
        }
        .logo h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .about-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 40px;
            color: #475569;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .about-btn i {
            font-size: 18px;
            color: #3b82f6;
        }
        .about-btn:hover {
            background-color: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }
        .about-btn:hover i {
            color: white;
        }
        .profile-section {
            position: relative;
        }
        .profile-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 40px;
            color: #1e293b;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .profile-btn i {
            color: #3b82f6;
        }
        .profile-btn:hover {
            border-color: #3b82f6;
        }
        
        /* Подвал */
        .footer {
            padding: 40px 0;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            margin-top: 60px;
        }
        .footer-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 20px;
            margin-bottom: 15px;
        }
        .footer-info p {
            margin: 5px 0;
            color: #64748b;
            font-size: 14px;
        }
        
        /* Тёмная тема */
        body.theme-dark .content-section {
            background: #1e293b;
            color: #e2e8f0;
        }
        body.theme-dark .assignment-section {
            background: linear-gradient(135deg, #2d1b4e, #1a1030);
        }
        body.theme-dark .modal-content {
            background: #1e293b;
            color: #e2e8f0;
        }
        body.theme-dark .modal-header {
            border-bottom-color: #334155;
        }
        body.theme-dark .modal-header h2 {
            color: #e2e8f0;
        }
        body.theme-dark .about-text .highlight {
            background: #334155;
            color: #e2e8f0;
        }
        body.theme-dark .profile-btn {
            background: #334155;
            color: #e2e8f0;
        }
        body.theme-dark .topic-image img {
            border: 1px solid #334155;
        }
        /* Стили для содержимого темы */
.topic-content {
    color: #1e293b;
    line-height: 1.8;
}

.topic-content h1, .topic-content h2, .topic-content h3 {
    color: #1e293b;
    margin-top: 25px;
    margin-bottom: 15px;
}

.topic-content p {
    margin-bottom: 15px;
    color: #1e293b;
}

.topic-content ul, .topic-content ol {
    margin: 15px 0 15px 25px;
    color: #1e293b;
}

.topic-content li {
    margin-bottom: 8px;
}

.topic-content code {
    background: #f1f5f9;
    padding: 2px 8px;
    border-radius: 4px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.9em;
    color: #0f172a;
}

.topic-content pre {
    background: #1e1e1e;
    color: #e2e8f0;
    padding: 15px;
    border-radius: 8px;
    overflow-x: auto;
    font-family: 'JetBrains Mono', monospace;
    font-size: 14px;
    margin: 15px 0;
}

.topic-content pre code {
    background: transparent;
    color: #e2e8f0;
    padding: 0;
}

.topic-content .code-block {
    background: #1e1e1e;
    border-radius: 8px;
    margin: 15px 0;
    overflow: hidden;
}

.topic-content .code-label {
    background: #2d2d2d;
    color: #e2e8f0;
    padding: 8px 15px;
    font-size: 12px;
    font-family: monospace;
    border-bottom: 1px solid #3d3d3d;
}

.topic-content .task-example {
    background: #f0fdf4;
    border-left: 4px solid #10b981;
    padding: 15px 20px;
    margin: 20px 0;
    border-radius: 8px;
}

.topic-content .info-box {
    background: #eff6ff;
    border-left: 4px solid #3b82f6;
    padding: 15px 20px;
    margin: 20px 0;
    border-radius: 8px;
}

/* Тёмная тема для содержимого */
body.theme-dark .topic-content {
    color: #e2e8f0;
}

body.theme-dark .topic-content h1,
body.theme-dark .topic-content h2,
body.theme-dark .topic-content h3 {
    color: #f1f5f9;
}

body.theme-dark .topic-content p,
body.theme-dark .topic-content ul,
body.theme-dark .topic-content ol,
body.theme-dark .topic-content li {
    color: #e2e8f0;
}

body.theme-dark .topic-content code {
    background: #334155;
    color: #e2e8f0;
}

body.theme-dark .topic-content .task-example {
    background: #1a3a2a;
    border-left-color: #10b981;
}

body.theme-dark .topic-content .info-box {
    background: #1e2a4a;
    border-left-color: #3b82f6;
}
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size) ?>">
    <div class="container">
        <!-- Шапка -->
        <header class="header">
            <div class="logo">
                <i class="fas fa-code"></i>
                <h1>CodeLeans</h1>
            </div>
            <div class="header-actions">
                <button class="about-btn" id="aboutBtn">
                    <i class="fas fa-info-circle"></i>
                    <span>О нас</span>
                </button>
                <div class="profile-section">
                    <button class="profile-btn" id="profileBtn">
                        <?php if (isset($_SESSION['avatar']) && $_SESSION['avatar'] && file_exists($_SESSION['avatar'])): ?>
                            <img src="<?= htmlspecialchars($_SESSION['avatar']) ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;margin-right:5px;">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="user-info">
                            <div class="user-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="user-details">
                                <?php if ($is_logged_in): ?>
                                    <h3><?php echo htmlspecialchars($user_name); ?></h3>
                                    <p><?php echo htmlspecialchars($user_email); ?></p>
                                    <p><small>Роль: <?php echo getUserRolesDisplay($user_roles); ?></small></p>
                                <?php else: ?>
                                    <h3>Гость</h3>
                                    <p>Войдите для доступа к чату и прогрессу</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <?php if ($is_logged_in): ?>
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user-cog"></i> Мой профиль
                            </a>
                            <a href="settings.php" class="dropdown-item">
                                <i class="fas fa-sliders-h"></i> Настройки
                            </a>
                            <?php
                            $menu_courses = $pdo->query("SELECT id, course_name FROM courses WHERE is_published = 1")->fetchAll();
                            foreach ($menu_courses as $c):
                            ?>
                                <a href="courses.php?course_id=<?php echo $c['id']; ?>" class="dropdown-item">
                                    <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($c['course_name']); ?> курс
                                </a>
                            <?php endforeach; ?>
                            <?php if ($is_teacher): ?>
                                <a href="create_course.php" class="dropdown-item">
                                    <i class="fas fa-plus-circle"></i> Создать курс
                                </a>
                            <?php endif; ?>
                            <a href="logout.php" class="dropdown-item logout">
                                <i class="fas fa-sign-out-alt"></i> Выйти
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="dropdown-item">
                                <i class="fas fa-sign-in-alt"></i> Вход
                            </a>
                            <a href="register.php" class="dropdown-item">
                                <i class="fas fa-user-plus"></i> Регистрация
                            </a>
                        <?php endif; ?>
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
                        <div class="about-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="about-text">
                            <p>Данное пособие не является коммерческим проектом и не предназначено для дальнейшей продажи.</p>
                            <p>Оно создано исключительно в рамках курсового проекта под руководством научного руководителя</p>
                            <p class="highlight"><i class="fas fa-user-tie"></i> <strong>Согомонян Инны Эдуардовны</strong></p>
                            <div class="divider"></div>
                            <p>Разработчиком является студент 35 группы</p>
                            <p class="highlight"><i class="fas fa-user"></i> <strong>Фадеев Виктор Юрьевич</strong></p>
                            <div class="divider"></div>
                            <p class="date"><i class="fas fa-calendar-alt"></i> © 2025 CodeLeans. Все права защищены.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="topic-container">
            <!-- Ссылка на главную -->
            <a href="index.php" class="home-link">
                <i class="fas fa-home"></i> Вернуться на главную
            </a>

            <div class="topic-header">
                <h1><?php echo htmlspecialchars($topic['topic_name']); ?></h1>
                <p>Курс: <?php echo htmlspecialchars($topic['course_name']); ?></p>
                <div class="topic-meta">
                    <span><i class="far fa-clock"></i> <?php echo $topic['duration_minutes']; ?> мин</span>
                    <?php if ($has_assignment): ?>
                        <span><i class="fas fa-tasks"></i> Есть практическое задание</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ИЗОБРАЖЕНИЕ ТЕМЫ (если есть) -->
            <?php if (!empty($topic['image_path']) && file_exists($topic['image_path'])): ?>
                <div class="topic-image">
                    <img src="<?= htmlspecialchars($topic['image_path']) ?>" alt="<?= htmlspecialchars($topic['topic_name']) ?>">
                </div>
            <?php endif; ?>

            <div class="content-section topic-content">
    <?php echo $topic['content']; ?>
</div>

            <?php if (!$is_logged_in): ?>
                <div class="guest-message">
                    <i class="fas fa-info-circle"></i> Вы изучаете материалы как гость. 
                    <a href="login.php">Войдите</a>, чтобы сохранять прогресс и выполнять задания.
                </div>
            <?php endif; ?>

            <?php if ($is_logged_in && !$is_completed): ?>
                <div class="topic-actions" style="text-align: center;">
                    <button class="btn-complete" onclick="markTopicComplete(<?php echo $topic_id; ?>)">
                        <i class="fas fa-check-circle"></i> Отметить как пройденное
                    </button>
                </div>
            <?php elseif ($is_logged_in && $is_completed): ?>
                <div class="completed-message">
                    <i class="fas fa-check-circle" style="color: #10b981;"></i> Вы уже прошли эту тему.
                </div>
            <?php endif; ?>

            <!-- Блок с заданиями -->
            <?php if ($has_assignment && $is_logged_in): ?>
                <div class="assignment-section">
                    <a href="assignment_system.php?topic_id=<?= $topic_id ?>" class="btn-assignment">
                        <i class="fas fa-code"></i> Перейти к заданиям
                    </a>
                    <div class="assignment-info">
                        <i class="fas fa-info-circle"></i> Выполните все задания, чтобы завершить тему
                    </div>
                </div>
            <?php elseif ($has_assignment && !$is_logged_in): ?>
                <div class="assignment-section" style="opacity: 0.7;">
                    <button class="btn-assignment" style="cursor: not-allowed; opacity: 0.5;" disabled>
                        <i class="fas fa-lock"></i> Задания (требуется вход)
                    </button>
                    <div class="assignment-info">
                        <a href="login.php" style="color: #8b5cf6;">Войдите</a>, чтобы выполнять задания
                    </div>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 40px; text-align: center;">
                <a href="courses.php?course_id=<?php echo $topic['course_id']; ?>" class="btn" style="display: inline-block; background: #3b82f6; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                    <i class="fas fa-arrow-left"></i> Назад к курсу
                </a>
            </div>
        </div>

        <!-- Подвал -->
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-logo">
                    <i class="fas fa-code"></i>
                    <span>CodeLeans</span>
                </div>
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

            // Модальное окно "О нас"
            const aboutBtn = document.getElementById('aboutBtn');
            const aboutModal = document.getElementById('aboutModal');
            const closeModal = document.getElementById('closeModal');
            if (aboutBtn && aboutModal && closeModal) {
                aboutBtn.addEventListener('click', function() {
                    aboutModal.classList.add('show');
                    document.body.style.overflow = 'hidden';
                });
                function closeAboutModal() {
                    aboutModal.classList.remove('show');
                    document.body.style.overflow = '';
                }
                closeModal.addEventListener('click', closeAboutModal);
                aboutModal.addEventListener('click', function(e) {
                    if (e.target === aboutModal) closeAboutModal();
                });
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && aboutModal.classList.contains('show')) closeAboutModal();
                });
            }
        });

        function markTopicComplete(topicId) {
            fetch('mark_complete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ topic_id: topicId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const actionsDiv = document.querySelector('.topic-actions');
                    if (actionsDiv) {
                        actionsDiv.innerHTML = '<div class="completed-message"><i class="fas fa-check-circle"></i> Вы уже прошли эту тему.</div>';
                    }
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    alert('Ошибка при сохранении: ' + (data.error || 'Неизвестная ошибка'));
                }
            })
            .catch(err => {
                alert('Ошибка соединения: ' + err.message);
            });
        }
    </script>
</body>
</html>