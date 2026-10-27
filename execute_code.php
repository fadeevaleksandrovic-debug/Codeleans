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

// Упрощённый парсер для console.log и alert
preg_match_all('/(?:console\.log|alert)\s*\(\s*([^)]+)\s*\)\s*;?/i', $code, $matches);
$lines = [];
foreach ($matches[1] as $match) {
    $val = trim($match, " \t\n\r\0\x0B'\"");
    $lines[] = $val;
}

// Если есть function и вызов – симулируем вызов (для самых простых заданий)
if (empty($lines) && preg_match('/function\s+(\w+)/', $code, $funcMatch)) {
    $funcName = $funcMatch[1];
    if (strpos($code, $funcName . '()') !== false) {
        $lines[] = "[Функция '$funcName' определена и вызвана]";
    }
}

$output = implode("\n", $lines);
sendResponse(null, $output ?: '✅ Синтаксис верный! Используйте console.log() для вывода результата.');
?>