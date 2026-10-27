<?php
require_once 'config/config.php';

if (!$is_logged_in || !$is_teacher) {
    header('Location: index.php');
    exit;
}

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$course_id) {
    die('Курс не указан');
}

// Получаем информацию о курсе
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();
if (!$course) {
    die('Курс не найден');
}

// Проверка прав: пользователь должен быть создателем или администратором
if (!($user_id == $course['created_by'] || in_array('admin', $user_roles))) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $total_topic = (int)($_POST['total_topic'] ?? 0);
    $total_hours = (int)($_POST['total_hours'] ?? 0);

    if (empty($name)) {
        $error = 'Введите название курса';
    } elseif (empty($description)) {
        $error = 'Введите описание курса';
    } else {
        $stmt = $pdo->prepare("UPDATE courses SET course_name = ?, course_description = ?, total_topic = ?, total_hours = ? WHERE id = ?");
        $stmt->execute([$name, $description, $total_topic, $total_hours, $course_id]);
        $success = 'Курс успешно обновлён';
        // Обновим данные для отображения
        $course['course_name'] = $name;
        $course['course_description'] = $description;
        $course['total_topic'] = $total_topic;
        $course['total_hours'] = $total_hours;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование курса | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .form-container {
            max-width: 700px;
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-container h1 i {
            color: #3b82f6;
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
        .form-group input:focus, 
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
        .btn-submit:hover {
            background: #2563eb;
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
    <header class="header">
        <div class="logo"><i class="fas fa-code"></i><h1>CodeLeans</h1></div>
        <div class="header-actions">
            <button class="about-btn" id="aboutBtn"><i class="fas fa-info-circle"></i><span>О нас</span></button>
            <div class="profile-section">
                <button class="profile-btn" id="profileBtn"><i class="fas fa-user-circle"></i><span><?= htmlspecialchars($user_name) ?></span><i class="fas fa-chevron-down"></i></button>
                <div class="profile-dropdown" id="profileDropdown"><!-- содержимое как в index.php --></div>
            </div>
        </div>
    </header>
    <div class="modal" id="aboutModal">...</div>

    <main>
        <a href="manage_course.php?id=<?= $course_id ?>" class="back-link"><i class="fas fa-arrow-left"></i> Назад к управлению</a>
        <div class="form-container">
            <h1><i class="fas fa-edit"></i> Редактирование курса</h1>
            <?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Название курса</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($course['course_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" rows="4" required><?= htmlspecialchars($course['course_description']) ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Количество тем</label>
                        <input type="number" name="total_topic" value="<?= $course['total_topic'] ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label>Часов</label>
                        <input type="number" name="total_hours" value="<?= $course['total_hours'] ?>" min="0" step="0.5">
                    </div>
                </div>
                <button type="submit" class="btn-submit">Сохранить изменения</button>
            </form>
        </div>
    </main>

    <footer class="footer"><!-- как в других файлах --></footer>
</div>
<script>
    // Скопировать скрипты для профиля и модалки из index.php
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