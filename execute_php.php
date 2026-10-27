<?php
header('Content-Type: application/json');
error_reporting(0);

$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';

function sendResponse($error, $output) {
    echo json_encode(['error' => $error, 'output' => $output]);
    exit;
}

if (empty(trim($code))) {
    sendResponse('Код пустой', '');
}

// Запрещённые функции
$dangerous = ['exec', 'shell_exec', 'system', 'passthru', 'popen', 'proc_open', 'eval', 'assert', 'include', 'require', 'fopen', 'file_get_contents', 'file_put_contents', 'unlink', 'rmdir', 'mkdir', 'chmod', 'chown', 'curl_exec', 'curl_init'];
foreach ($dangerous as $func) {
    if (preg_match('/\b' . preg_quote($func, '/') . '\s*\(/i', $code)) {
        sendResponse("Использование функции '$func' запрещено", '');
    }
}

// Убираем теги 
$code = preg_replace('/<\?php/', '', $code);
$code = preg_replace('/\?>/', '', $code);
$code = trim($code);

// Перехват вывода
ob_start();
try {
    eval($code);
    $output = ob_get_clean();
} catch (Throwable $e) {
    ob_end_clean();
    sendResponse('Ошибка выполнения: ' . $e->getMessage(), '');
}

sendResponse(null, trim($output));
?>