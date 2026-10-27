<?php
require_once 'config/config.php';

// Если пользователь уже авторизован – перенаправляем
if ($is_logged_in) {
    header('Location: index.php');
    exit;
}

// Инициализация переменных (чтобы избежать ошибок Undefined)
$error = '';
$success = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Введите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email';
    } else {
        // Проверяем, существует ли пользователь
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Генерируем токен
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Сохраняем токен в БД
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            if ($stmt->execute([$token, $expires, $user['id']])) {
                // Формируем ссылку для сброса (корректный путь)
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
                $reset_link = $protocol . $_SERVER['HTTP_HOST'] . $base_path . '/reset_password.php?token=' . $token;
                
                $success = 'Ссылка для сброса пароля создана. Перейдите по ней, чтобы установить новый пароль.';
            } else {
                $error = 'Ошибка при сохранении токена. Попробуйте позже.';
            }
        } else {
            // Не показываем, что email не найден – безопасность
            $success = 'Если такой email существует, ссылка для сброса будет создана. (Но в данном режиме email не проверяется)';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Восстановление пароля | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .reset-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 0 20px;
        }
        .reset-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .reset-card h1 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
        }
        .btn-submit {
            width: 100%;
            background: #3b82f6;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .reset-link-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            word-break: break-all;
            margin-top: 15px;
        }
        .reset-link-box a {
            color: #3b82f6;
            text-decoration: none;
        }
        .reset-link-box a:hover {
            text-decoration: underline;
        }
        .back-link {
            display: inline-block;
            margin: 20px;
            color: #3b82f6;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="login.php" class="back-link"><i class="fas fa-arrow-left"></i> Назад к входу</a>
        <div class="reset-container">
            <div class="reset-card">
                <h1><i class="fas fa-key"></i> Восстановление пароля</h1>
                <p>Введите ваш email, и вы получите ссылку для сброса пароля (письмо не отправляется – ссылка появится прямо здесь).</p>
                
                <?php if (!empty($error)): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success"><?= htmlspecialchars($success) ?></div>
                    <?php if (!empty($reset_link)): ?>
                        <div class="reset-link-box">
                            <strong>🔗 Ссылка для сброса пароля:</strong><br>
                            <a href="<?= htmlspecialchars($reset_link) ?>" target="_blank"><?= htmlspecialchars($reset_link) ?></a>
                            <p style="margin-top: 10px; font-size: 14px; color: #475569;">
                                <i class="fas fa-info-circle"></i> Перейдите по этой ссылке, чтобы установить новый пароль. Ссылка действительна 1 час.
                            </p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <form method="post">
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Ваш email" required>
                        </div>
                        <button type="submit" class="btn-submit">Создать ссылку для сброса</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>