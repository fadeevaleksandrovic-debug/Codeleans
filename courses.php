<?php
require_once 'config/config.php';

// Запрещаем кеширование страницы
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if (!$course_id) {
    die('Курс не указан');
}

// Информация о курсе
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();
if (!$course) {
    die('Курс не найден');
}

// Темы курса
$stmt = $pdo->prepare("SELECT * FROM topics WHERE course_id = ? ORDER BY id");
$stmt->execute([$course_id]);
$topics = $stmt->fetchAll();

// Прогресс пользователя по темам (если авторизован)
$completed_topics = [];
$progress = 0;
if ($is_logged_in) {
    $stmt = $pdo->prepare("SELECT topic_id, is_completed FROM topic_progress WHERE user_id = ?");
    $stmt->execute([$user_id]);
    while ($row = $stmt->fetch()) {
        $completed_topics[$row['topic_id']] = $row['is_completed'];
    }
    
    // Получаем общий прогресс по курсу
    $stmt = $pdo->prepare("SELECT progress_percent FROM user_progress WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$user_id, $course_id]);
    $progress = $stmt->fetchColumn() ?: 0;
}

// Проверяем, есть ли сертификат
$has_certificate = false;
$certificate = null;
if ($is_logged_in && $progress >= 100) {
    $stmt = $pdo->prepare("SELECT * FROM certificates WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$user_id, $course_id]);
    $certificate = $stmt->fetch();
    $has_certificate = ($certificate !== false);
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
    <title><?php echo htmlspecialchars($course['course_name']); ?> | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* Дополнительные стили для страницы курса */
        .topics-list {
            margin: 30px 0;
        }
        .topic-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s, background 0.3s;
            position: relative;
        }
        .topic-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .topic-item.completed {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
        }
        .topic-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .topic-link h3 {
            color: #1e293b;
            margin-bottom: 5px;
        }
        .topic-link p {
            color: #475569;
            margin-bottom: 10px;
        }
        .completed-badge {
            position: absolute;
            top: 15px;
            right: 20px;
            color: #10b981;
            font-weight: 500;
        }
        .course-actions {
            margin-top: 30px;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
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
        .btn-secondary:hover {
            background: #475569;
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
        
        /* Стили для сертификата */
        .certificate-section {
            text-align: center;
            margin-top: 30px;
            padding: 25px;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 16px;
            animation: pulse 2s infinite;
        }
        
        .certificate-section i {
            font-size: 48px;
            color: #d97706;
            margin-bottom: 10px;
        }
        
        .certificate-section h3 {
            color: #92400e;
            margin-top: 10px;
            font-size: 24px;
        }
        
        .certificate-section p {
            color: #78350f;
            margin: 10px 0;
        }
        
        .btn-certificate {
            display: inline-block;
            background: #d97706;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 15px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-certificate:hover {
            background: #b45309;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(217, 119, 6, 0.3);
        }
        
        .btn-certificate i {
            font-size: 16px;
            margin-right: 8px;
        }
        
        .progress-notice {
            text-align: center;
            margin-top: 30px;
            padding: 15px 20px;
            background: #f1f5f9;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .progress-notice i {
            font-size: 20px;
            color: #3b82f6;
        }
        
        .progress-notice span {
            color: #475569;
        }
        
        .progress-notice .progress-value {
            font-weight: 700;
            color: #3b82f6;
            font-size: 18px;
        }
        
        .certificate-issued {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: #d1fae5;
            border-radius: 16px;
        }
        
        .certificate-issued i {
            font-size: 48px;
            color: #10b981;
        }
        
        .certificate-issued h3 {
            color: #065f46;
            margin-top: 10px;
        }
        
        .certificate-issued .btn-view {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 15px;
            font-weight: 600;
        }
        
        .certificate-issued .btn-view:hover {
            background: #059669;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.01); }
            100% { transform: scale(1); }
        }
        
        /* Стили для модального окна и профиля (копия из index.php) */
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
        
        /* Тёмная тема */
        body.theme-dark .topic-item {
            background: #1e293b;
        }
        body.theme-dark .topic-item.completed {
            background: #0f2e1a;
        }
        body.theme-dark .topic-link h3 {
            color: #f1f5f9;
        }
        body.theme-dark .topic-link p {
            color: #94a3b8;
        }
        body.theme-dark .progress-notice {
            background: #1e293b;
        }
        body.theme-dark .progress-notice span {
            color: #94a3b8;
        }
        body.theme-dark .certificate-section {
            background: linear-gradient(135deg, #78350f, #92400e);
        }
        body.theme-dark .certificate-section h3 {
            color: #fde68a;
        }
        body.theme-dark .certificate-section p {
            color: #fef3c7;
        }
        body.theme-dark .certificate-issued {
            background: #064e3b;
        }
        body.theme-dark .certificate-issued h3 {
            color: #a7f3d0;
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

        <!-- Основной контент -->
        <main>
            <a href="index.php" class="home-link">
                <i class="fas fa-home"></i> Вернуться на главную
            </a>

            <div class="course-detail">
                <h1><?php echo htmlspecialchars($course['course_name']); ?></h1>
                <p class="course-description"><?php echo htmlspecialchars($course['course_description']); ?></p>

                <h2>Темы курса</h2>
                <div class="topics-list">
                    <?php foreach ($topics as $topic): 
                        $completed = isset($completed_topics[$topic['id']]) && $completed_topics[$topic['id']];
                    ?>
                        <div class="topic-item <?php echo $completed ? 'completed' : ''; ?>" data-topic-id="<?php echo $topic['id']; ?>">
                            <a href="topic.php?id=<?php echo $topic['id']; ?>" class="topic-link">
                                <h3><?php echo htmlspecialchars($topic['topic_name']); ?></h3>
                                <p><?php echo htmlspecialchars($topic['topic_description']); ?></p>
                                <small>Длительность: <?php echo $topic['duration_minutes']; ?> мин</small>
                            </a>
                            <?php if ($is_logged_in && $completed): ?>
                                <span class="completed-badge"><i class="fas fa-check-circle"></i> Пройдено</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($is_logged_in): ?>
    <div class="course-actions">
        <?php
        // Проверяем, закреплён ли текущий преподаватель за этим курсом
        $is_teacher_assigned = false;
        if ($is_teacher) {
            $stmt = $pdo->prepare("SELECT 1 FROM course_teachers WHERE course_id = ? AND teacher_id = ?");
            $stmt->execute([$course_id, $user_id]);
            $is_teacher_assigned = (bool)$stmt->fetchColumn();
        }
        // Есть ли вообще преподаватели у курса
        $has_teachers = $pdo->prepare("SELECT COUNT(*) FROM course_teachers WHERE course_id = ?");
        $has_teachers->execute([$course_id]);
        $has_teachers = $has_teachers->fetchColumn() > 0;
        ?>
        
        <!-- Общий чат показываем всем авторизованным, кроме преподавателей, не закреплённых за курсом -->
        <?php if (!$is_teacher || ($is_teacher && $is_teacher_assigned)): ?>
            <a href="chat.php?course_id=<?= $course_id ?>" class="btn btn-primary">
                <i class="fas fa-comments"></i> Чат курса
            </a>
        <?php endif; ?>
        
        <!-- Личный чат -->
        <?php if ($is_teacher && $is_teacher_assigned): ?>
            <a href="teacher_messages.php" class="btn" style="background: #8b5cf6; color: white;">
                <i class="fas fa-envelope"></i> Сообщения от учеников
            </a>
        <?php elseif (!$is_teacher && $has_teachers): ?>
            <a href="private_chat.php?course_id=<?= $course_id ?>" class="btn" style="background: #8b5cf6; color: white;">
                <i class="fas fa-user-friends"></i> Написать преподавателю
            </a>
        <?php endif; ?>
        
        <?php if ($user_id == $course['created_by'] || in_array('admin', $user_roles)): ?>
            <a href="manage_course.php?id=<?= $course_id ?>" class="btn btn-secondary">
                <i class="fas fa-cog"></i> Управление курсом
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>
                
                <!-- Блок с сертификатом -->
                <?php if ($is_logged_in): ?>
                    <?php if ($progress >= 100): ?>
                        <?php if ($has_certificate): ?>
                            <!-- Уже есть сертификат -->
                            <div class="certificate-issued">
                                <i class="fas fa-certificate"></i>
                                <h3>Сертификат получен!</h3>
                                <p>Вы успешно завершили курс и получили сертификат</p>
                                <a href="generate_certificate.php?course_id=<?= $course_id ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> Посмотреть сертификат
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Курс пройден, можно получить сертификат -->
                            <div class="certificate-section">
                                <i class="fas fa-award"></i>
                                <h3>Курс успешно завершен!</h3>
                                <p>Вы изучили все темы курса. Получите сертификат о прохождении!</p>
                                <a href="generate_certificate.php?course_id=<?= $course_id ?>" class="btn-certificate">
                                    <i class="fas fa-certificate"></i> Получить сертификат
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Прогресс не 100% -->
                        <div class="progress-notice">
                            <i class="fas fa-chart-line"></i>
                            <span>Ваш прогресс:</span>
                            <span class="progress-value"><?= round($progress, 1) ?>%</span>
                            <span>|</span>
                            <span>Пройдите все темы, чтобы получить сертификат</span>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Не авторизован -->
                    <div class="progress-notice">
                        <i class="fas fa-lock"></i>
                        <span><a href="login.php" style="color: #3b82f6;">Войдите</a> или <a href="register.php" style="color: #3b82f6;">зарегистрируйтесь</a>, чтобы отслеживать прогресс и получить сертификат</span>
                    </div>
                <?php endif; ?>
            </div>
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

            // AJAX-проверка статуса тем
            const topicItems = document.querySelectorAll('.topic-item');
            topicItems.forEach(item => {
                const topicId = item.getAttribute('data-topic-id');
                if (topicId) {
                    fetch(`check_topic_status.php?topic_id=${topicId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.completed) {
                                item.classList.add('completed');
                                if (!item.querySelector('.completed-badge')) {
                                    const badge = document.createElement('span');
                                    badge.className = 'completed-badge';
                                    badge.innerHTML = '<i class="fas fa-check-circle"></i> Пройдено';
                                    item.appendChild(badge);
                                }
                            }
                        })
                        .catch(err => console.log('Ошибка проверки статуса:', err));
                }
            });
        });
    </script>
</body>
</html>