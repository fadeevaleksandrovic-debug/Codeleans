<?php
require_once 'config/config.php';

if (!$is_logged_in) {
    header('Location: login.php');
    exit;
}

$topic_id = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;
if (!$topic_id) die('Тема не указана');

$stmt = $pdo->prepare("SELECT t.*, c.course_name, c.id as course_id FROM topics t JOIN courses c ON t.course_id = c.id WHERE t.id = ?");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch();
if (!$topic) die('Тема не найдена');

$stmt = $pdo->prepare("SELECT * FROM assignments WHERE topic_id = ? ORDER BY order_num ASC");
$stmt->execute([$topic_id]);
$assignments = $stmt->fetchAll();
$total_assignments = count($assignments);

$current_assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
$current_assignment = null;
foreach ($assignments as $a) {
    if ($a['id'] == $current_assignment_id) {
        $current_assignment = $a;
        break;
    }
}
if (!$current_assignment) {
    $current_assignment = $assignments[0] ?? null;
    $current_assignment_id = $current_assignment ? $current_assignment['id'] : 0;
}
$current_order = $current_assignment ? $current_assignment['order_num'] : 1; // для совместимости

$progress_map = [];
if ($is_logged_in) {
    $stmt = $pdo->prepare("SELECT assignment_id, is_correct, user_code, attempts FROM assignment_progress WHERE user_id = ?");
    $stmt->execute([$user_id]);
    while ($row = $stmt->fetch()) {
        $progress_map[$row['assignment_id']] = [
            'is_correct' => $row['is_correct'],
            'user_code' => $row['user_code'],
            'attempts' => $row['attempts']
        ];
    }
}

$user_code = $progress_map[$current_assignment['id']]['user_code'] ?? $current_assignment['starter_code'] ?? '';
$is_completed = $progress_map[$current_assignment['id']]['is_correct'] ?? false;
$attempts = $progress_map[$current_assignment['id']]['attempts'] ?? 0;

$all_assignments_completed = false;
$completed_count = 0;
foreach ($assignments as $a) {
    if (isset($progress_map[$a['id']]) && $progress_map[$a['id']]['is_correct']) {
        $completed_count++;
    }
}
$all_assignments_completed = ($completed_count == $total_assignments);

function normalizeOutput($str) {
    $str = trim($str);
    $str = preg_replace('/\s+/', ' ', $str);
    $str = preg_replace('/int\(\d+\)/', '', $str);
    $str = preg_replace('/string\(\d+\)/', '', $str);
    return trim($str);
}

