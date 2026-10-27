<?php
require_once 'config/config.php';

// Если пользователь уже авторизован, перенаправляем на главную
if ($is_logged_in) {
    header('Location: index.php');
    exit();
}

$errors = [];
$success = false;

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirmPassword'] ?? '';
    $user_type = $_POST['userType'] ?? 'student';

    // Валидация
    if (empty($full_name)) {
        $errors[] = 'Введите полное имя';
    } elseif (strlen($full_name) < 2) {
        $errors[] = 'Полное имя должно содержать минимум 2 символа';
    }

    if (empty($email)) {
        $errors[] = 'Введите email адрес';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email адрес';
    }

    if (empty($password)) {
        $errors[] = 'Введите пароль';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Пароль должен содержать минимум 8 символов';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Пароли не совпадают';
    }

    // Проверка существования email
    if (empty($errors)) {
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->execute([$email]);
        if ($check_stmt->fetch()) {
            $errors[] = 'Пользователь с таким email уже существует';
        }
    }

    // Если нет ошибок - регистрируем пользователя
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Начинаем транзакцию
        $pdo->beginTransaction();

        try {
            // Вставляем пользователя
            $insert_stmt = $pdo->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
            $insert_stmt->execute([$full_name, $email, $hashed_password]);
            $user_id = $pdo->lastInsertId();

            // Добавляем роль
            $role_id = ($user_type === 'teacher') ? 2 : 1;
            $role_stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES (?, ?, ?)");
            $role_stmt->execute([$user_id, $role_id, $user_id]);

            // Создаем записи о прогрессе для всех курсов
            $courses_stmt = $pdo->query("SELECT id FROM courses");
            $progress_stmt = $pdo->prepare("INSERT INTO user_progress (user_id, course_id, progress_percent, completed_topic) VALUES (?, ?, 0, 0)");

            while ($course = $courses_stmt->fetch()) {
                $progress_stmt->execute([$user_id, $course['id']]);
            }

            // Подтверждаем транзакцию
            $pdo->commit();

            // Сохраняем пользователя в сессию
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_level'] = $user_type;
            $_SESSION['is_teacher'] = ($user_type === 'teacher');

            $success = true;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Ошибка при регистрации: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        .auth-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 0 20px;
        }
        
        .auth-card {
            background-color: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-header h1 {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 32px;
            color: #1e293b;
            margin-bottom: 10px;
        }
        
        .auth-header i {
            color: #3b82f6;
        }
        
        .auth-header p {
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
        
        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2563eb;
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
        
        .user-type-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 25px 0;
        }
        
        .user-type-option {
            position: relative;
        }
        
        .user-type-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .user-type-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            background-color: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-type-option input[type="radio"]:checked + .user-type-label {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }
        
        .user-type-icon {
            font-size: 32px;
            color: #3b82f6;
            margin-bottom: 10px;
        }
        
        .user-type-label h4 {
            margin: 0;
            color: #1e293b;
            font-size: 16px;
        }
        
        .auth-btn {
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
        }
        
        .auth-btn:hover {
            background-color: #2563eb;
        }
        
        .auth-link {
            text-align: center;
            margin-top: 25px;
            color: #64748b;
        }
        
        .auth-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-link a:hover {
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
        
        .success-message {
            background-color: #dcfce7;
            color: #16a34a;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            font-size: 18px;
        }
        
        .success-message i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #22c55e;
        }
        
        .success-message h2 {
            margin: 10px 0;
            color: #166534;
        }
        
        .success-message p {
            margin: 10px 0 20px;
            color: #166534;
        }
        
        .success-message .btn {
            display: inline-block;
            background-color: #22c55e;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        
        .success-message .btn:hover {
            background-color: #16a34a;
        }
        
        @media (max-width: 768px) {
            .auth-container {
                margin: 50px auto;
            }
            
            .auth-card {
                padding: 30px 25px;
            }
            
            .user-type-selector {
                grid-template-columns: 1fr;
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
        
        <div class="auth-container">
            <div class="auth-card">
                <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <h2>Регистрация прошла успешно!</h2>
                        <p>Добро пожаловать, <?php echo htmlspecialchars($full_name); ?>!</p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home"></i>Перейти на главную</a>
                    </div>
                <?php else: ?>
                    <div class="auth-header">
                        <h1>
                            <i class="fas fa-user-plus"></i>
                            Регистрация
                        </h1>
                        <p>Создайте аккаунт для начала обучения</p>
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
                    
                    <form method="POST" action="" class="auth-form" id="registerForm">
                        <div class="form-group">
                            <label for="fullName">Полное имя</label>
                            <input type="text" id="fullName" name="fullName" placeholder="Иван Иванов" required 
                                   value="<?php echo isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Электронная почта</label>
                            <input type="email" id="email" name="email" placeholder="ivan@example.com" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <!-- Выбор типа пользователя -->
                        <div class="user-type-selector">
                            <div class="user-type-option">
                                <input type="radio" id="student" name="userType" value="student" checked>
                                <label for="student" class="user-type-label">
                                    <div class="user-type-icon">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <h4>Студент</h4>
                                </label>
                            </div>
                            
                            <div class="user-type-option">
                                <input type="radio" id="teacher" name="userType" value="teacher">
                                <label for="teacher" class="user-type-label">
                                    <div class="user-type-icon">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                    </div>
                                    <h4>Преподаватель</h4>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Пароль</label>
                            <div class="password-container">
                                <input type="password" id="password" name="password" placeholder="Не менее 8 символов" required minlength="8">
                                <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword">Подтверждение пароля</label>
                            <div class="password-container">
                                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Повторите пароль" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="auth-btn">
                            Зарегистрироваться
                        </button>
                    </form>
                    
                    <div class="auth-link">
                        Уже есть аккаунт? <a href="login.php">Войти</a>
                    </div>
                <?php endif; ?>
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

        // Клиентская валидация паролей
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Пароли не совпадают!');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Пароль должен содержать минимум 8 символов!');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>