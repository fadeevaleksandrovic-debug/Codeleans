<?php
// logout.php
session_start();

// Сброс темы при выходе
unset($_SESSION['theme']);
unset($_SESSION['site_theme']); // если используется

// Уничтожаем все данные сессии
$_SESSION = array();

// Если нужно уничтожить cookie сессии
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Уничтожаем сессию
session_destroy();

// Перенаправляем на главную страницу
header('Location: index.php');
exit();
?>