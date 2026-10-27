<?php
// config/config.php
session_start();

// Настройки базы данных
$host = 'localhost';
$dbname = 'codeleans';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Функция для получения класса темы body
function getBodyThemeClass() {
    // Проверяем наличие темы в сессии (для всех пользователей, включая гостей)
    if (isset($_SESSION['site_theme'])) {
        $theme = $_SESSION['site_theme'];
        if ($theme === 'auto') {
            return 'theme-light'; // или можно определить через JS
        }
        return 'theme-' . $theme;
    }
    
    // Если тема не установлена в сессии, пробуем получить из БД (для авторизованных)
    if (isset($_SESSION['user_id'])) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $theme = $stmt->fetchColumn();
        if ($theme && in_array($theme, ['light', 'dark', 'auto'])) {
            $_SESSION['site_theme'] = $theme;
            return 'theme-' . $theme;
        }
    }
    
    // По умолчанию - светлая тема
    return 'theme-light';
}

// Функция для переключения темы
function toggleTheme() {
    $current = $_SESSION['site_theme'] ?? 'light';
    $new = $current === 'dark' ? 'light' : 'dark';
    $_SESSION['site_theme'] = $new;
    
    // Если пользователь авторизован, сохраняем в БД
    if (isset($_SESSION['user_id'])) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
        $stmt->execute([$new, $_SESSION['user_id']]);
    }
    
    return $new;
}

// Функция для применения темы пользователя (вызывать после авторизации)
function applyUserTheme($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $theme = $stmt->fetchColumn();
    if ($theme && in_array($theme, ['light', 'dark', 'auto'])) {
        $_SESSION['site_theme'] = $theme;
    } else {
        $_SESSION['site_theme'] = 'light';
    }
}

// Функция для проверки прав на управление курсом
function canManageCourse($pdo, $user_id, $course_id, $is_admin = false) {
    if ($is_admin) return true;
    $stmt = $pdo->prepare("SELECT 1 FROM courses WHERE id = ? AND created_by = ?");
    $stmt->execute([$course_id, $user_id]);
    if ($stmt->fetch()) return true;
    $stmt = $pdo->prepare("SELECT 1 FROM course_teachers WHERE course_id = ? AND teacher_id = ?");
    $stmt->execute([$course_id, $user_id]);
    return (bool)$stmt->fetch();
}

// Определяем текущего пользователя
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Гость';
$user_email = $_SESSION['user_email'] ?? '';
$is_logged_in = isset($user_id);
$user_roles = [];
$is_teacher = false;
$is_admin = false;

if ($is_logged_in) {
    $stmt = $pdo->prepare("SELECT r.role_name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ?");
    $stmt->execute([$user_id]);
    $user_roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $is_teacher = in_array('teacher', $user_roles) || in_array('admin', $user_roles);
    $is_admin = in_array('admin', $user_roles);
    
    // Применяем тему пользователя, если её нет в сессии
    if (!isset($_SESSION['site_theme'])) {
        applyUserTheme($pdo, $user_id);
    }
}

