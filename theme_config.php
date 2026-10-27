// config/theme_config.php
<?php
/**
 * Глобальная тёмная тема для всего сайта
 * Чёрный фон + зелёный текст + фоновое изображение
 */

// Фоновое изображение для всего сайта (поверх чёрного фона)
$GLOBALS['global_background'] = 'url("../uploads/backgrounds/code-bg.jpg")';

// Альтернативные варианты (раскомментировать нужный)
// $GLOBALS['global_background'] = 'url("../uploads/backgrounds/matrix-bg.jpg")';
// $GLOBALS['global_background'] = 'url("../uploads/backgrounds/cyber-bg.jpg")';
// $GLOBALS['global_background'] = 'url("../uploads/backgrounds/network-bg.jpg")';

// Функция для применения стилей к body
function getBodyThemeClass() {
    $theme = $_SESSION['site_theme'] ?? 'dark'; // по умолчанию тёмная
    return 'theme-' . $theme;
}

// Переключение темы (если нужно добавить кнопку)
function toggleTheme() {
    $current = $_SESSION['site_theme'] ?? 'dark';
    $_SESSION['site_theme'] = $current === 'dark' ? 'light' : 'dark';
}

// Применение фона
function applyGlobalBackground() {
    if (isset($GLOBALS['global_background'])) {
        return 'style="background: #000000; background-image: ' . $GLOBALS['global_background'] . '; background-size: cover; background-attachment: fixed; background-position: center; background-blend-mode: overlay;"';
    }
    return 'style="background: #000000;"';
}
?>