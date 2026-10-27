<?php
require_once 'config/config.php';

// Если пользователь уже авторизован, перенаправляем на главную
if ($is_logged_in) {
    header('Location: index.php');
    exit();
}

$errors = [];

// Обработка формы авторизации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Валидация
    if (empty($email)) {
        $errors[] = 'Введите email адрес';
    }

    if (empty($password)) {
        $errors[] = 'Введите пароль';
    }

    if (empty($errors)) {
        // Поиск пользователя в базе данных
        $stmt = $pdo->prepare("SELECT id, full_name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Получаем роли пользователя
            $role_stmt = $pdo->prepare("SELECT r.role_name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ?");
            $role_stmt->execute([$user['id']]);
            $roles = $role_stmt->fetchAll(PDO::FETCH_COLUMN);

            $user_level = 'student';
            $is_teacher = false;

            foreach ($roles as $role) {
                if ($role === 'teacher') {
                    $is_teacher = true;
                    $user_level = 'teacher';
                } elseif ($role === 'admin') {
                    $user_level = 'admin';
                    $is_teacher = true;
                }
            }

            // Сохраняем пользователя в сессии
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_level'] = $user_level;
            $_SESSION['is_teacher'] = $is_teacher;

            // Если выбрано "Запомнить меня", устанавливаем долгую куку
            if (isset($_POST['rememberMe']) && $_POST['rememberMe'] === 'on') {
                $token = bin2hex(random_bytes(32));
                // Здесь можно сохранить токен в БД для автоматического входа
                setcookie('remember_token', $token, time() + 86400 * 30, '/');
            }

            // Обновляем updated_at, если поле существует
            $check_column = $pdo->query("SHOW COLUMNS FROM users LIKE 'updated_at'")->fetch();
            if ($check_column) {
                $update_stmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                $update_stmt->execute([$user['id']]);
            }

            // Перенаправляем на главную
            header('Location: index.php');
            exit();
        } else {
            $errors[] = 'Неверный email или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация - CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        .login-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 0 20px;
        }
        
        .login-card {
            background-color: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 32px;
            color: #1e293b;
            margin-bottom: 10px;
        }
        
        .login-header i {
            color: #3b82f6;
        }
        
        .login-header p {
            color: #64748b;
            font-size: 16px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            text-decoration: none;
            margin: 20px;
            font-size: 15px;
        }
        
        .back-link:hover {
            color: #3b82f6;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #475569;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .password-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 16px;
        }
        
        .login-btn {
            width: 100%;
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }
        
        .login-btn:hover {
            background-color: #2563eb;
        }
        
        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            cursor: pointer;
        }
        
        .remember-me input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .forgot-password {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            color: #64748b;
        }
        
        .register-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .error-message ul {
            margin: 0;
            padding-left: 20px;
        }
        
        @media (max-width: 768px) {
            .login-container {
                margin: 50px auto;
            }
            
            .login-card {
                padding: 30px 25px;
            }
            
            .login-options {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size) ?>">
    <div class="container">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Вернуться на главную
        </a>
        
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h1>
                        <i class="fas fa-sign-in-alt"></i>
                        Авторизация
                    </h1>
                    <p>Войдите в свой аккаунт для продолжения обучения</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Электронная почта</label>
                        <input type="email" id="email" name="email" placeholder="ivan@example.com" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <div class="password-container">
                            <input type="password" id="password" name="password" placeholder="Введите ваш пароль" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="login-options">
    <label class="remember-me">
        <input type="checkbox" name="rememberMe">
        <span>Запомнить меня</span>
    </label>
    <a href="forgot_password.php" class="forgot-password">Забыли пароль?</a>
</div>
                    
                    <button type="submit" class="login-btn">
                        Войти
                    </button>
                </form>
                
                <div class="register-link">
                    Нет аккаунта? <a href="register.php">Зарегистрироваться</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, button) {
            const passwordInput = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>