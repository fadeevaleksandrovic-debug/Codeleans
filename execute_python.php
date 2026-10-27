<?php
header('Content-Type: application/json');
error_reporting(0);

$input = json_decode(file_get_contents('php://input'), true);
$code = trim($input['code'] ?? '');

function sendResponse($error, $output) {
    echo json_encode(['error' => $error, 'output' => $output]);
    exit;
}

if (empty($code)) {
    sendResponse('Код пустой', '');
}

// Безопасность
$dangerous = ['os.', 'subprocess.', 'sys.', 'shutil.', '__import__', 'exec(', 'eval(', 'compile(', 'open(', 'file(', 'input('];
foreach ($dangerous as $word) {
    if (stripos($code, $word) !== false) {
        sendResponse("Использование '$word' запрещено", '');
    }
}

$tempFile = sys_get_temp_dir() . '/py_' . uniqid() . '.py';
file_put_contents($tempFile, $code);

// Определяем путь к python
$pythonCmd = 'python3';
exec("which python3 2>/dev/null", $_, $ret);
if ($ret !== 0) $pythonCmd = 'python';

$command = "$pythonCmd " . escapeshellarg($tempFile) . " 2>&1";
$output = [];
$returnVar = 0;
exec($command, $output, $returnVar);
unlink($tempFile);

$outputStr = trim(implode("\n", $output));
if ($returnVar !== 0 && empty($outputStr)) {
    sendResponse("Ошибка выполнения (код $returnVar). Проверьте синтаксис.", '');
}
sendResponse(null, $outputStr);
?>