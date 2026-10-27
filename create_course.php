<?php
require_once 'config/config.php';

if (!$is_logged_in || !$is_teacher) {
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
        $stmt = $pdo->prepare("INSERT INTO courses (course_name, course_description, total_topic, total_hours, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $total_topic, $total_hours, $user_id]);
        $course_id = $pdo->lastInsertId();

        // Создаём прогресс для всех пользователей
        $users_stmt = $pdo->query("SELECT id FROM users");
        $progress_stmt = $pdo->prepare("INSERT INTO user_progress (user_id, course_id, progress_percent, completed_topic) VALUES (?, ?, 0, 0)");
        while ($user = $users_stmt->fetch()) {
            $progress_stmt->execute([$user['id'], $course_id]);
        }

        header("Location: manage_course.php?id=$course_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание курса | CodeLeans</title>
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
        .form-container .subtitle {
            color: #64748b;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
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
            transition: all 0.3s;
        }
        .form-group input:focus, 
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
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
            transition: background 0.3s;
            width: 100%;
        }
        .btn-submit:hover {
            background: #2563eb;
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
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        @media (max-width: 640px) {
            .form-container {
                padding: 25px;
            }
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
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
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <!-- Содержимое такое же как в index.php (для краткости опустим, но вы можете скопировать) -->
                    </div>
                </div>
            </div>
        </header>

        <!-- Модальное окно О нас (как в index.php) -->
        <div class="modal" id="aboutModal">...</div>

        <main>
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> На главную
            </a>

            <div class="form-container">
                <h1><i class="fas fa-plus-circle"></i> Создать новый курс</h1>
                <div class="subtitle">Заполните информацию о курсе. После создания вы сможете добавлять темы.</div>

                <?php if ($error): ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label for="name">Название курса</label>
                        <input type="text" id="name" name="name" required placeholder="Например: Java, Python, JavaScript">
                    </div>
                    <div class="form-group">
                        <label for="description">Описание курса</label>
                        <textarea id="description" name="description" rows="4" required placeholder="Расскажите, чему научится студент..."></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="total_topic">Количество тем</label>
                            <input type="number" id="total_topic" name="total_topic" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="total_hours">Общее количество часов</label>
                            <input type="number" id="total_hours" name="total_hours" min="0" value="0" step="0.5">
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-check-circle"></i> Создать курс
                    </button>
                </form>
            </div>
        </main>

        <!-- Подвал -->
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
        // Скрипты для шапки и модального окна (скопируйте из index.php)
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