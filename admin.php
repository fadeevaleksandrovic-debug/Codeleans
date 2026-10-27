<?php

// Подключаем конфиг (обязательно ДО использования $pdo)
require_once 'config/config.php';

// --- Проверка доступа ---
$is_admin_session = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_user_admin = false;

if (!$is_admin_session && isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT r.role_name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('admin', $roles)) {
        $is_user_admin = true;
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $_SESSION['user_id'];
        $_SESSION['admin_email'] = $_SESSION['user_email'] ?? '';
    }
}

if (!$is_admin_session && !$is_user_admin) {
    header('Location: admin_login.php');
    exit;
}
// --- Конец проверки ---

// --- Обработка действий ---
$action = $_GET['action'] ?? '';
$success = '';
$error = '';

// 1. Назначение преподавателя на курс
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_teacher'])) {
    $course_id = (int)$_POST['course_id'];
    $teacher_id = (int)$_POST['teacher_id'];
    if ($course_id && $teacher_id) {
        try {
            $stmt = $pdo->prepare("INSERT INTO course_teachers (course_id, teacher_id) VALUES (?, ?)");
            $stmt->execute([$course_id, $teacher_id]);
            $success = 'Преподаватель назначен на курс.';
        } catch (PDOException $e) {
            $error = 'Ошибка: возможно, назначение уже существует.';
        }
    } else {
        $error = 'Выберите курс и преподавателя.';
    }
}

// 2. Удаление назначения
if ($action === 'remove_assignment' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM course_teachers WHERE id = ?")->execute([$id]);
    $success = 'Назначение удалено.';
}

// 3. Удаление сообщения чата
if ($action === 'delete_chat' && isset($_GET['msg_id'])) {
    $msg_id = (int)$_GET['msg_id'];
    $pdo->prepare("DELETE FROM chat_messages WHERE id = ?")->execute([$msg_id]);
    $success = 'Сообщение удалено.';
}

// 4. Удаление темы
if ($action === 'delete_topic' && isset($_GET['topic_id'])) {
    $topic_id = (int)$_GET['topic_id'];
    $pdo->prepare("DELETE FROM topics WHERE id = ?")->execute([$topic_id]);
    $success = 'Тема удалена.';
}

// 5. Удаление курса
if ($action === 'delete_course' && isset($_GET['course_id'])) {
    $course_id = (int)$_GET['course_id'];
    $pdo->prepare("DELETE FROM courses WHERE id = ?")->execute([$course_id]);
    $success = 'Курс удалён (вместе со всеми темами и прогрессом).';
}

