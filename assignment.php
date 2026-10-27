<?php
require_once 'config/config.php';

// Обработка AJAX-запросов должна быть ДО любых редиректов
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    // Проверка авторизации для AJAX
    if (!$is_logged_in) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Не авторизован']);
        exit;
    }
    
    $assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$assignment_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Задание не указано']);
        exit;
    }
    
    // Получаем задание
    $stmt = $pdo->prepare("SELECT expected_output FROM assignments WHERE id = ?");
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch();
    if (!$assignment) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Задание не найдено']);
        exit;
    }
    
    $submitted_code = $_POST['code'] ?? '';
    $output = $_POST['output'] ?? '';
    $expected = trim($assignment['expected_output']);
    
    $normalized_output = normalizeJsOutput($output);
    $normalized_expected = normalizeJsOutput($expected);
    
    $is_correct = ($normalized_output === $normalized_expected);
    $feedback = $is_correct 
        ? "✅ Задание выполнено верно!\n\nВаш вывод:\n" . $output
        : "❌ Неправильный результат\n\nОжидалось:\n{$expected}\n\nПолучено:\n{$output}";
    
    // Сохраняем результат
    $stmt = $pdo->prepare("
        INSERT INTO assignment_submissions (user_id, assignment_id, user_code, is_correct, feedback) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            user_code = VALUES(user_code), 
            is_correct = VALUES(is_correct), 
            feedback = VALUES(feedback),
            submitted_at = NOW()
    ");
    $stmt->execute([$user_id, $assignment_id, $submitted_code, $is_correct ? 1 : 0, $feedback]);
    
    // Если задание выполнено, обновляем прогресс курса
    if ($is_correct) {
        $stmt = $pdo->prepare("SELECT t.course_id FROM topics t JOIN assignments a ON a.topic_id = t.id WHERE a.id = ?");
        $stmt->execute([$assignment_id]);
        $course_id = $stmt->fetchColumn();
        if ($course_id) {
            updateUserCourseProgress($user_id, $course_id);
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $is_correct, 'message' => $feedback]);
    exit;
}

// Далее обычная проверка для не-AJAX запросов
if (!$is_logged_in) {
    header('Location: login.php');
    exit;
}

$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$assignment_id) {
    die('Задание не указано');
}

