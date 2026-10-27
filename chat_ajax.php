<?php
require_once 'config/config.php';

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if (!$course_id) {
    exit;
}

// Упрощённый запрос – только нужные поля
$stmt = $pdo->prepare("
    SELECT m.*, u.full_name
    FROM chat_messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.course_id = ?
    ORDER BY m.created_at ASC
");
$stmt->execute([$course_id]);
$messages = $stmt->fetchAll();

foreach ($messages as $msg): 
    // Определяем класс для стилей (учитель/админ – можно добавить позже)
    $role_class = '';
    // Можно добавить определение роли, но для простоты оставим пустым
?>
    <div class="message <?php echo $role_class; ?>">
        <div class="message-header">
            <strong><?php echo htmlspecialchars($msg['full_name']); ?></strong>
            <small><?php echo date('d.m.Y H:i', strtotime($msg['created_at'])); ?></small>
        </div>
        <div class="message-body">
            <?php if ($msg['message']): ?>
                <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
            <?php endif; ?>
            <?php if ($msg['file_path'] && file_exists($msg['file_path'])): ?>
                <?php if (strpos($msg['file_type'], 'image/') === 0): ?>
                    <img src="<?php echo htmlspecialchars($msg['file_path']); ?>" style="max-width: 300px;">
                <?php elseif (strpos($msg['file_type'], 'video/') === 0): ?>
                    <video controls style="max-width: 400px;">
                        <source src="<?php echo htmlspecialchars($msg['file_path']); ?>" type="<?php echo $msg['file_type']; ?>">
                    </video>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>