// Настройки шрифта
$current_font_size = $_SESSION['font_size'] ?? 'medium';
if ($is_logged_in && !isset($_SESSION['font_size'])) {
    $stmt = $pdo->prepare("SELECT font_size FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $font_size = $stmt->fetchColumn();
    if ($font_size) {
        $_SESSION['font_size'] = $font_size;
        $current_font_size = $font_size;
    }
}

// Обновление прогресса курса
function updateUserCourseProgress($user_id, $course_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(t.id) as total, COUNT(tp.id) as completed
        FROM topics t
        LEFT JOIN topic_progress tp ON t.id = tp.topic_id AND tp.user_id = ? AND tp.is_completed = 1
        WHERE t.course_id = ?
    ");
    $stmt->execute([$user_id, $course_id]);
    $result = $stmt->fetch();
    $total = $result['total'] ?: 1;
    $completed = $result['completed'] ?: 0;
    $percent = ($completed / $total) * 100;
    $stmt = $pdo->prepare("
        INSERT INTO user_progress (user_id, course_id, progress_percent, completed_topic)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        progress_percent = VALUES(progress_percent),
        completed_topic = VALUES(completed_topic)
    ");
    $stmt->execute([$user_id, $course_id, $percent, $completed]);
    return $percent;
}
// Фоновое изображение
$GLOBALS['global_background'] = 'url("../uploads/backgrounds/code-bg.jpg")';

// Функция получения доступных курсов для текущего пользователя
function getAvailableCourses($pdo, $user_id, $is_logged_in, $user_roles) {
    // Показываем только основные курсы (созданные NULL или is_official = 1, если есть)
    // По вашей БД – это курсы с created_by IS NULL
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE created_by IS NULL AND is_published = 1 ORDER BY id");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Функция проверки доступа к курсу
function canAccessCourse($pdo, $course_id, $user_id, $is_logged_in, $user_roles) {
    $is_admin = in_array('admin', $user_roles);
    if ($is_admin) return true;
    
    $stmt = $pdo->prepare("SELECT created_by, is_public FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    if (!$course) return false;
    
    // Публичный курс доступен всем
    if ($course['is_public']) return true;
    
    // Приватный курс доступен только автору
    if ($is_logged_in && $course['created_by'] == $user_id) return true;
    
    return false;
}

// ============================================================
// ЖЁСТКО ЗАДАННЫЕ ПРАВИЛЬНЫЕ ОТВЕТЫ ДЛЯ ВСЕХ ЗАДАНИЙ
// Ключ = id задания, значение = ожидаемый вывод (строка)
// ============================================================
$HARDCODED_EXPECTED_OUTPUTS = [
    1 => 'Привет, мир!',
    2 => '123',
    3 => '6',
    4 => '12',
    5 => '15',
    6 => '12',
    7 => '20',
    8 => '6',
    9 => '8',
    10 => '25',
    11 => '36',
    27 => '2.25',
    28 => '-100',
    29 => '-50',
    // PHP задания
    30 => 'Привет, мир!',
    31 => "int(12345)\nstring(5) \"Hello\"",
    32 => 'Готово',
    33 => 'Production mode',
    34 => 'Hello',
    35 => 'PHP',
    36 => '123',
    37 => "10\n20",
    38 => "12\n8\n20\n5",
    39 => '20',
    40 => '16',
    41 => '10',
    42 => '36',
    // Python задания
    44 => 'Иван',
    45 => '42',
    46 => '',
    47 => '',
    48 => 'abc',
    49 => '25',
    50 => 'Привет',
    51 => '100',
    52 => '6',
    53 => '2',
    54 => '21',
    55 => '2.6666666666666665',
    56 => '3',
    57 => '8',
    58 => '4',
    59 => '21',
    60 => '2.5',
    61 => '2',
    62 => '5',
    63 => '14',
    64 => '8.0',
    65 => '-13',
    66 => '3.0',
    67 => '9.0',
    68 => '6.0',
    69 => '20',
    70 => '1.3333333333333333',
    71 => '22',
    72 => '62',
];

function getUserRolesDisplay($roles) {
    if (empty($roles)) {
        return 'Студент';
    }
    $map = [
        'student' => 'Студент',
        'teacher' => 'Преподаватель',
        'admin'   => 'Администратор'
    ];
    $translated = array_map(function($role) use ($map) {
        return $map[$role] ?? $role;
    }, $roles);
    return implode(', ', $translated);
}

function getUserAvatarHtml($size = '32px') {
    global $_SESSION;
    $avatar = $_SESSION['avatar'] ?? null;
    $html = '';
    if (!empty($avatar)) {
        // Проверяем существование файла относительно корня
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $avatar;
        if (file_exists($filePath)) {
            $html = '<img src="' . htmlspecialchars($avatar) . '" style="width:' . $size . ';height:' . $size . ';border-radius:50%;object-fit:cover;margin-right:5px;">';
        }
    }
    if (empty($html)) {
        $html = '<i class="fas fa-user-circle" style="font-size:' . $size . '; margin-right:5px;"></i>';
    }
    return $html;
}
?>