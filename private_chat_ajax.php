<?php
require_once 'config/config.php';

if (!$is_logged_in) exit;

$course_id = (int)$_GET['course_id'];
$teacher_id = (int)$_GET['teacher_id'];

if (!$course_id || !$teacher_id) exit;

$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$teacher_id]);
$teacher_name = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT * FROM private_messages 
    WHERE (from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?)
    ORDER BY created_at ASC
");
$stmt->execute([$_SESSION['user_id'], $teacher_id, $teacher_id, $_SESSION['user_id']]);
$messages = $stmt->fetchAll();

$stmt = $pdo->prepare("UPDATE private_messages SET is_read = 1 WHERE to_user_id = ? AND from_user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id'], $teacher_id]);

foreach ($messages as $msg):
    $is_own = ($msg['from_user_id'] == $_SESSION['user_id']);
?>
    <div class="message <?= $is_own ? 'own' : '' ?>">
        <div class="message-header">
            <strong><?= $is_own ? 'Вы' : htmlspecialchars($teacher_name) ?></strong>
            <small><?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?></small>
        </div>
        <div class="message-body">
            <?php if ($msg['message']): ?>
                <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
            <?php endif; ?>
            <?php if ($msg['file_path'] && file_exists($msg['file_path'])): ?>
                <?php if (strpos($msg['file_type'], 'image/') === 0): ?>
                    <img src="<?= htmlspecialchars($msg['file_path']) ?>" style="max-width: 300px;">
                <?php elseif (strpos($msg['file_type'], 'video/') === 0): ?>
                    <video controls style="max-width: 400px;">
                        <source src="<?= htmlspecialchars($msg['file_path']) ?>" type="<?= $msg['file_type'] ?>">
                    </video>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>