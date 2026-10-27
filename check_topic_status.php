<?php
require_once 'config/config.php';
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

$topic_id = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;
if (!$topic_id || !$is_logged_in) {
    echo json_encode(['completed' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT is_completed FROM topic_progress WHERE user_id = ? AND topic_id = ?");
$stmt->execute([$user_id, $topic_id]);
$completed = (bool)$stmt->fetchColumn();

echo json_encode(['completed' => $completed]);
?>