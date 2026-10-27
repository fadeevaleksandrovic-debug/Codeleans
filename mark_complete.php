<?php
require_once 'config/config.php';

header('Content-Type: application/json');

if (!$is_logged_in) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$topic_id = isset($input['topic_id']) ? (int)$input['topic_id'] : 0;

if (!$topic_id) {
    echo json_encode(['success' => false, 'error' => 'Не указана тема']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT course_id FROM topics WHERE id = ?");
    $stmt->execute([$topic_id]);
    $course_id = $stmt->fetchColumn();

    if (!$course_id) {
        echo json_encode(['success' => false, 'error' => 'Тема не найдена']);
        exit;
    }

    // Отметить тему
    $stmt = $pdo->prepare("
        INSERT INTO topic_progress (user_id, topic_id, is_completed, time_spent_minutes) 
        VALUES (?, ?, 1, 0) 
        ON DUPLICATE KEY UPDATE is_completed = 1
    ");
    $stmt->execute([$user_id, $topic_id]);

    // Обновить прогресс курса
    $new_percent = updateUserCourseProgress($user_id, $course_id);

    echo json_encode(['success' => true, 'progress_percent' => $new_percent]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}