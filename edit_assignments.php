<?php
require_once 'config/config.php';

if (!$is_logged_in || !$is_teacher) {
    header('Location: index.php');
    exit;
}

$topic_id = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;
if (!$topic_id) {
    die('Тема не указана');
}

// Проверяем права на тему
$stmt = $pdo->prepare("SELECT t.*, c.created_by FROM topics t JOIN courses c ON t.course_id = c.id WHERE t.id = ?");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch();
if (!$topic || !($user_id == $topic['created_by'] || in_array('admin', $user_roles))) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Обработка создания/редактирования задания
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignment_id = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
    $title = trim($_POST['title'] ?? '');
    $description = $_POST['description'] ?? '';
    $language = $_POST['language'] ?? 'javascript';
    $starter_code = $_POST['starter_code'] ?? '';
    $solution_code = $_POST['solution_code'] ?? '';
    $expected_output = trim($_POST['expected_output'] ?? '');
    $hints = $_POST['hints'] ?? '';
    $order_num = (int)($_POST['order_num'] ?? 1);
    
    if (empty($title)) {
        $error = 'Введите название задания';
    } elseif (empty($expected_output)) {
        $error = 'Введите ожидаемый результат';
    } else {
        if ($assignment_id) {
            // Обновляем существующее задание
            $stmt = $pdo->prepare("UPDATE assignments SET title = ?, description = ?, language = ?, starter_code = ?, solution_code = ?, expected_output = ?, hints = ?, order_num = ? WHERE id = ? AND topic_id = ?");
            $stmt->execute([$title, $description, $language, $starter_code, $solution_code, $expected_output, $hints, $order_num, $assignment_id, $topic_id]);
            $success = 'Задание обновлено';
        } else {
            // Создаем новое задание
            $stmt = $pdo->prepare("INSERT INTO assignments (topic_id, title, description, language, starter_code, solution_code, expected_output, hints, order_num) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$topic_id, $title, $description, $language, $starter_code, $solution_code, $expected_output, $hints, $order_num]);
            $success = 'Задание создано';
        }
    }
}

// Удаление задания
if (isset($_GET['delete'])) {
    $assignment_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ? AND topic_id = ?");
    $stmt->execute([$assignment_id, $topic_id]);
    header("Location: edit_assignments.php?topic_id=$topic_id");
    exit;
}

// Получаем все задания темы
$assignments = $pdo->prepare("SELECT * FROM assignments WHERE topic_id = ? ORDER BY order_num");
$assignments->execute([$topic_id]);
$assignments = $assignments->fetchAll();

