<?php
require_once 'config/config.php';

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$course_id) die('Курс не указан');

$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();
if (!$course) die('Курс не найден');

if (!canManageCourse($pdo, $user_id, $course_id, $is_admin)) {
    header('Location: index.php');
    exit;
}

$topics = $pdo->prepare("SELECT * FROM topics WHERE course_id = ? ORDER BY id");
$topics->execute([$course_id]);
$topics = $topics->fetchAll();

if (isset($_GET['delete_topic'])) {
    $topic_id = (int)$_GET['delete_topic'];
    $stmt = $pdo->prepare("DELETE FROM topics WHERE id = ? AND course_id = ?");
    $stmt->execute([$topic_id, $course_id]);
    header("Location: manage_course.php?id=$course_id");
    exit;
}

$current_theme = $_SESSION['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление курсом: <?= htmlspecialchars($course['course_name']) ?> | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .manage-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-header h1 {
            font-size: 28px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-header h1 i {
            color: #3b82f6;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
        }
        .btn-secondary {
            background: #64748b !important;
        }
        .btn-secondary:hover {
            background: #475569 !important;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #1e293b;
        }
        .btn-small {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            margin-right: 8px;
            background: #3b82f6;
            color: white;
        }
        .btn-small:hover {
            background: #2563eb;
        }
        .btn-small.danger {
            background: #dc2626;
        }
        .btn-small.danger:hover {
            background: #b91c1c;
        }
        .empty-message {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 16px;
            color: #64748b;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            color: #3b82f6;
            text-decoration: none;
        }
        body.theme-dark table,
        body.theme-dark .empty-message {
            background: #1e293b;
        }
        body.theme-dark th {
            background: #0f172a;
            color: #e2e8f0;
        }
        body.theme-dark td {
            border-bottom-color: #334155;
            color: #cbd5e1;
        }
        body.theme-dark .page-header h1 {
            color: #e2e8f0;
        }
        .actions-cell {
            white-space: nowrap;
        }
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size) ?>">
    <div class="container">
        <a href="index.php" class="back-link">
            <i class="fas fa-home"></i> На главную
        </a>
        
        <div class="manage-container">
            <div class="page-header">
                <h1>
                    <i class="fas fa-cog"></i>
                    Управление курсом: <?= htmlspecialchars($course['course_name']) ?>
                </h1>
                <div class="action-buttons">
                    <a href="add_topic.php?course_id=<?= $course_id ?>" class="btn">
                        <i class="fas fa-plus"></i> Добавить тему
                    </a>
                    <a href="edit_course.php?id=<?= $course_id ?>" class="btn btn-secondary">
                        <i class="fas fa-edit"></i> Редактировать курс
                    </a>
                </div>
            </div>
            
            <h2 style="margin-bottom: 20px;"><i class="fas fa-list"></i> Темы курса</h2>
            
            <?php if (empty($topics)): ?>
                <div class="empty-message">
                    <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                    <p>Пока нет тем. Нажмите "Добавить тему", чтобы создать первую тему.</p>
                </div>
            <?php else: ?>
                 <table>
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Описание</th>
                            <th>Длительность (мин)</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($topics as $topic): ?>
                        <tr>
                            <td><?= htmlspecialchars($topic['topic_name']) ?></td>
                            <td><?= htmlspecialchars(mb_substr($topic['topic_description'], 0, 100)) ?>...</td>
                            <td><?= $topic['duration_minutes'] ?></td>
                            <td class="actions-cell">
                                <!-- Кнопка управления заданиями -->
                                <a href="edit_assignments.php?topic_id=<?= $topic['id'] ?>" class="btn-small">
                                    <i class="fas fa-tasks"></i> Задания
                                </a>
                                <!-- Кнопка редактирования темы -->
                                <a href="edit_topic.php?id=<?= $topic['id'] ?>" class="btn-small">
                                    <i class="fas fa-edit"></i> Ред.
                                </a>
                                <!-- Кнопка удаления темы -->
                                <a href="?id=<?= $course_id ?>&delete_topic=<?= $topic['id'] ?>" 
                                   onclick="return confirm('Удалить тему? Это действие нельзя отменить.')" 
                                   class="btn-small danger">
                                    <i class="fas fa-trash"></i> Удалить
                                </a>
                             </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                 </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>