// --- Получение данных для отображения ---
$teachers = $pdo->query("
    SELECT u.* FROM users u 
    JOIN user_roles ur ON u.id = ur.user_id 
    JOIN roles r ON ur.role_id = r.id 
    WHERE r.role_name = 'teacher'
    ORDER BY u.full_name
")->fetchAll();

$courses = $pdo->query("SELECT * FROM courses ORDER BY course_name")->fetchAll();

$assignments = $pdo->query("
    SELECT ct.id, c.course_name, u.full_name as teacher_name 
    FROM course_teachers ct
    JOIN courses c ON ct.course_id = c.id
    JOIN users u ON ct.teacher_id = u.id
    ORDER BY c.course_name
")->fetchAll();

$chat_messages = $pdo->query("
    SELECT m.*, u.full_name 
    FROM chat_messages m
    JOIN users u ON m.user_id = u.id
    ORDER BY m.created_at DESC LIMIT 50
")->fetchAll();

$topics = $pdo->query("
    SELECT t.*, c.course_name 
    FROM topics t 
    JOIN courses c ON t.course_id = c.id
    ORDER BY c.course_name, t.id
")->fetchAll();

$stats = [];
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['total_teachers'] = $pdo->query("
    SELECT COUNT(*) FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE r.role_name = 'teacher'
")->fetchColumn();
$stats['total_courses'] = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$stats['total_topics'] = $pdo->query("SELECT COUNT(*) FROM topics")->fetchColumn();
$stats['total_completed_topics'] = $pdo->query("SELECT COUNT(*) FROM topic_progress WHERE is_completed = 1")->fetchColumn();
$stats['total_messages'] = $pdo->query("SELECT COUNT(*) FROM chat_messages")->fetchColumn();

$course_progress = $pdo->query("
    SELECT c.course_name, 
           COUNT(DISTINCT CASE WHEN up.progress_percent > 0 THEN up.user_id END) as students_started,
           AVG(up.progress_percent) as avg_progress
    FROM courses c
    LEFT JOIN user_progress up ON c.id = up.course_id
    GROUP BY c.id
    ORDER BY students_started DESC
")->fetchAll();

// Экспорт в Word
if ($action === 'export_doc') {
    // Функция для русского месяца
    function ruMonth($monthNum) {
        $months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
        return $months[(int)$monthNum - 1];
    }
    $dateStr = date('d') . ' ' . ruMonth(date('n')) . ' ' . date('Y');
    $timeStr = date('H:i');
    
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment; filename="report_' . date('Y-m-d') . '.doc"');
    echo '<html><head><meta charset="utf-8"><title>Отчёт по курсам CodeLeans</title>';
    echo '<style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            h1 { color: #1e293b; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; }
            .date { color: #64748b; margin-bottom: 20px; }
            .stats { margin: 20px 0; }
            .stats p { margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background-color: #3b82f6; color: white; padding: 10px; text-align: left; }
            td { padding: 10px; border: 1px solid #e2e8f0; }
            .footer { margin-top: 40px; color: #94a3b8; font-style: italic; }
          </style>';
    echo '</head><body>';
    echo '<h1>Отчёт по курсам CodeLeans</h1>';
    echo '<div class="date">Дата формирования: ' . $dateStr . ' в ' . $timeStr . '</div>';
    
    echo '<div class="stats">';
    echo '<p><strong>Общая статистика:</strong></p>';
    echo '<p>Всего пользователей: ' . $stats['total_users'] . '</p>';
    echo '<p>Преподавателей: ' . $stats['total_teachers'] . '</p>';
    echo '<p>Всего курсов: ' . $stats['total_courses'] . '</p>';
    echo '<p>Всего тем: ' . $stats['total_topics'] . '</p>';
    echo '<p>Пройденных тем: ' . $stats['total_completed_topics'] . '</p>';
    echo '<p>Сообщений в чатах: ' . $stats['total_messages'] . '</p>';
    echo '</div>';
    
    echo '<h2>Прогресс по курсам</h2>';
    if (empty($course_progress)) {
        echo '<p>Нет данных о прогрессе.</p>';
    } else {
        echo '<table>';
        echo '<tr><th>Курс</th><th>Студентов начали</th><th>Средний прогресс (%)</th></tr>';
        foreach ($course_progress as $row) {
            echo '<tr><td>' . htmlspecialchars($row['course_name']) . '</td><td>' . $row['students_started'] . '</td><td>' . round($row['avg_progress'], 1) . '</td></tr>';
        }
        echo '</table>';
    }
    
    echo '<div class="footer">С уважением, администрация CodeLeans</div>';
    echo '</body></html>';
    exit;
}

$current_theme = $_SESSION['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style/global.css">
    <style>
        .admin-header { background: linear-gradient(135deg, #1e293b, #0f172a); color: white; padding: 20px 0; }
        .admin-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; margin-bottom: 40px; }
        .admin-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .admin-card h2 { font-size: 24px; margin-bottom: 20px; border-bottom: 2px solid #3b82f6; display: inline-block; padding-bottom: 5px; }
        .form-group { margin-bottom: 15px; display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
        select, button { padding: 10px 16px; border-radius: 8px; border: 1px solid #e2e8f0; }
        button { background: #3b82f6; color: white; border: none; cursor: pointer; transition: 0.3s; }
        button:hover { background: #2563eb; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .delete-btn { background: #dc2626; color: white; padding: 4px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; display: inline-block; }
        .delete-btn:hover { background: #b91c1c; }
        .success { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 10px; margin-bottom: 20px; }
        .error { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 10px; margin-bottom: 20px; }
        .stats-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; }
        .stat-box { background: #f8fafc; padding: 20px; border-radius: 16px; flex: 1; text-align: center; }
        .stat-number { font-size: 36px; font-weight: 700; color: #3b82f6; }
        .export-btn { background: #10b981; margin-top: 20px; display: inline-block; padding: 10px 20px; border-radius: 8px; text-decoration: none; color: white; }
        .export-btn:hover { background: #059669; }
        body.theme-dark .admin-card, body.theme-dark .stat-box { background: #1e293b; color: #e2e8f0; }
        body.theme-dark table td, body.theme-dark table th { border-color: #334155; }
        body.theme-dark .admin-card h2 { color: #e2e8f0; }
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size ?? 'medium') ?>">
    <div class="admin-header">
        <div class="admin-container" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="logo" style="color:white;">
                <i class="fas fa-code"></i>
                <h1 style="color:white;">CodeLeans | Администратор</h1>
            </div>
            <div>
                <span><?= htmlspecialchars($_SESSION['admin_email']) ?></span>
                <a href="admin_logout.php" style="color:white; margin-left:20px;"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                <a href="index.php" style="color:white; margin-left:20px;"><i class="fas fa-home"></i> На сайт</a>
            </div>
        </div>
    </div>

    <div class="admin-container">
        <?php if ($success): ?>
            <div class="success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="admin-card">
            <h2><i class="fas fa-chalkboard-user"></i> Назначение преподавателей на курсы</h2>
            <form method="post">
                <div class="form-group">
                    <select name="course_id" required>
                        <option value="">Выберите курс</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="teacher_id" required>
                        <option value="">Выберите преподавателя</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="assign_teacher">Назначить</button>
                </div>
            </form>
            <h3>Текущие назначения</h3>
            <table>
                <thead><tr><th>Курс</th><th>Преподаватель</th><th>Действие</th></tr></thead>
                <tbody>
                    <?php foreach ($assignments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['course_name']) ?></td>
                            <td><?= htmlspecialchars($a['teacher_name']) ?></td>
                            <td><a href="?action=remove_assignment&id=<?= $a['id'] ?>" class="delete-btn" onclick="return confirm('Удалить назначение?')">Удалить</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-card">
            <h2><i class="fas fa-shield-alt"></i> Модерация контента</h2>
            <div style="margin-bottom: 30px;">
                <h3>Сообщения чата (последние 50)</h3>
                <table>
                    <thead><tr><th>Пользователь</th><th>Сообщение</th><th>Дата</th><th>Действие</th></tr></thead>
                    <tbody>
                        <?php foreach ($chat_messages as $msg): ?>
                            <tr>
                                <td><?= htmlspecialchars($msg['full_name']) ?></td>
                                <td><?= htmlspecialchars(mb_substr($msg['message'], 0, 80)) ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?></td>
                                <td><a href="?action=delete_chat&msg_id=<?= $msg['id'] ?>" class="delete-btn" onclick="return confirm('Удалить сообщение?')">Удалить</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div>
                <h3>Темы курсов</h3>
                <table>
                    <thead><tr><th>Курс</th><th>Тема</th><th>Действие</th></tr></thead>
                    <tbody>
                        <?php foreach ($topics as $topic): ?>
                            <tr>
                                <td><?= htmlspecialchars($topic['course_name']) ?></td>
                                <td><?= htmlspecialchars($topic['topic_name']) ?></td>
                                <td><a href="?action=delete_topic&topic_id=<?= $topic['id'] ?>" class="delete-btn" onclick="return confirm('Удалить тему и весь прогресс по ней?')">Удалить</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 20px;">
                <h3>Курсы (полное удаление)</h3>
                <table>
                    <thead><tr><th>Курс</th><th>Действие</th></tr></thead>
                    <tbody>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['course_name']) ?></td>
                                <td><a href="?action=delete_course&course_id=<?= $c['id'] ?>" class="delete-btn" onclick="return confirm('Удалить курс со всеми темами и прогрессом?')">Удалить курс</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-card">
            <h2><i class="fas fa-chart-line"></i> Отчёты и статистика</h2>
            <div class="stats-row">
                <div class="stat-box"><div class="stat-number"><?= $stats['total_users'] ?></div><div>Всего пользователей</div></div>
                <div class="stat-box"><div class="stat-number"><?= $stats['total_teachers'] ?></div><div>Преподавателей</div></div>
                <div class="stat-box"><div class="stat-number"><?= $stats['total_courses'] ?></div><div>Курсов</div></div>
                <div class="stat-box"><div class="stat-number"><?= $stats['total_topics'] ?></div><div>Тем</div></div>
                <div class="stat-box"><div class="stat-number"><?= $stats['total_completed_topics'] ?></div><div>Пройденных тем</div></div>
                <div class="stat-box"><div class="stat-number"><?= $stats['total_messages'] ?></div><div>Сообщений в чатах</div></div>
            </div>
            <div class="admin-card">
    <h2><i class="fas fa-headset"></i> Техподдержка</h2>
    <p>Просмотр и ответ на обращения пользователей.</p>
    <a href="admin_support.php" class="export-btn">
        <i class="fas fa-envelope"></i> Перейти к обращениям
    </a>
</div>
            <h3>Прогресс по курсам</h3>
            <table>
                <thead><tr><th>Курс</th><th>Студентов начали</th><th>Средний прогресс (%)</th></tr></thead>
                <tbody>
                    <?php foreach ($course_progress as $cp): ?>
                        <tr>
                            <td><?= htmlspecialchars($cp['course_name']) ?></td>
                            <td><?= $cp['students_started'] ?></td>
                            <td><?= round($cp['avg_progress'], 1) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="?action=export_doc" class="export-btn"><i class="fas fa-download"></i> Экспорт отчёта в Word</a>
        </div>
    </div>
</body>
</html>