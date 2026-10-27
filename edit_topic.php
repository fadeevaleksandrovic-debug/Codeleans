<?php
require_once 'config/config.php';

if (!$is_logged_in || !$is_teacher) {
    header('Location: index.php');
    exit;
}

$topic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$topic_id) {
    die('Тема не указана');
}

// Получаем тему и проверяем права через курс
$stmt = $pdo->prepare("SELECT t.*, c.created_by, c.course_name FROM topics t JOIN courses c ON t.course_id = c.id WHERE t.id = ?");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch();
if (!$topic) {
    die('Тема не найдена');
}

$course_id = $topic['course_id'];
if (!($user_id == $topic['created_by'] || in_array('admin', $user_roles))) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration = (int)($_POST['duration_minutes'] ?? 0);
    $content = $_POST['content'] ?? '';

    if (empty($name)) {
        $error = 'Введите название темы';
    } elseif (empty($description)) {
        $error = 'Введите описание темы';
    } elseif ($duration <= 0) {
        $error = 'Длительность должна быть положительным числом';
    } else {
        $stmt = $pdo->prepare("UPDATE topics SET topic_name = ?, topic_description = ?, duration_minutes = ?, content = ? WHERE id = ?");
        $stmt->execute([$name, $description, $duration, $content, $topic_id]);
        $success = 'Тема успешно обновлена';
        // Обновим локальные данные
        $topic['topic_name'] = $name;
        $topic['topic_description'] = $description;
        $topic['duration_minutes'] = $duration;
        $topic['content'] = $content;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование темы | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .form-container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .form-container h1 {
            font-size: 28px;
            color: #1e293b;
            margin-bottom: 10px;
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
        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
        }
        .form-group textarea {
            font-family: 'JetBrains Mono', monospace;
        }
        .btn-submit {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
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
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size) ?>">
<div class="container">
    <header class="header"><!-- как в edit_course.php --></header>
    <div class="modal" id="aboutModal">...</div>
    <main>
        <a href="manage_course.php?id=<?= $course_id ?>" class="back-link"><i class="fas fa-arrow-left"></i> Назад к управлению курсом</a>
        <div class="form-container">
            <h1><i class="fas fa-edit"></i> Редактирование темы: <?= htmlspecialchars($topic['topic_name']) ?></h1>
            <?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Название темы</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($topic['topic_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" rows="3" required><?= htmlspecialchars($topic['topic_description']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Длительность (минуты)</label>
                    <input type="number" name="duration_minutes" value="<?= $topic['duration_minutes'] ?>" min="1" required>
                </div>
                <div class="form-group">
                    <label>Содержание (HTML/текст)</label>
                    <textarea name="content" rows="15" placeholder="Введите учебный материал. Можно использовать HTML теги."><?= htmlspecialchars($topic['content']) ?></textarea>
                </div>
                <button type="submit" class="btn-submit">Сохранить изменения</button>
            </form>
        </div>
    </main>
    <footer class="footer"></footer>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const profileBtn = document.getElementById('profileBtn');
        const profileDropdown = document.getElementById('profileDropdown');
        if (profileBtn && profileDropdown) {
            profileBtn.addEventListener('click', function(e) { e.stopPropagation(); profileDropdown.classList.toggle('show'); });
            document.addEventListener('click', function() { profileDropdown.classList.remove('show'); });
            profileDropdown.addEventListener('click', function(e) { e.stopPropagation(); });
        }
        const aboutBtn = document.getElementById('aboutBtn');
        const aboutModal = document.getElementById('aboutModal');
        const closeModal = document.getElementById('closeModal');
        if (aboutBtn && aboutModal && closeModal) {
            aboutBtn.addEventListener('click', function() { aboutModal.classList.add('show'); document.body.style.overflow = 'hidden'; });
            function closeAboutModal() { aboutModal.classList.remove('show'); document.body.style.overflow = ''; }
            closeModal.addEventListener('click', closeAboutModal);
            aboutModal.addEventListener('click', function(e) { if (e.target === aboutModal) closeAboutModal(); });
            document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && aboutModal.classList.contains('show')) closeAboutModal(); });
        }
    });
</script>
</body>
</html>