// Получаем задание и информацию о теме/курсе
$stmt = $pdo->prepare("
    SELECT a.*, t.topic_name, t.course_id, c.course_name 
    FROM assignments a
    JOIN topics t ON a.topic_id = t.id
    JOIN courses c ON t.course_id = c.id
    WHERE a.id = ?
");
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch();

if (!$assignment) {
    die('Задание не найдено');
}

// Проверяем, выполнил ли пользователь это задание ранее
$stmt = $pdo->prepare("SELECT * FROM assignment_submissions WHERE user_id = ? AND assignment_id = ?");
$stmt->execute([$user_id, $assignment_id]);
$submission = $stmt->fetch();

$user_code = $submission ? $submission['user_code'] : $assignment['starter_code'];
$is_completed = $submission && $submission['is_correct'];

// Функция нормализации вывода
function normalizeJsOutput($str) {
    $str = trim($str);
    $str = preg_replace('/\s+/', ' ', $str);
    return $str;
}

$current_theme = $_SESSION['theme'] ?? 'light';
$current_font_size = $_SESSION['font_size'] ?? 'medium';
$avatar = $_SESSION['avatar'] ?? null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Задание: <?= htmlspecialchars($assignment['title']) ?> | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/global.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        
        body.theme-dark {
            background-color: #0f172a;
            color: #e2e8f0;
        }
        
        body.theme-dark .header,
        body.theme-dark .footer,
        body.theme-dark .assignment-description,
        body.theme-dark .code-editor-container {
            background-color: #1e293b;
            border-color: #334155;
        }
        
        body.theme-dark .profile-btn,
        body.theme-dark .about-btn {
            background-color: #334155;
            color: #e2e8f0;
        }
        
        body.font-small { font-size: 14px; }
        body.font-medium { font-size: 16px; }
        body.font-large { font-size: 18px; }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Шапка */
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
        
        body.theme-dark .logo h1 {
            color: #e2e8f0;
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
        
        body.theme-dark .profile-dropdown {
            background-color: #1e293b;
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
        
        body.theme-dark .user-details h3 {
            color: #e2e8f0;
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
        
        body.theme-dark .dropdown-item:hover {
            background-color: #334155;
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
        
        body.theme-dark .modal-content {
            background-color: #1e293b;
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
        
        body.theme-dark .modal-header h2 {
            color: #e2e8f0;
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
        
        body.theme-dark .about-text {
            color: #94a3b8;
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
        
        body.theme-dark .about-text .highlight {
            background-color: #334155;
            color: #e2e8f0;
        }
        
        .about-text .divider {
            width: 50px;
            height: 2px;
            background: linear-gradient(to right, transparent, #3b82f6, transparent);
            margin: 20px auto;
        }
        
        /* Стили задания */
        .assignment-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .assignment-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        .assignment-description {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .assignment-description h1 {
            font-size: 28px;
            margin-bottom: 20px;
            color: #1e293b;
        }
        
        body.theme-dark .assignment-description h1 {
            color: #e2e8f0;
        }
        
        .assignment-description h3 {
            margin: 20px 0 10px 0;
            color: #3b82f6;
        }
        
        .assignment-description p {
            margin: 10px 0;
            line-height: 1.6;
        }
        
        .assignment-description ul {
            margin: 10px 0 10px 20px;
        }
        
        .assignment-description li {
            margin: 5px 0;
        }
        
        .assignment-description code {
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 14px;
        }
        
        body.theme-dark .assignment-description code {
            background: #334155;
            color: #e2e8f0;
        }
        
        .code-editor-container {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .code-editor-container h3 {
            margin-bottom: 15px;
            color: #1e293b;
        }
        
        body.theme-dark .code-editor-container h3 {
            color: #e2e8f0;
        }
        
        .CodeMirror {
            height: 350px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 14px;
        }
        
        .output-area {
            margin-top: 20px;
            background-color: #1e1e1e;
            border-radius: 8px;
            padding: 15px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 14px;
        }
        
        .output-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: #94a3b8;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .output-label i {
            color: #10b981;
        }
        
        .output-content {
            background-color: #0f0f0f;
            border-radius: 6px;
            padding: 12px;
            color: #e2e8f0;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 150px;
            overflow-y: auto;
        }
        
        .output-content.empty {
            color: #64748b;
            font-style: italic;
        }
        
        .result-area {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
            white-space: pre-line;
            font-family: 'JetBrains Mono', monospace;
            font-size: 14px;
            line-height: 1.5;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .result-area.success {
            display: block;
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .result-area.error {
            display: block;
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        
        .btn-submit, .btn-run {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 15px;
            margin-right: 10px;
        }
        
        .btn-submit:hover, .btn-run:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .btn-run {
            background: #10b981;
        }
        
        .btn-run:hover {
            background: #059669;
        }
        
        .btn-submit:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
        }
        
        .completed-badge {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 15px;
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
        
        /* Подвал */
        .footer {
            padding: 40px 0;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            margin-top: 60px;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 20px;
            margin-bottom: 15px;
        }
        
        .footer-logo i {
            color: #3b82f6;
        }
        
        .footer-info p {
            margin: 5px 0;
            color: #64748b;
            font-size: 14px;
        }
        
        body.theme-dark .footer-info p {
            color: #94a3b8;
        }
        
        @media (max-width: 768px) {
            .assignment-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="theme-<?= htmlspecialchars($current_theme) ?> font-<?= htmlspecialchars($current_font_size) ?>">
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
            <div class="profile-section">
                <button class="profile-btn" id="profileBtn">
                    <?php if ($avatar && file_exists($avatar)): ?>
                        <img src="<?= htmlspecialchars($avatar) ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;margin-right:5px;">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($user_name); ?></span>
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
                            <h3><?php echo htmlspecialchars($user_name); ?></h3>
                            <p><?php echo htmlspecialchars($user_email); ?></p>
                            <p><small>Роль: <?php echo getUserRolesDisplay($user_roles); ?></small></p>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
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
    <div class="assignment-container">
        <a href="topic.php?id=<?= $assignment['topic_id'] ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Вернуться к теме
        </a>
        
        <?php if ($is_completed): ?>
            <div class="completed-badge">
                <i class="fas fa-check-circle"></i> Задание выполнено!
            </div>
        <?php endif; ?>
        
        <div class="assignment-layout">
            <div class="assignment-description">
                <h1><?= htmlspecialchars($assignment['title']) ?></h1>
                <?= $assignment['description'] ?>
            </div>
            
            <div class="code-editor-container">
                <h3><i class="fas fa-code"></i> Редактор кода</h3>
                
                <textarea id="codeEditor" name="code"><?= htmlspecialchars($user_code) ?></textarea>
                
                <div class="output-area">
                    <div class="output-label">
                        <i class="fas fa-terminal"></i>
                        <span>Результат выполнения</span>
                    </div>
                    <div class="output-content" id="outputContent">
                        <span style="color: #64748b;">Нажмите «Запустить код»</span>
                    </div>
                </div>
                
                <div id="result" class="result-area"></div>
                
                <div>
                    <button class="btn-run" id="runBtn">
                        <i class="fas fa-play"></i> Запустить код
                    </button>
                    <button class="btn-submit" id="submitBtn" <?= $is_completed ? 'disabled' : '' ?>>
                        <i class="fas fa-check-circle"></i> <?= $is_completed ? 'Задание выполнено' : 'Проверить задание' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

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
    // Инициализация CodeMirror
    var editor = CodeMirror.fromTextArea(document.getElementById('codeEditor'), {
        lineNumbers: true,
        mode: 'javascript',
        theme: 'dracula',
        indentUnit: 4,
        tabSize: 4,
        lineWrapping: true
    });
    
    // Функция выполнения JavaScript кода в iframe и получения вывода
   function runJavaScript(code, callback) {
    try {
        // Создаём функцию, которая возвращает собранный вывод
        const wrapped = `
            let __output = [];
            const __log = console.log;
            console.log = function(...args) {
                __output.push(args.map(arg => String(arg)).join(' '));
                __log.apply(console, args);
            };
            try {
                ${code}
            } catch(e) {
                __output.push('Error: ' + e.message);
            }
            console.log = __log;
            return __output.join('\\n');
        `;
        const func = new Function(wrapped);
        const result = func();
        callback(null, result);
    } catch (e) {
        callback(e.message, '');
    }
}
    
    // Запуск кода и отображение вывода
    document.getElementById('runBtn').addEventListener('click', function() {
        const code = editor.getValue();
        const outputDiv = document.getElementById('outputContent');
        outputDiv.innerHTML = '<span style="color: #f59e0b;">Выполнение...</span>';
        
        runJavaScript(code, (err, out) => {
            if (err) {
                outputDiv.innerHTML = `<span style="color: #ef4444;">❌ Ошибка: ${err}</span>`;
            } else {
                outputDiv.innerHTML = out || '<span style="color: #64748b;">[Нет вывода]</span>';
            }
        });
    });
    
    // Проверка задания: выполнить код, отправить вывод на сервер
    document.getElementById('submitBtn').addEventListener('click', function() {
        const code = editor.getValue();
        if (!code.trim()) {
            alert('Напишите код перед проверкой');
            return;
        }
        
        const submitBtn = this;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Проверка...';
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '';
        
        runJavaScript(code, (err, output) => {
            if (err) {
                resultDiv.innerHTML = `<div class="error">❌ Ошибка выполнения: ${err}</div>`;
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Проверить задание';
                return;
            }
            
            // Отправка вывода на сервер через AJAX
            const formData = new FormData();
            formData.append('code', code);
            formData.append('output', output);
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `<div class="success">${data.message.replace(/\n/g, '<br>')}</div>`;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Задание выполнено';
                    // Обновить страницу через 1 секунду, чтобы показать обновлённый статус
                    setTimeout(() => location.reload(), 1000);
                } else {
                    resultDiv.innerHTML = `<div class="error">${data.message.replace(/\n/g, '<br>')}</div>`;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Проверить задание';
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                resultDiv.innerHTML = '<div class="error">❌ Ошибка связи с сервером. Попробуйте ещё раз.</div>';
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Проверить задание';
            });
        });
    });
    
    // Выпадающее меню профиля
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
        
        // Автозапуск кода при загрузке (если есть код)
        setTimeout(function() {
            if (editor.getValue().trim().length > 0) {
                document.getElementById('runBtn').click();
            }
        }, 500);
    });
</script>
</body>
</html>