<?php
require_once 'config/config.php';

$error = '';
$success = '';

// Обработка отправки
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message)) {
        $error = 'Введите текст сообщения.';
    } else {
        if (!$is_logged_in && empty($email)) {
            $error = 'Введите ваш email.';
        } elseif (!$is_logged_in && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Некорректный email.';
        } else {
            $user_id = $is_logged_in ? $user_id : null;
            $email = $is_logged_in ? $user_email : $email;
            $stmt = $pdo->prepare("INSERT INTO support_messages (user_id, email, message) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $email, $message]);
            // Редирект, чтобы избежать повторной отправки при обновлении страницы
            header('Location: support.php?success=1');
            exit;
        }
    }
}

// Если есть параметр success, показываем сообщение
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = 'Ваше сообщение отправлено. Мы свяжемся с вами в ближайшее время.';
}

// Получаем обращения пользователя (если авторизован)
$my_tickets = [];
if ($is_logged_in) {
    $stmt = $pdo->prepare("SELECT * FROM support_messages WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $my_tickets = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Техподдержка | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .support-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .ticket-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .ticket-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-new { background: #fef3c7; color: #92400e; }
        .status-in_progress { background: #dbeafe; color: #1e40af; }
        .status-resolved { background: #d1fae5; color: #065f46; }
        .ticket-message { margin: 10px 0; }
        .ticket-response {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
        }
        .btn-submit {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-submit:hover {
            background: #2563eb;
        }
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        /* Логотип */
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 30px;
        }
        .logo i {
            font-size: 32px;
            color: #3b82f6;
        }
        /* Ссылка назад */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 20px 0;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        /* Подвал */
        .footer {
            padding: 40px 0;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            margin-top: 60px;
        }
        .footer-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 20px;
            margin-bottom: 15px;
        }
        .footer-info p {
            margin: 5px 0;
            color: #64748b;
            font-size: 14px;
        }
        /* Тёмная тема */
        body.theme-dark .ticket-card {
            background: #1e293b;
        }
        body.theme-dark .ticket-response {
            background: #0f172a;
        }
        body.theme-dark .logo {
            color: #e2e8f0;
        }
        .no-tickets {
            text-align: center;
            padding: 30px;
            color: #94a3b8;
            background: #f8fafc;
            border-radius: 12px;
            margin-top: 15px;
        }
        body.theme-dark .no-tickets {
            background: #1e293b;
        }
        .no-tickets i {
            font-size: 48px;
            display: block;
            margin-bottom: 10px;
            color: #cbd5e1;
        }
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size) ?>">
    <div class="container">
        <!-- Логотип -->
        <div class="logo">
            <i class="fas fa-code"></i>
            <span>CodeLeans</span>
        </div>

        <!-- Ссылка "Вернуться на главную" -->
        <a href="index.php" class="back-link">
            <i class="fas fa-home"></i> Вернуться на главную
        </a>

        <!-- Основной контент -->
        <div class="support-container">
            <h1><i class="fas fa-headset"></i> Техподдержка</h1>
            <p>Опишите вашу проблему или вопрос, и мы ответим вам в ближайшее время.</p>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="post" class="ticket-card">
                <?php if (!$is_logged_in): ?>
                    <div class="form-group">
                        <label>Ваш email</label>
                        <input type="email" name="email" required placeholder="ivan@example.com">
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Сообщение</label>
                    <textarea name="message" rows="5" required placeholder="Опишите проблему..."></textarea>
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Отправить</button>
            </form>

            <?php if ($is_logged_in): ?>
                <h2>Ваши обращения</h2>
                <?php if (empty($my_tickets)): ?>
                    <div class="no-tickets">
                        <i class="fas fa-inbox"></i>
                        <p>У вас пока нет обращений. Создайте обращение выше.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($my_tickets as $ticket): ?>
                        <div class="ticket-card">
                            <div>
                                <span class="ticket-status status-<?= $ticket['status'] ?>">
                                    <?= $ticket['status'] === 'new' ? 'Новое' : ($ticket['status'] === 'in_progress' ? 'В работе' : 'Решено') ?>
                                </span>
                                <small style="color: #64748b; margin-left: 15px;"><?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?></small>
                            </div>
                            <div class="ticket-message"><?= nl2br(htmlspecialchars($ticket['message'])) ?></div>
                            <?php if ($ticket['admin_response']): ?>
                                <div class="ticket-response">
                                    <strong>Ответ администратора:</strong>
                                    <p><?= nl2br(htmlspecialchars($ticket['admin_response'])) ?></p>
                                </div>
                            <?php else: ?>
                                <div style="color: #94a3b8; font-size: 14px; margin-top: 10px;">
                                    <i class="fas fa-clock"></i> Ответ пока не получен
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Подвал -->
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-logo">
                    <i class="fas fa-code"></i>
                    <span>CodeLeans</span>
                </div>
                <div class="footer-info">
                    <p>Пособие выполнено в рамках курсового проекта</p>
                    <p>Разработчик: Фадеев Виктор Юрьевич</p>
                    <p>© 2026 Все права защищены</p>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>