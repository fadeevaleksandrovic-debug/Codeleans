<?php
require_once 'config/config.php';

// Если пользователь уже авторизован – перенаправляем
if ($is_logged_in) {
    header('Location: index.php');
    exit;
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Проверяем токен
$stmt = $pdo->prepare("SELECT id, full_name, reset_expires FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user || strtotime($user['reset_expires']) < time()) {
    die('Недействительная или просроченная ссылка для сброса пароля.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($password)) {
        $error = 'Введите новый пароль';
    } elseif (strlen($password) < 8) {
        $error = 'Пароль должен содержать минимум 8 символов';
    } elseif ($password !== $confirm) {
        $error = 'Пароли не совпадают';
    } else {
        // Обновляем пароль
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->execute([$hashed, $user['id']]);
        $success = 'Пароль успешно изменён. Теперь вы можете войти.';
        // Очищаем переменную пользователя, чтобы форма больше не показывалась
        $user = null;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Установка нового пароля | CodeLeans</title>
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
        .error { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 10px; margin-bottom: 20px; }
        .success { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 10px; margin-bottom: 20px; }
        .back-link { display: inline-block; margin: 20px; color: #3b82f6; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <a href="login.php" class="back-link"><i class="fas fa-arrow-left"></i> На страницу входа</a>
        <div class="reset-container">
            <div class="reset-card">
                <?php if ($success): ?>
                    <div class="success">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                        <p style="margin-top: 15px;"><a href="login.php">Войти</a></p>
                    </div>
                <?php elseif ($user): ?>
                    <h1><i class="fas fa-lock-open"></i> Новый пароль</h1>
                    <p>Введите новый пароль для аккаунта: <?= htmlspecialchars($user['full_name']) ?></p>
                    <?php if ($error): ?>
                        <div class="error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="form-group">
                            <input type="password" name="password" placeholder="Новый пароль (мин. 8 символов)" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="confirm_password" placeholder="Подтвердите пароль" required>
                        </div>
                        <button type="submit" class="btn-submit">Сохранить пароль</button>
                    </form>
                <?php else: ?>
                    <div class="error">Недействительная или просроченная ссылка для сброса пароля.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>