// AJAX-запуск кода
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'run_code') {
    header('Content-Type: application/json');
    $code = $_POST['code'] ?? '';
    $language = $current_assignment['language'] ?? 'javascript';
    
    $endpoint = '';
    if ($language === 'php') $endpoint = 'execute_php.php';
    elseif ($language === 'python') $endpoint = 'execute_python.php';
    else $endpoint = 'execute_code.php';
    
    $ch = curl_init('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/' . $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['code' => $code]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($resp === false || $httpCode !== 200) {
        echo json_encode(['error' => 'Не удалось выполнить код. Проверьте работу сервера.', 'output' => '']);
    } else {
        echo $resp;
    }
    exit;
}

$result = null;
$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_code'])) {
    $submitted_code = $_POST['code'];
    $assignment_id = $current_assignment['id'];
    $language = $current_assignment['language'] ?? 'javascript';
    $expected = trim($current_assignment['expected_output'] ?? '');
    $output = trim($_POST['output'] ?? '');
    
    if (empty($expected)) {
        $error = "❌ Ожидаемый результат для этого задания не задан. Сообщите преподавателю.";
    } else {
        $is_correct = (normalizeOutput($output) === normalizeOutput($expected));
        $message = $is_correct ? "✅ Задание выполнено верно!\n\nВывод:\n" . $output : "❌ Неправильный результат\n\nОжидалось:\n{$expected}\n\nПолучено:\n{$output}";
        
        $new_attempts = $attempts + 1;
        $stmt = $pdo->prepare("
            INSERT INTO assignment_progress (user_id, assignment_id, user_code, is_correct, attempts, last_submitted_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            user_code = VALUES(user_code), 
            is_correct = VALUES(is_correct), 
            attempts = VALUES(attempts),
            last_submitted_at = NOW()
        ");
        $stmt->execute([$user_id, $assignment_id, $submitted_code, $is_correct ? 1 : 0, $new_attempts]);
        
        if ($is_correct) {
            $success = true;
            $result = $message;
            $is_completed = true;
            $user_code = $submitted_code;
            
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as completed 
                FROM assignment_progress ap
                JOIN assignments a ON ap.assignment_id = a.id
                WHERE a.topic_id = ? AND ap.user_id = ? AND ap.is_correct = 1
            ");
            $stmt->execute([$topic_id, $user_id]);
            $new_completed_count = (int)$stmt->fetchColumn();
            if ($new_completed_count == $total_assignments) {
                $stmt = $pdo->prepare("
                    INSERT INTO topic_progress (user_id, topic_id, is_completed, completed_at) 
                    VALUES (?, ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE 
                    is_completed = 1,
                    completed_at = IF(is_completed = 0, NOW(), completed_at)
                ");
                $stmt->execute([$user_id, $topic_id]);
                updateUserCourseProgress($user_id, $topic['course_id']);
                $all_assignments_completed = true;
            }
        } else {
            $error = $message;
            $user_code = $submitted_code;
        }
    }
}

$current_theme = $_SESSION['theme'] ?? 'light';
$avatar = $_SESSION['avatar'] ?? null;
$current_font_size = $_SESSION['font_size'] ?? 'medium';
$current_language = $current_assignment['language'] ?? 'javascript';

// Определяем режим для CodeMirror
$language_mode = 'javascript';
if ($current_language === 'php') $language_mode = 'text/x-php';
elseif ($current_language === 'python') $language_mode = 'text/x-python';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Задания: <?= htmlspecialchars($topic['topic_name']) ?> | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/php/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/python/python.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        body.theme-dark { background: #0f172a; color: #e2e8f0; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 30px; }
        .logo { display: flex; align-items: center; gap: 10px; }
        .logo i { font-size: 28px; color: #3b82f6; }
        .logo h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
        body.theme-dark .logo h1 { color: #e2e8f0; }
        .assignments-nav { background: white; border-radius: 60px; padding: 10px 20px; margin-bottom: 30px; display: inline-flex; gap: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        body.theme-dark .assignments-nav { background: #1e293b; }
        .assignment-circle { width: 48px; height: 48px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; text-decoration: none; color: #64748b; transition: all 0.3s; border: 2px solid transparent; }
        .assignment-circle:hover { background: #e2e8f0; transform: translateY(-2px); }
        .assignment-circle.active { background: #3b82f6; color: white; border-color: #3b82f6; }
        .assignment-circle.completed { background: #10b981; color: white; }
        .assignment-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px; }
        .assignment-description { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        body.theme-dark .assignment-description { background: #1e293b; }
        .assignment-description h2 { font-size: 24px; margin-bottom: 20px; color: #1e293b; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        body.theme-dark .assignment-description h2 { color: #e2e8f0; }
        body.theme-dark .assignment-description h1 { color: #e2e8f0; }
        .code-editor-container { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        body.theme-dark .code-editor-container { background: #1e293b; }
        .CodeMirror { height: 400px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 14px; }
        body.theme-dark .CodeMirror { border-color: #475569; }
        .output-area { margin-top: 20px; background: #1e1e1e; border-radius: 12px; padding: 15px; }
        .output-label { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .output-content { background: #0f0f0f; border-radius: 8px; padding: 12px; color: #10b981; font-family: 'JetBrains Mono', monospace; font-size: 14px; white-space: pre-wrap; word-break: break-all; max-height: 150px; overflow-y: auto; }
        .result-area { margin-top: 20px; padding: 15px; border-radius: 12px; display: none; white-space: pre-line; font-family: 'JetBrains Mono', monospace; font-size: 14px; line-height: 1.5; }
        .result-area.success { display: block; background: #d1fae5; color: #065f46; border: 1px solid #10b981; }
        .result-area.error { display: block; background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
        .btn-group { display: flex; gap: 15px; margin-top: 20px; flex-wrap: wrap; }
        .btn-run, .btn-submit { display: inline-flex; align-items: center; gap: 8px; padding: 12px 30px; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-run { background: #10b981; color: white; }
        .btn-run:hover { background: #059669; transform: translateY(-2px); }
        .btn-submit { background: #3b82f6; color: white; }
        .btn-submit:hover { background: #2563eb; transform: translateY(-2px); }
        .btn-submit:disabled { background: #94a3b8; cursor: not-allowed; transform: none; }
        .completed-badge { display: inline-block; background: #10b981; color: white; padding: 6px 14px; border-radius: 20px; font-size: 13px; margin-left: 15px; }
        .topic-completed-banner { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 15px 25px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; margin: 20px 0; color: #3b82f6; text-decoration: none; font-weight: 500; }
        .footer { padding: 40px 0; border-top: 1px solid #e2e8f0; text-align: center; margin-top: 60px; }
        body.theme-dark .footer { border-color: #334155; }
        @media (max-width: 768px) { .assignment-layout { grid-template-columns: 1fr; } }
        
        /* Дополнительные стили для содержимого задания */
        .assignment-description h3 {
            margin-top: 20px;
            margin-bottom: 10px;
            color: #3b82f6;
        }
        body.theme-dark .assignment-description h3 {
            color: #60a5fa;
        }
        .assignment-description ul, .assignment-description ol {
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
        .assignment-description pre {
            background: #1e1e1e;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'JetBrains Mono', monospace;
            font-size: 14px;
            margin: 10px 0;
        }
        .assignment-description pre code {
            background: transparent;
            color: #e2e8f0;
            padding: 0;
        }
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size) ?>">
<div class="container">
    <header class="header">
        <div class="logo"><i class="fas fa-code"></i><h1>CodeLeans</h1></div>
        <div class="header-actions">
            <a href="profile.php" class="profile-btn" style="display:flex;align-items:center;gap:8px;padding:8px 16px;background:white;border:1px solid #e2e8f0;border-radius:40px;text-decoration:none;color:#1e293b;">
                <i class="fas fa-user-circle"></i>
                <span><?= htmlspecialchars($user_name) ?></span>
            </a>
        </div>
    </header>

    <?php if ($all_assignments_completed): ?>
        <div class="topic-completed-banner">
            <div><i class="fas fa-check-circle"></i> <strong>Поздравляем! 🎉</strong> Вы выполнили все задания по теме!</div>
            <a href="topic.php?id=<?= $topic_id ?>" style="background:rgba(255,255,255,0.2); padding:8px 20px; border-radius:8px; text-decoration:none; color:white;"><i class="fas fa-arrow-left"></i> Вернуться к теме</a>
        </div>
    <?php endif; ?>

    <a href="topic.php?id=<?= $topic_id ?>" class="back-link"><i class="fas fa-arrow-left"></i> Вернуться к теме</a>

    <div class="assignments-nav">
    <?php $counter = 1; foreach ($assignments as $a): 
        $is_assignment_completed = $progress_map[$a['id']]['is_correct'] ?? false;
        $order_display = $counter; // всегда показываем порядковый номер
    ?>
        <a href="?topic_id=<?= $topic_id ?>&assignment_id=<?= $a['id'] ?>" 
           class="assignment-circle <?= ($current_assignment_id == $a['id']) ? 'active' : '' ?> <?= $is_assignment_completed ? 'completed' : '' ?>">
            <?php if ($is_assignment_completed): ?>
                <i class="fas fa-check"></i>
            <?php else: ?>
                <?= $order_display ?>
            <?php endif; ?>
        </a>
    <?php $counter++; endforeach; ?>
</div>

    <div class="assignment-layout">
        <div class="assignment-description">
            <h2>
                <i class="fas fa-tasks"></i>
                Задание <?= $current_order ?> из <?= $total_assignments ?>
                <?php if ($is_completed): ?>
                    <span class="completed-badge"><i class="fas fa-check-circle"></i> Выполнено</span>
                <?php endif; ?>
            </h2>
            <h1 style="font-size: 28px; margin-bottom: 20px;"><?= htmlspecialchars($current_assignment['title']) ?></h1>
            <?= $current_assignment['description'] ?>
            <?php if ($attempts > 0): ?>
                <p style="margin-top: 20px; color: #64748b;"><i class="fas fa-history"></i> Попыток: <?= $attempts ?></p>
            <?php endif; ?>
        </div>

        <div class="code-editor-container">
            <h3><i class="fas fa-code"></i> Редактор кода (<?= strtoupper($current_language) ?>)</h3>
            <form method="post" id="assignmentForm">
                <textarea id="codeEditor" name="code"><?= htmlspecialchars($user_code) ?></textarea>
                <input type="hidden" name="output" id="outputHidden">
                <div class="output-area">
                    <div class="output-label"><i class="fas fa-terminal"></i><span>Результат выполнения</span></div>
                    <div class="output-content" id="outputContent"><span style="color: #64748b;">Нажмите «Запустить код»</span></div>
                </div>
                <div id="result" class="result-area <?= $success ? 'success' : ($error ? 'error' : '') ?>">
                    <?= nl2br(htmlspecialchars($success ? $result : ($error ?: ''))) ?>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn-run" id="runBtn"><i class="fas fa-play"></i> Запустить код</button>
                    <button type="submit" name="submit_code" class="btn-submit" id="submitBtn" <?= $is_completed ? 'disabled' : '' ?>>
                        <i class="fas fa-check-circle"></i> <?= $is_completed ? 'Задание выполнено' : 'Проверить задание' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

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
    // Функция выполнения JavaScript кода в браузере с перехватом console.log
function runJavaScript(code, callback) {
    try {
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

    // Дожидаемся загрузки всех скриптов
    document.addEventListener('DOMContentLoaded', function() {
        var textarea = document.getElementById('codeEditor');
        if (textarea) {
            var editor = CodeMirror.fromTextArea(textarea, {
                lineNumbers: true,
                mode: '<?= $language_mode ?>',
                theme: 'dracula',
                indentUnit: 4,
                tabSize: 4,
                lineWrapping: true,
                viewportMargin: Infinity
            });

            // Запуск кода
            document.getElementById('runBtn').addEventListener('click', function() {
    var code = editor.getValue();
    var outputDiv = document.getElementById('outputContent');
    var lang = '<?= $current_language ?>';

    // Для JavaScript – выполняем код в браузере
    if (lang === 'javascript') {
        outputDiv.innerHTML = '<span style="color: #f59e0b;">Выполнение...</span>';
        runJavaScript(code, (err, out) => {
            if (err) {
                outputDiv.innerHTML = '<span style="color: #ef4444;">❌ Ошибка: ' + err + '</span>';
                document.getElementById('outputHidden').value = '';
            } else {
                var result = out || '[Нет вывода]';
                outputDiv.innerHTML = result;
                document.getElementById('outputHidden').value = result;
            }
        });
    } else {
        // Для PHP/Python – отправляем на сервер
        outputDiv.innerHTML = '<span style="color: #f59e0b;">Выполнение...</span>';
        var formData = new FormData();
        formData.append('action', 'run_code');
        formData.append('code', code);
        fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    outputDiv.innerHTML = '<span style="color: #ef4444;">❌ ' + data.error + '</span>';
                    document.getElementById('outputHidden').value = '';
                } else {
                    var result = data.output || '[Нет вывода]';
                    outputDiv.innerHTML = result;
                    document.getElementById('outputHidden').value = result;
                }
            })
            .catch(err => {
                outputDiv.innerHTML = '<span style="color: #ef4444;">❌ Ошибка: ' + err.message + '</span>';
                document.getElementById('outputHidden').value = '';
            });
    }
});
            // Отправка формы
            document.getElementById('assignmentForm').addEventListener('submit', function(e) {
                var output = document.getElementById('outputHidden').value;
                if (!output && output !== '') {
                    e.preventDefault();
                    alert('Сначала запустите код (нажмите «Запустить код»), чтобы получить результат выполнения.');
                    return false;
                }
                return true;
            });

            // Автозапуск кода при загрузке
            setTimeout(function() {
                if (editor.getValue().trim().length > 0) {
                    document.getElementById('runBtn').click();
                }
            }, 500);
        }
    });
</script>
</body>
</html>