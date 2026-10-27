<?php
require_once 'config/config.php';

if (!$is_logged_in || !$is_teacher) {
    header('Location: index.php');
    exit;
}

// Получаем курсы, где преподаватель закреплён
$stmt = $pdo->prepare("
    SELECT c.* 
    FROM courses c
    JOIN course_teachers ct ON c.id = ct.course_id
    WHERE ct.teacher_id = ?
    ORDER BY c.course_name
");
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();

$selected_course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$topics = [];
$assignments = [];
$submissions = [];

if ($selected_course_id) {
    // Проверяем, что преподаватель действительно закреплён за этим курсом
    $check = $pdo->prepare("SELECT 1 FROM course_teachers WHERE course_id = ? AND teacher_id = ?");
    $check->execute([$selected_course_id, $user_id]);
    if (!$check->fetchColumn()) {
        $selected_course_id = 0;
    } else {
        // Получаем темы курса
        $stmt = $pdo->prepare("SELECT * FROM topics WHERE course_id = ? ORDER BY id");
        $stmt->execute([$selected_course_id]);
        $topics = $stmt->fetchAll();

        // Для каждой темы получаем задания
        foreach ($topics as &$topic) {
            $stmt = $pdo->prepare("SELECT * FROM assignments WHERE topic_id = ? ORDER BY order_num");
            $stmt->execute([$topic['id']]);
            $topic['assignments'] = $stmt->fetchAll();
        }
        unset($topic);

        // Если выбрано конкретное задание для просмотра, получим решения
        $selected_assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
        if ($selected_assignment_id) {
            $stmt = $pdo->prepare("
                SELECT ap.*, u.full_name, u.id as user_id
                FROM assignment_progress ap
                JOIN users u ON ap.user_id = u.id
                WHERE ap.assignment_id = ?
                ORDER BY ap.last_submitted_at DESC
            ");
            $stmt->execute([$selected_assignment_id]);
            $submissions = $stmt->fetchAll();
        }
    }
}

$current_theme = $_SESSION['theme'] ?? 'light';
$current_font_size = $_SESSION['font_size'] ?? 'medium';
$avatar = $_SESSION['avatar'] ?? null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Контроль заданий | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 30px; }
        .logo { display: flex; align-items: center; gap: 10px; }
        .logo i { font-size: 28px; color: #3b82f6; }
        .logo h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; margin: 20px 0; color: #3b82f6; text-decoration: none; font-weight: 500; }
        .course-selector { background: white; border-radius: 16px; padding: 20px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .course-selector select { padding: 10px 16px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 16px; min-width: 250px; }
        .course-selector button { background: #3b82f6; color: white; border: none; padding: 10px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .topic-card { background: white; border-radius: 16px; padding: 20px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .topic-card h3 { margin-top: 0; color: #1e293b; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; display: inline-block; }
        .assignment-item { border: 1px solid #e2e8f0; border-radius: 10px; padding: 15px; margin: 15px 0; }
        .assignment-item h4 { margin: 0 0 10px 0; color: #1e293b; }
        .submission-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .submission-table th { background: #f8fafc; text-align: left; padding: 10px; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
        .submission-table td { padding: 10px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        .submission-table .status-correct { color: #10b981; font-weight: 600; }
        .submission-table .status-wrong { color: #ef4444; font-weight: 600; }
        .submission-table .code-preview { font-family: 'JetBrains Mono', monospace; font-size: 13px; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; background: #f1f5f9; padding: 4px 8px; border-radius: 4px; }
        .btn-small { display: inline-block; padding: 4px 12px; background: #3b82f6; color: white; border-radius: 6px; text-decoration: none; font-size: 13px; }
        .btn-small:hover { background: #2563eb; }
        .footer { padding: 40px 0; border-top: 1px solid #e2e8f0; text-align: center; margin-top: 60px; }
        body.theme-dark .course-selector,
        body.theme-dark .topic-card,
        body.theme-dark .assignment-item { background: #1e293b; border-color: #334155; }
        body.theme-dark .topic-card h3 { color: #e2e8f0; }
        body.theme-dark .assignment-item h4 { color: #e2e8f0; }
        body.theme-dark .submission-table th { background: #0f172a; color: #e2e8f0; border-color: #334155; }
        body.theme-dark .submission-table td { border-color: #334155; color: #cbd5e1; }
        body.theme-dark .submission-table .code-preview { background: #334155; color: #e2e8f0; }
        body.theme-dark .logo h1 { color: #e2e8f0; }
        .no-data { text-align: center; padding: 30px; color: #64748b; }
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size) ?>">
<div class="container">
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
            <div class="profile-dropdown" id="profileDropdown">
                <div class="user-info">
                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                    <div class="user-details">
                        <h3><?= htmlspecialchars($user_name) ?></h3>
                        <p><?= htmlspecialchars($user_email) ?></p>
                        <p><small>Роль: <?php echo getUserRolesDisplay($user_roles); ?></small></p>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="profile.php" class="dropdown-item"><i class="fas fa-user-cog"></i> Мой профиль</a>
                <a href="settings.php" class="dropdown-item"><i class="fas fa-sliders-h"></i> Настройки</a>
                <a href="create_course.php" class="dropdown-item"><i class="fas fa-plus-circle"></i> Создать курс</a>
                <a href="teacher_assignments.php" class="dropdown-item"><i class="fas fa-clipboard-list"></i> Проверка заданий</a>
                <a href="logout.php" class="dropdown-item logout"><i class="fas fa-sign-out-alt"></i> Выйти</a>
            </div>
        </div>
    </header>

    <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> На главную</a>

    <h1><i class="fas fa-clipboard-list"></i> Контроль выполнения заданий</h1>

    <div class="course-selector">
        <form method="get" action="">
            <label for="course_id">Выберите курс:</label>
            <select name="course_id" id="course_id" onchange="this.form.submit()">
                <option value="">-- Выберите курс --</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $selected_course_id == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['course_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit">Показать</button></noscript>
        </form>
    </div>

    <?php if ($selected_course_id && empty($topics)): ?>
        <div class="no-data">В этом курсе пока нет тем.</div>
    <?php endif; ?>

    <?php foreach ($topics as $topic): ?>
        <div class="topic-card">
            <h3><?= htmlspecialchars($topic['topic_name']) ?></h3>
            <p style="color: #64748b; margin-bottom: 15px;"><?= htmlspecialchars($topic['topic_description']) ?></p>
            
            <?php if (empty($topic['assignments'])): ?>
                <p style="color: #94a3b8;">Заданий в этой теме нет.</p>
            <?php else: ?>
                <?php foreach ($topic['assignments'] as $assignment): ?>
                    <div class="assignment-item">
                        <h4>
                            <i class="fas fa-tasks"></i> 
                            <?= htmlspecialchars($assignment['title']) ?>
                            <span style="font-weight: normal; font-size: 14px; color: #64748b; margin-left: 10px;">
                                (язык: <?= strtoupper($assignment['language']) ?>)
                            </span>
                        </h4>
                        <p style="color: #64748b; font-size: 14px; margin-bottom: 10px;">
                            <?= htmlspecialchars(strip_tags(mb_substr($assignment['description'], 0, 150))) ?>...
                        </p>
                        <?php
                        // Получаем решения для этого задания
                        $stmt = $pdo->prepare("
                            SELECT ap.*, u.full_name, u.id as user_id
                            FROM assignment_progress ap
                            JOIN users u ON ap.user_id = u.id
                            WHERE ap.assignment_id = ?
                            ORDER BY ap.last_submitted_at DESC
                        ");
                        $stmt->execute([$assignment['id']]);
                        $subs = $stmt->fetchAll();
                        ?>
                        <?php if (empty($subs)): ?>
                            <p style="color: #94a3b8;">Нет выполненных попыток.</p>
                        <?php else: ?>
                            <table class="submission-table">
                                <thead>
                                    <tr>
                                        <th>Студент</th>
                                        <th>Код (фрагмент)</th>
                                        <th>Статус</th>
                                        <th>Попыток</th>
                                        <th>Дата</th>
                                        <th>Действие</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subs as $s): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
                                            <td><div class="code-preview"><?= htmlspecialchars(mb_substr($s['user_code'], 0, 80)) ?>…</div></td>
                                            <td class="<?= $s['is_correct'] ? 'status-correct' : 'status-wrong' ?>">
                                                <?= $s['is_correct'] ? '✅ Выполнено' : '❌ Ошибка' ?>
                                            </td>
                                            <td><?= $s['attempts'] ?></td>
                                            <td><?= date('d.m.Y H:i', strtotime($s['last_submitted_at'])) ?></td>
                                            <td>
                                                <a href="view_submission.php?assignment_id=<?= $assignment['id'] ?>&user_id=<?= $s['user_id'] ?>" target="_blank" class="btn-small">
                                                    <i class="fas fa-eye"></i> Просмотр
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

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
    });
</script>
</body>
</html>