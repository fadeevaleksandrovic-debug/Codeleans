<?php
require_once 'config/config.php';

// Если пользователь уже авторизован на сайте и является администратором – перенаправляем в админку
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT r.role_name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('admin', $roles)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $_SESSION['user_id'];
        $_SESSION['admin_email'] = $_SESSION['user_email'] ?? '';
        header('Location: admin.php');
        exit;
    }
}

// Если админ уже авторизован через отдельную сессию
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        // Проверка по таблице admin
        $stmt = $pdo->prepare("SELECT id, email, password FROM admin WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Неверный email или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход для администратора | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style/global.css">
    <style>
        .login-container { max-width: 500px; margin: 100px auto; padding: 0 20px; }
        .login-card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .login-header { text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group input { width: 100%; padding: 14px; border: 1px solid #e2e8f0; border-radius: 10px; }
        .btn-login { width: 100%; background: #3b82f6; color: white; padding: 14px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .error { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 10px; margin-bottom: 20px; }
        .back-link { display: inline-block; margin: 20px; color: #3b82f6; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> На главную</a>
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <i class="fas fa-user-shield" style="font-size: 48px; color: #3b82f6;"></i>
                    <h1>Вход администратора</h1>
                    <p>Управление платформой CodeLeans</p>
                </div>
                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email администратора" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Пароль" required>
                    </div>
                    <button type="submit" class="btn-login">Войти</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>