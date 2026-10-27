<?php
require_once 'config/config.php';

// Проверка прав администратора
if (!in_array('admin', $user_roles)) {
    header('Location: index.php');
    exit;
}

// Обработка ответа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'], $_POST['response'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    $response = trim($_POST['response']);
    $status = $_POST['status'] ?? 'resolved';
    if (!empty($response)) {
        $stmt = $pdo->prepare("UPDATE support_messages SET admin_response = ?, status = ? WHERE id = ?");
        $stmt->execute([$response, $status, $ticket_id]);
        $success = 'Ответ сохранён.';
    }
}

// Получаем все обращения
$tickets = $pdo->query("SELECT sm.*, u.full_name FROM support_messages sm LEFT JOIN users u ON sm.user_id = u.id ORDER BY sm.created_at DESC")->fetchAll();

$current_theme = $_SESSION['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Обращения в поддержку | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .ticket { background: white; border-radius: 16px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .ticket-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .ticket-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-new { background: #fef3c7; color: #92400e; }
        .status-in_progress { background: #dbeafe; color: #1e40af; }
        .status-resolved { background: #d1fae5; color: #065f46; }
        .ticket-message { margin: 15px 0; }
        .response-form { margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 15px; }
        .response-form textarea { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; }
        .btn { padding: 8px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        body.theme-dark .ticket { background: #1e293b; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #3b82f6; text-decoration: none; }
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size) ?>">
    <div class="container">
        <div class="admin-container">
            <a href="admin.php" class="back-link"><i class="fas fa-arrow-left"></i> Назад в админ-панель</a>
            <h1><i class="fas fa-headset"></i> Обращения в поддержку</h1>

            <?php if (isset($success)): ?>
                <div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 20px;"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (empty($tickets)): ?>
                <p>Нет обращений.</p>
            <?php else: ?>
                <?php foreach ($tickets as $t): ?>
                    <div class="ticket">
                        <div class="ticket-header">
                            <div>
                                <strong><?= $t['user_id'] ? htmlspecialchars($t['full_name']) : 'Гость' ?></strong>
                                <?php if ($t['email']): ?>
                                    <span style="color: #64748b; font-size: 14px;">&lt;<?= htmlspecialchars($t['email']) ?>&gt;</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="ticket-status status-<?= $t['status'] ?>">
                                    <?= $t['status'] === 'new' ? 'Новое' : ($t['status'] === 'in_progress' ? 'В работе' : 'Решено') ?>
                                </span>
                                <span style="font-size: 12px; color: #64748b; margin-left: 10px;"><?= date('d.m.Y H:i', strtotime($t['created_at'])) ?></span>
                            </div>
                        </div>
                        <div class="ticket-message"><?= nl2br(htmlspecialchars($t['message'])) ?></div>
                        <?php if ($t['admin_response']): ?>
                            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; margin-top: 10px;">
                                <strong>Ответ:</strong>
                                <p><?= nl2br(htmlspecialchars($t['admin_response'])) ?></p>
                            </div>
                        <?php endif; ?>
                        <form method="post" class="response-form">
                            <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                            <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-start;">
                                <textarea name="response" rows="2" style="flex: 1;" placeholder="Введите ответ..."><?= htmlspecialchars($t['admin_response'] ?? '') ?></textarea>
                                <div style="display: flex; gap: 10px;">
                                    <select name="status" style="padding: 8px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                        <option value="new" <?= $t['status'] == 'new' ? 'selected' : '' ?>>Новое</option>
                                        <option value="in_progress" <?= $t['status'] == 'in_progress' ? 'selected' : '' ?>>В работе</option>
                                        <option value="resolved" <?= $t['status'] == 'resolved' ? 'selected' : '' ?>>Решено</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Ответить</button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>