// Редактируемое задание
$edit_assignment = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    foreach ($assignments as $a) {
        if ($a['id'] == $edit_id) {
            $edit_assignment = $a;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление заданиями | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>
    <style>
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .form-card { background: white; border-radius: 16px; padding: 25px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #1e293b; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; }
        .CodeMirror { height: 300px; border: 1px solid #e2e8f0; border-radius: 8px; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-secondary { background: #64748b; color: white; }
        .assignments-list { margin-top: 30px; }
        .assignment-item { background: #f8fafc; padding: 15px; border-radius: 10px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        body.theme-dark .form-card { background: #1e293b; }
        body.theme-dark .form-group label { color: #e2e8f0; }
        body.theme-dark .assignment-item { background: #334155; }
    </style>
</head>
<body class="<?= getBodyThemeClass() ?>">
    <div class="container">
        <header class="header">
            <div class="logo"><i class="fas fa-code"></i><h1>CodeLeans</h1></div>
            <a href="manage_course.php?id=<?= $topic['course_id'] ?>" class="back-link"><i class="fas fa-arrow-left"></i> Назад</a>
        </header>

        <h1><i class="fas fa-tasks"></i> Управление заданиями: <?= htmlspecialchars($topic['topic_name']) ?></h1>

        <div class="two-columns">
            <!-- Форма создания/редактирования задания -->
            <div class="form-card">
                <h2><?= $edit_assignment ? 'Редактировать задание' : 'Создать новое задание' ?></h2>
                
                <?php if ($error): ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success-message"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <?php if ($edit_assignment): ?>
                        <input type="hidden" name="assignment_id" value="<?= $edit_assignment['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Название задания</label>
                        <input type="text" name="title" required value="<?= $edit_assignment ? htmlspecialchars($edit_assignment['title']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Описание задания (HTML разрешен)</label>
                        <textarea name="description" rows="5"><?= $edit_assignment ? htmlspecialchars($edit_assignment['description']) : '' ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Язык программирования</label>
                        <select name="language" id="languageSelect">
                            <option value="javascript" <?= $edit_assignment && $edit_assignment['language'] == 'javascript' ? 'selected' : '' ?>>JavaScript</option>
                            <option value="php" <?= $edit_assignment && $edit_assignment['language'] == 'php' ? 'selected' : '' ?>>PHP</option>
                            <option value="python" <?= $edit_assignment && $edit_assignment['language'] == 'python' ? 'selected' : '' ?>>Python</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Стартовый код (шаблон)</label>
                        <textarea id="starterCode" name="starter_code"><?= $edit_assignment ? htmlspecialchars($edit_assignment['starter_code']) : '// Напишите свой код здесь' ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Решение (правильный код)</label>
                        <textarea id="solutionCode" name="solution_code"><?= $edit_assignment ? htmlspecialchars($edit_assignment['solution_code']) : '' ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Ожидаемый результат</label>
                        <textarea name="expected_output" rows="3" required><?= $edit_assignment ? htmlspecialchars($edit_assignment['expected_output']) : '' ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Подсказки (через |)</label>
                        <textarea name="hints" rows="2" placeholder="Подсказка 1 | Подсказка 2 | Подсказка 3"><?= $edit_assignment ? htmlspecialchars($edit_assignment['hints']) : '' ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Порядковый номер</label>
                        <input type="number" name="order_num" min="1" value="<?= $edit_assignment ? $edit_assignment['order_num'] : count($assignments) + 1 ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= $edit_assignment ? 'Сохранить изменения' : 'Создать задание' ?>
                    </button>
                    
                    <?php if ($edit_assignment): ?>
                        <a href="?topic_id=<?= $topic_id ?>" class="btn btn-secondary">Отменить</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Список заданий -->
            <div class="form-card">
                <h2>Список заданий</h2>
                <?php if (empty($assignments)): ?>
                    <p>Пока нет заданий. Создайте первое задание!</p>
                <?php else: ?>
                    <div class="assignments-list">
                        <?php foreach ($assignments as $a): ?>
                            <div class="assignment-item">
                                <div>
                                    <strong>#<?= $a['order_num'] ?></strong> 
                                    <?= htmlspecialchars($a['title']) ?>
                                    <span class="badge"><?= $a['language'] ?></span>
                                </div>
                                <div>
                                    <a href="?topic_id=<?= $topic_id ?>&edit=<?= $a['id'] ?>" class="btn-small">✏️</a>
                                    <a href="?topic_id=<?= $topic_id ?>&delete=<?= $a['id'] ?>" class="btn-small" onclick="return confirm('Удалить задание?')">🗑️</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        var starterEditor = CodeMirror.fromTextArea(document.getElementById('starterCode'), {
            lineNumbers: true,
            mode: 'javascript',
            theme: 'dracula',
            lineWrapping: true
        });
        
        var solutionEditor = CodeMirror.fromTextArea(document.getElementById('solutionCode'), {
            lineNumbers: true,
            mode: 'javascript',
            theme: 'dracula',
            lineWrapping: true
        });
        
        document.getElementById('languageSelect').addEventListener('change', function() {
            var mode = 'javascript';
            if (this.value === 'php') mode = 'text/x-php';
            if (this.value === 'python') mode = 'text/x-python';
            starterEditor.setOption('mode', mode);
            solutionEditor.setOption('mode', mode);
        });
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>
    </script>
</body>
</html>