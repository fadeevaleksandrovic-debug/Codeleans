<?php
require_once 'config/config.php';

// Получаем аватар из сессии для отображения в меню
$avatar = $_SESSION['avatar'] ?? null;
if ($is_logged_in && !$avatar) {
    // Если в сессии нет, пробуем загрузить из БД
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $avatar = $stmt->fetchColumn();
    if ($avatar && file_exists($avatar)) {
        $_SESSION['avatar'] = $avatar;
    }
}
// Получаем все общие курсы (публичные или созданные текущим пользователем)
$courses = getAvailableCourses($pdo, $user_id, $is_logged_in, $user_roles);

// Получаем курсы, созданные преподавателями (только для авторизованных)
$teacher_courses = [];
if ($is_logged_in) {
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name as teacher_name 
        FROM courses c
        JOIN users u ON c.created_by = u.id
        WHERE c.created_by IS NOT NULL AND c.is_published = 1
        ORDER BY c.id DESC
    ");
    $stmt->execute();
    $teacher_courses = $stmt->fetchAll();
}

// Получаем прогресс пользователя по курсам (если авторизован)
$user_progress = [];
if ($is_logged_in) {
    $stmt = $pdo->prepare("SELECT course_id, progress_percent FROM user_progress WHERE user_id = ?");
    $stmt->execute([$user_id]);
    while ($row = $stmt->fetch()) {
        $user_progress[$row['course_id']] = $row['progress_percent'];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* Стили для кнопки О нас и модального окна (как в вашем оригинале) */
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

        /* Модальное окно */
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

        /* Стили для кнопок курсов */
        .course-btn {
            width: 100%;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            background-color: #3b82f6;
            color: white;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .course-btn:hover {
            background-color: #2563eb;
        }

        .start-btn {
            background-color: #10b981;
        }

        .start-btn:hover {
            background-color: #059669;
        }

        .course-btn i {
            margin-right: 8px;
        }

        /* Стили для секции "Почему выбирают" */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .feature-item {
            background: white;
            padding: 30px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }

        .feature-item:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 48px;
            color: #3b82f6;
            margin-bottom: 20px;
        }

        .feature-item h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #1e293b;
        }

        .feature-item p {
            color: #64748b;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .header-actions {
                gap: 10px;
            }
            .about-btn span {
                display: none;
            }
            .about-btn {
                padding: 10px;
            }
        }

        /* Кнопка создания курса */
        .create-course-btn {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 30px;
            transition: background 0.3s;
        }
        .create-course-btn:hover {
            background: #2563eb;
        }
        .create-course-btn i {
            margin-right: 8px;
        }

        /* Стили для блока курсов от преподавателей */
        .teacher-courses-section {
            margin-top: 60px;
        }
        .teacher-info {
            margin-top: 10px;
            font-size: 13px;
            color: #3b82f6;
        }
        .btn-small {
            display: inline-block;
            background: #64748b;
            color: white;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
            transition: background 0.3s;
            margin-top: 10px;
        }
        .btn-small:hover {
            background: #475569;
        }
        .empty-message {
            text-align: center;
            padding: 40px;
            background: #f8fafc;
            border-radius: 16px;
        }
        body.theme-dark .empty-message {
            background: #1e293b;
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
                <?php if (!in_array('admin', $user_roles)): ?>
    <a href="support.php" class="about-btn">
        <i class="fas fa-headset"></i>
        <span>Поддержка</span>
    </a>
<?php endif; ?>
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
    <a href="teacher_assignments.php" class="dropdown-item">
        <i class="fas fa-clipboard-list"></i> Проверка заданий
    </a>
                                <?php if (in_array('admin', $user_roles)): ?>
        <div class="dropdown-divider"></div>
        <a href="admin.php" class="dropdown-item">
            <i class="fas fa-shield-alt"></i> Админ панель
        </a>
    <?php endif; ?>
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

        <!-- Основной контент -->
        <main class="main-content">
            <!-- Приветственная секция -->
            <section class="hero">
                <h2>Добро пожаловать в CodeLeans, <?php echo htmlspecialchars($user_name); ?>!</h2>
            </section>

            <!-- Кнопка создания курса для учителя/админа -->
            <?php if ($is_logged_in && $is_teacher): ?>
                <div style="text-align: right; margin-bottom: 20px;">
                    <a href="create_course.php" class="create-course-btn">
                        <i class="fas fa-plus-circle"></i> Создать новый курс
                    </a>
                </div>
            <?php endif; ?>

            <!-- Секция "Почему выбирают наше пособие" - показываем только для гостей -->
            <?php if (!$is_logged_in): ?>
            <section class="why-choose">
                <h2 class="section-title">Почему выбирают наше пособие</h2>
                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Надежность и удобство</h3>
                        <p>Стабильная работа платформы 24/7, интуитивно понятный интерфейс и доступ к материалам с любого устройства. Ваш прогресс всегда сохраняется автоматически.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <h3>Интерактивность в обучении</h3>
                        <p>Практические задания с мгновенной проверкой, интерактивные примеры кода и возможность сразу применять полученные знания на практике.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h3>Актуальные знания</h3>
                        <p>Материалы обновляются в соответствии с последними версиями языков программирования и требованиями рынка. Реальные проекты и актуальные технологии.</p>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- Секция доступных курсов (общие курсы) -->
            <section class="courses-section">
                <h2><i class="fas fa-graduation-cap"></i> Доступные курсы для изучения</h2>
                <p class="section-description">Выбирайте язык программирования и начинайте учиться</p>
                <div class="courses-grid">
                    <?php foreach ($courses as $course): 
                        $progress = isset($user_progress[$course['id']]) ? $user_progress[$course['id']] : 0;
                    ?>
                        <div class="course-card" data-course="<?php echo strtolower($course['course_name']); ?>">
                            <div class="course-icon <?php echo strtolower($course['course_name']); ?>">
                                <?php 
                                $icon_name = strtolower($course['course_name']);
                                if ($icon_name == 'javascript' || $icon_name == 'js') {
                                    $icon_name = 'js';
                                }
                                ?>
                                <i class="fab fa-<?php echo $icon_name; ?>"></i>
                            </div>
                            <div class="course-content">
                                <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                                <p><?php echo htmlspecialchars($course['course_description']); ?></p>
                                <div class="course-stats">
                                    <span><i class="fas fa-play-circle"></i> <?php echo $course['total_topic']; ?> тем</span>
                                    <span><i class="fas fa-clock"></i> <?php echo $course['total_hours']; ?> часов</span>
                                </div>
                                <?php if ($is_logged_in): ?>
                                    <div class="progress-container">
                                        <div class="progress-label">
                                            <span>Прогресс:</span>
                                            <span class="progress-percent"><?php echo number_format($progress, 1); ?>%</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $progress; ?>%; background-color: #3b82f6;"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="courses.php?course_id=<?php echo $course['id']; ?>" class="course-btn">
                                <?php if ($is_logged_in): ?>
                                    <i class="fas fa-play-circle"></i> Перейти к курсу
                                <?php else: ?>
                                    <i class="fas fa-eye"></i> Смотреть как гость
                                <?php endif; ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- НОВЫЙ БЛОК: Курсы от преподавателей (только для авторизованных) -->
            <?php if ($is_logged_in): ?>
                <section class="teacher-courses-section">
                    <h2><i class="fas fa-chalkboard-teacher"></i> Курсы от преподавателей</h2>
                    <p class="section-description">Курсы, созданные нашими преподавателями специально для вас</p>
                    
                    <?php if (empty($teacher_courses)): ?>
                        <div class="empty-message">
                            <i class="fas fa-info-circle" style="font-size: 48px; color: #94a3b8;"></i>
                            <p style="margin-top: 15px; color: #64748b;">Пока нет курсов от преподавателей. Загляните позже!</p>
                        </div>
                    <?php else: ?>
                        <div class="courses-grid">
                            <?php foreach ($teacher_courses as $course): 
                                $progress = isset($user_progress[$course['id']]) ? $user_progress[$course['id']] : 0;
                            ?>
                                <div class="course-card" data-course="<?php echo strtolower($course['course_name']); ?>">
                                    <div class="course-icon <?php echo strtolower($course['course_name']); ?>">
                                        <?php 
                                        $icon_name = strtolower($course['course_name']);
                                        if ($icon_name == 'javascript' || $icon_name == 'js') $icon_name = 'js';
                                        ?>
                                        <i class="fab fa-<?php echo $icon_name; ?>"></i>
                                    </div>
                                    <div class="course-content">
                                        <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                                        <p><?php echo htmlspecialchars($course['course_description']); ?></p>
                                        <div class="course-stats">
                                            <span><i class="fas fa-play-circle"></i> <?php echo $course['total_topic']; ?> тем</span>
                                            <span><i class="fas fa-clock"></i> <?php echo $course['total_hours']; ?> часов</span>
                                        </div>
                                        <div class="teacher-info">
                                            <i class="fas fa-user-graduate"></i> Преподаватель: <?php echo htmlspecialchars($course['teacher_name']); ?>
                                        </div>
                                        <div class="progress-container">
                                            <div class="progress-label">
                                                <span>Ваш прогресс:</span>
                                                <span class="progress-percent"><?php echo number_format($progress, 1); ?>%</span>
                                            </div>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $progress; ?>%; background-color: #3b82f6;"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="courses.php?course_id=<?php echo $course['id']; ?>" class="course-btn">
                                        <i class="fas fa-play-circle"></i> Перейти к курсу
                                    </a>
                                    <?php if ($user_id == $course['created_by'] || in_array('admin', $user_roles)): ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>

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

            // Клик по карточке курса (для перехода)
            document.querySelectorAll('.course-card').forEach(card => {
                const link = card.querySelector('.course-btn');
                if (link) {
                    card.style.cursor = 'pointer';
                    card.addEventListener('click', (e) => {
                        if (!e.target.closest('.course-btn')) {
                            window.location.href = link.href;
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>