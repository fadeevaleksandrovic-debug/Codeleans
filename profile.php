<?php
require_once 'config/config.php';

if (isset($_POST['toggle_theme'])) {
    $_SESSION['site_theme'] = $_SESSION['site_theme'] === 'dark' ? 'light' : 'dark';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!$is_logged_in) {
    header('Location: login.php');
    exit;
}

// Получаем данные пользователя с датой регистрации
$stmt = $pdo->prepare("SELECT full_name, email, avatar, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die('Пользователь не найден');
}

// Разделяем full_name на имя и фамилию
$full_name = $user['full_name'];
$name_parts = explode(' ', $full_name, 2);
$first_name = $name_parts[0];
$last_name = isset($name_parts[1]) ? $name_parts[1] : '';

// Форматируем дату регистрации
// Функция для получения даты с русским месяцем
function getRussianDate($date) {
    $months = [
        1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
        5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
        9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря'
    ];
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp);
    return "$day $month $year";
}

$registered_date = getRussianDate($user['created_at']);
$registered_date_short = date('d.m.Y', strtotime($user['created_at']));
$days_on_site = floor((time() - strtotime($user['created_at'])) / (60 * 60 * 24));

// Прогресс по курсам
$progress_stmt = $pdo->prepare("
    SELECT c.course_name, up.progress_percent 
    FROM user_progress up 
    JOIN courses c ON up.course_id = c.id 
    WHERE up.user_id = ?
");
$progress_stmt->execute([$user_id]);
$progress = $progress_stmt->fetchAll();

// Статистика: завершённые темы и общее время
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(tp.id) as completed_topics,
        COALESCE(SUM(t.duration_minutes), 0) as total_minutes
    FROM topic_progress tp
    JOIN topics t ON tp.topic_id = t.id
    WHERE tp.user_id = ? AND tp.is_completed = 1
");
$stats_stmt->execute([$user_id]);
$stats = $stats_stmt->fetch();

$total_hours = floor($stats['total_minutes'] / 60);
$total_minutes_remain = $stats['total_minutes'] % 60;

// Получаем настройки темы и шрифта из сессии
$current_theme = $_SESSION['theme'] ?? 'light';
$current_font_size = $_SESSION['font_size'] ?? 'medium';
$avatar = $_SESSION['avatar'] ?? $user['avatar'] ?? null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой профиль - CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* Динамические стили для темы */
        body.theme-dark {
            background-color: #0f172a;
            color: #e2e8f0;
        }
        body.theme-dark .header,
        body.theme-dark .footer,
        body.theme-dark .course-card,
        body.theme-dark .content-section,
        body.theme-dark .modal-content,
        body.theme-dark .form-container,
        body.theme-dark .profile-container .stat-card,
        body.theme-dark .profile-container .progress-section {
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
        
        /* Специальные стили для страницы профиля */
        .profile-container {
            max-width: 900px;
            margin: 40px auto;
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 40px;
            color: white;
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-avatar i {
            font-size: 48px;
        }
        .profile-info h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .profile-info .full-name {
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        .profile-info .email {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 8px;
        }
        .profile-info .joined {
            margin-top: 10px;
            font-size: 14px;
            opacity: 0.9;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.15);
            padding: 5px 12px;
            border-radius: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .stat-card h3 {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
        }
        body.theme-dark .stat-card .value {
            color: #e2e8f0;
        }
        .progress-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .progress-section h2 {
            font-size: 24px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .progress-item {
            margin-bottom: 20px;
        }
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .progress-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
        }
        .progress-fill {
            height: 8px;
            background: #3b82f6;
            border-radius: 4px;
            width: 0%;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
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
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 20px 0;
            color: #3b82f6;
            text-decoration: none;
        }
        /* Модальное окно (как в index.php) */
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
        
        /* Стиль для кнопки смены темы */
        .theme-toggle-btn {
            background: transparent;
            border: 1px solid currentColor;
            border-radius: 20px;
            padding: 5px 15px;
            cursor: pointer;
        }
        /* Блок информации о пользователе (профиль) */
.profile-card {
    background: white;
    border-radius: 24px;
    padding: 30px;
    margin-bottom: 40px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
    align-items: center;
    transition: background 0.3s, box-shadow 0.3s;
}

body.theme-dark .profile-card {
    background: #1e293b;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
}

.profile-card-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
}

.profile-card-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-card-avatar i {
    font-size: 64px;
    color: white;
}

.profile-card-info {
    flex: 1;
}

.profile-card-name {
    font-size: 32px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 15px;
}

body.theme-dark .profile-card-name {
    color: #f1f5f9;
}

.profile-card-details {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 25px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8fafc;
    padding: 8px 16px;
    border-radius: 40px;
    font-size: 14px;
    color: #475569;
}

body.theme-dark .detail-item {
    background: #334155;
    color: #cbd5e1;
}

.detail-item i {
    color: #3b82f6;
    width: 18px;
}

.days-badge {
    background: #e2e8f0;
    padding: 2px 10px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 8px;
    color: #1e293b;
}

body.theme-dark .days-badge {
    background: #475569;
    color: #e2e8f0;
}

.profile-card-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.theme-toggle-btn {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 40px;
    padding: 10px 20px;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s;
}

body.theme-dark .theme-toggle-btn {
    background: #334155;
    border-color: #475569;
    color: #e2e8f0;
}

.theme-toggle-btn:hover {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size) ?>">
<div class="container">
    <!-- ШАПКА -->
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
                    <span><?= htmlspecialchars($user_name) ?></span>
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
                            <h3><?= htmlspecialchars($user_name) ?></h3>
                            <p><?= htmlspecialchars($user_email) ?></p>
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

    <!-- ОСНОВНОЙ КОНТЕНТ ПРОФИЛЯ -->
    <div class="profile-container">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Вернуться на главную
        </a>

        <!-- Блок информации о пользователе -->
<div class="profile-card">
    <div class="profile-card-avatar">
        <?php if ($avatar && file_exists($avatar)): ?>
            <img src="<?= htmlspecialchars($avatar) ?>" alt="avatar">
        <?php else: ?>
            <i class="fas fa-user-circle"></i>
        <?php endif; ?>
    </div>
    <div class="profile-card-info">
        <h1 class="profile-card-name"><?= htmlspecialchars($user['full_name']) ?></h1>
        <div class="profile-card-details">
            <div class="detail-item">
                <i class="fas fa-envelope"></i>
                <span><?= htmlspecialchars($user['email']) ?></span>
            </div>
            <div class="detail-item">
                <i class="fas fa-calendar-alt"></i>
                <span>На сайте с <?= $registered_date ?></span>
                <?php if ($days_on_site > 0): ?>
                    <span class="days-badge"><?= $days_on_site ?> <?= declOfNum($days_on_site, ['день', 'дня', 'дней']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Завершено тем</h3>
                <div class="value"><?= $stats['completed_topics'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <h3>Часов обучения</h3>
                <div class="value"><?= $total_hours ?> ч <?= $total_minutes_remain ?> мин</div>
            </div>
            <div class="stat-card">
                <h3>Всего минут</h3>
                <div class="value"><?= $stats['total_minutes'] ?? 0 ?></div>
            </div>
        </div>

        <div class="progress-section">
            <h2><i class="fas fa-chart-line"></i> Прогресс по курсам</h2>
            <?php if (empty($progress)): ?>
                <p>Вы ещё не начали изучать курсы.</p>
            <?php else: ?>
                <?php foreach ($progress as $cp): ?>
                    <div class="progress-item">
                        <div class="progress-header">
                            <span><?= htmlspecialchars($cp['course_name']) ?></span>
                            <span><?= round($cp['progress_percent'], 1) ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $cp['progress_percent'] ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="action-buttons">
            <a href="settings.php" class="btn btn-primary"><i class="fas fa-sliders-h"></i> Настройки</a>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-home"></i> На главную</a>
            <a href="logout.php" class="btn btn-secondary"><i class="fas fa-sign-out-alt"></i> Выйти</a>
            <form method="post" style="display: inline;">
                <button type="submit" name="toggle_theme" class="theme-toggle-btn">
                    <i class="fas <?= $_SESSION['site_theme'] === 'dark' ? 'fa-sun' : 'fa-moon' ?>"></i>
                </button>
            </form>
        </div>
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
    // Функция склонения числительных
    function declOfNum(number, titles) {
        let cases = [2, 0, 1, 1, 1, 2];
        return titles[ (number % 100 > 4 && number % 100 < 20) ? 2 : cases[(number % 10 < 5) ? number % 10 : 5] ];
    }

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
    // Авто-тема (если выбран режим auto)
    if (document.body.classList.contains('theme-auto')) {
        const darkModeMq = window.matchMedia('(prefers-color-scheme: dark)');
        const setAutoTheme = (e) => {
            if (e.matches) document.body.classList.replace('theme-auto', 'theme-dark');
            else document.body.classList.replace('theme-auto', 'theme-light');
        };
        setAutoTheme(darkModeMq);
        darkModeMq.addEventListener('change', setAutoTheme);
    }
</script>
</body>
</html>

<?php
// Функция склонения для PHP (дублируем на всякий случай)
function declOfNum($number, $titles) {
    $cases = [2, 0, 1, 1, 1, 2];
    return $titles[ ($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)] ];
}
?>