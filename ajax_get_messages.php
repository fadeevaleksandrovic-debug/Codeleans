<?php
require_once 'config/config.php';
if (!$is_logged_in || !$is_teacher) exit;

$with_user = (int)$_GET['with_user'];
$stmt = $pdo->prepare("
    SELECT * FROM private_messages 
    WHERE (from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?)
    ORDER BY created_at ASC
");
$stmt->execute([$user_id, $with_user, $with_user, $user_id]);
$messages = $stmt->fetchAll();

foreach ($messages as $msg):
    $is_teacher = ($msg['from_user_id'] == $user_id);
?>
    <div class="message <?= $is_teacher ? 'outgoing' : 'incoming' ?>">
        <div class="message-body"><p><?= nl2br(htmlspecialchars($msg['message'])) ?></p></div>
        <div class="message-time"><?= date('H:i d.m.Y', strtotime($msg['created_at'])) ?></div>
    </div>
<?php endforeach;