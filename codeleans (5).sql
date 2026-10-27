-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Июн 24 2026 г., 18:00
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `codeleans`
--

-- --------------------------------------------------------

--
-- Структура таблицы `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `admin`
--

INSERT INTO `admin` (`id`, `email`, `password`, `created_at`) VALUES
(1, 'admin@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-05-07 18:02:04');

-- --------------------------------------------------------

--
-- Структура таблицы `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `language` varchar(20) DEFAULT 'javascript',
  `order_num` int(11) DEFAULT 0,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `starter_code` text DEFAULT NULL,
  `expected_output` text DEFAULT NULL,
  `hints` text DEFAULT NULL,
  `test_type` enum('console','return','both') DEFAULT 'console',
  `solution_code` text DEFAULT NULL,
  `test_cases` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `assignments`
--

INSERT INTO `assignments` (`id`, `topic_id`, `language`, `order_num`, `title`, `description`, `starter_code`, `expected_output`, `hints`, `test_type`, `solution_code`, `test_cases`, `created_at`) VALUES
(1, 7, 'javascript', 0, 'Первая программа на JavaScript', '<h3>Задание: Напишите свою первую программу!</h3>\r\n<p>Создайте функцию <code>sayHello()</code>, которая выводит сообщение \"Привет, мир!\" в консоль.</p>\r\n<p>Требования:</p>\r\n<ul>\r\n    <li>Функция должна называться <code>sayHello</code></li>\r\n    <li>Функция не принимает параметров</li>\r\n    <li>Функция должна выводить в консоль строку \"Привет, мир!\"</li>\r\n</ul>', '// Напишите свою первую программу здесь\r\nfunction sayHello() {\r\n    // Ваш код здесь\r\n    \r\n}\r\n\r\n// Не удаляйте вызов функции ниже (он нужен для проверки)\r\nsayHello();', 'Привет, мир!', NULL, 'console', 'function sayHello() {\n    console.log(\"Привет, мир!\");\n}', '[{\"input\":[],\"expected\":\"Привет, мир!\"}]', '2026-05-24 16:19:09'),
(2, 17, 'javascript', 0, 'Работа с переменными', 'Создайте переменную num и присвойте ей значение 123. Выведите значение этой переменной на экран с помощью функции console.log().', '// ========== Задание ==========\r\n// Создайте переменную num и присвойте ей значение 123\r\n// Затем выведите её значение через console.log()\r\n// ==============================\r\n\r\nlet num;\r\n\r\n// Ваш код здесь\r\n\r\n\r\n// Проверка (не удаляйте):\r\nif (typeof num !== \"undefined\") {\r\n    console.log(\"Переменная num = \" + num);\r\n}', '123', NULL, 'console', 'let num = 123;\r\nconsole.log(num);', NULL, '2026-05-24 18:28:47'),
(3, 8, 'javascript', 1, 'Задание 1: Сумма чисел', 'Создайте переменную a. Запишите в нее сумму чисел 1, 2 и 3. С помощью функции console.log выведите на экран содержимое переменной a.', '// ========== Задание 1 ==========\r\n// Создайте переменную a и запишите в нее сумму чисел 1, 2 и 3\r\n// Затем выведите значение a через console.log()\r\n// ================================\r\n\r\n// Ваш код здесь\r\n\r\n\r\n// Проверка (не удаляйте):\r\n', '6', NULL, 'console', 'let a = 1 + 2 + 3;\nconsole.log(a);', NULL, '2026-05-24 18:35:26'),
(4, 8, 'javascript', 2, 'Задание 2: Арифметические операции', 'Создайте переменную a со значением 10 и переменную b со значением 2. Выведите на экран их сумму, разность, произведение и частное с помощью console.log (каждый результат в отдельном вызове).', '// ========== Задание 2 ==========\r\n// Создайте переменные a=10 и b=2\r\n// Выведите сумму, разность, произведение и частное\r\n// ================================\r\n\r\n// Ваш код здесь\r\n\r\n\r\n// Проверка (не удаляйте):\r\n', '12\n8\n20\n5', NULL, 'console', 'let a = 10;\nlet b = 2;\nconsole.log(a + b);\nconsole.log(a - b);\nconsole.log(a * b);\nconsole.log(a / b);', NULL, '2026-05-24 18:35:26'),
(5, 8, 'javascript', 3, 'Задание 3: Сумма с сохранением в переменную', 'Создайте переменную c со значением 10 и переменную d со значением 5. Просуммируйте их, а результат присвойте переменной result. Выведите на экран значение переменной result через console.log.', '// ========== Задание 3 ==========\r\n// Создайте переменные c=10 и d=5\r\n// Создайте переменную result = c + d\r\n// Выведите result через console.log()\r\n// ================================\r\n\r\n// Ваш код здесь\r\n\r\n\r\n// Проверка (не удаляйте):\r\n', '15', NULL, 'console', 'let c = 10;\nlet d = 5;\nlet result = c + d;\nconsole.log(result);', NULL, '2026-05-24 18:35:26'),
(6, 8, 'javascript', 4, 'Задание 4: Цепочка вычислений', 'Создайте переменную a со значением 10 и переменную b со значением 5. Отнимите от a переменную b и результат присвойте переменной c. Затем создайте переменную d, присвойте ей значение 7. Сложите c и d, результат запишите в переменную result. Выведите на экран значение result через console.log.', '// ========== Задание 4 ==========\r\n// a = 10, b = 5\r\n// c = a - b\r\n// d = 7\r\n// result = c + d\r\n// Выведите result\r\n// ================================\r\n\r\n// Ваш код здесь\r\n\r\n\r\n// Проверка (не удаляйте):\r\n', '12', NULL, 'console', 'let a = 10;\nlet b = 5;\nlet c = a - b;\nlet d = 7;\nlet result = c + d;\nconsole.log(result);', NULL, '2026-05-24 18:35:26'),
(7, 9, 'javascript', 1, 'Задание 1: Вычисление выражения', '<h3>📝 Задание: Вычисление выражения</h3>\r\n<p>Вычислите значение выражения <code>5 + 5 * 3</code> и сохраните результат в переменную <code>a</code>. Выведите результат в консоль.</p>\r\n<p><strong>Ожидаемый результат:</strong> 20</p>', '// ========== Задание 1 ==========\r\n// Вычислите: 5 + 5 * 3\r\n// Результат сохраните в переменную a\r\n// Выведите переменную a в консоль (console.log)\r\n// ================================\r\n\r\nlet a;\r\n\r\n// Ваш код здесь\r\n\r\n\r\nconsole.log(a);', '20', NULL, 'console', 'let a = 5 + 5 * 3;\nconsole.log(a);', NULL, '2026-05-24 18:45:35'),
(8, 9, 'javascript', 2, 'Задание 2: Деление и сложение', '<h3>📝 Задание: Деление и сложение</h3>\r\n<p>Вычислите значение выражения <code>8 / 2 + 2</code> и сохраните результат в переменную <code>a</code>. Выведите результат в консоль.</p>\r\n<p><strong>Ожидаемый результат:</strong> 6</p>', '// ========== Задание 2 ==========\r\n// Вычислите: 8 / 2 + 2\r\n// Результат сохраните в переменную a\r\n// Выведите переменную a в консоль (console.log)\r\n// ================================\r\n\r\nlet a;\r\n\r\n// Ваш код здесь\r\n\r\n\r\nconsole.log(a);', '6', NULL, 'console', 'let a = 8 / 2 + 2;\nconsole.log(a);', NULL, '2026-05-24 18:45:35'),
(9, 9, 'javascript', 3, 'Задание 3: Операции слева направо', '<h3>📝 Задание: Операции слева направо</h3>\r\n<p>Вычислите значение выражения <code>8 / 2 * 2</code> и сохраните результат в переменную <code>a</code>. Выведите результат в консоль.</p>\r\n<p><strong>Ожидаемый результат:</strong> 8</p>\r\n<p><em>Подсказка: умножение и деление выполняются слева направо</em></p>', '// ========== Задание 3 ==========\r\n// Вычислите: 8 / 2 * 2\r\n// Результат сохраните в переменную a\r\n// Выведите переменную a в консоль (console.log)\r\n// ================================\r\n\r\nlet a;\r\n\r\n// Ваш код здесь\r\n\r\n\r\nconsole.log(a);', '8', NULL, 'console', 'let a = 8 / 2 * 2;\nconsole.log(a);', NULL, '2026-05-24 18:45:35'),
(10, 9, 'javascript', 4, 'Задание 4: Выражение со скобками', '<h3>📝 Задание: Выражение со скобками</h3>\r\n<p>Вычислите значение выражения <code>(2 + 3) * (2 + 3)</code> и сохраните результат в переменную <code>a</code>. Выведите результат в консоль.</p>\r\n<p><strong>Ожидаемый результат:</strong> 25</p>', '// ========== Задание 4 ==========\r\n// Вычислите: (2 + 3) * (2 + 3)\r\n// Результат сохраните в переменную a\r\n// Выведите переменную a в консоль (console.log)\r\n// ================================\r\n\r\nlet a;\r\n\r\n// Ваш код здесь\r\n\r\n\r\nconsole.log(a);', '25', NULL, 'console', 'let a = (2 + 3) * (2 + 3);\nconsole.log(a);', NULL, '2026-05-24 18:45:35'),
(11, 9, 'javascript', 5, 'Задание 5: Вложенные скобки', '<h3>📝 Задание: Вложенные скобки</h3>\r\n<p>Вычислите значение выражения <code>2 * (2 + 4 * (3 + 1))</code> и сохраните результат в переменную <code>a</code>. Выведите результат в консоль.</p>\r\n<p><strong>Ожидаемый результат:</strong> 36</p>', '// ========== Задание 5 ==========\r\n// Вычислите: 2 * (2 + 4 * (3 + 1))\r\n// Результат сохраните в переменную a\r\n// Выведите переменную a в консоль (console.log)\r\n// ================================\r\n\r\nlet a;\r\n\r\n// Ваш код здесь\r\n\r\n\r\nconsole.log(a);', '36', NULL, 'console', 'let a = 2 * (2 + 4 * (3 + 1));\nconsole.log(a);', NULL, '2026-05-24 18:45:35'),
(27, 10, 'javascript', 1, 'Задание 1: Сумма десятичных дробей', '<h3>📝 Задание 1: Сумма десятичных дробей</h3>\r\n<p>Запишите в переменную <code>a</code> число <strong>1.5</strong>, а в переменную <code>b</code> - число <strong>0.75</strong>. Найдите сумму значений этих переменных и выведите ее в консоль.</p>\r\n<p><strong>Ожидаемый результат:</strong> 2.25</p>', '// ========== Задание 1 ==========\r\n// Создайте переменную a = 1.5\r\n// Создайте переменную b = 0.75\r\n// Выведите сумму a + b в консоль (console.log)\r\n// ================================\r\n\r\nlet a;\r\nlet b;\r\n\r\n// Ваш код здесь\r\n\r\n\r\nconsole.log(a + b);', '2.25', NULL, 'console', 'let a = 1.5;\nlet b = 0.75;\nconsole.log(a + b);', NULL, '2026-05-24 18:55:13'),
(28, 10, 'javascript', 2, 'Задание 2: Отрицательное число', '<h3>📝 Задание 2: Отрицательное число</h3>\r\n<p>Создайте переменную <code>a</code> со значением <strong>-100</strong>. Выведите это значение в консоль.</p>\r\n<p><strong>Ожидаемый результат:</strong> -100</p>', '// ========== Задание 2 ==========\r\n// Создайте переменную a = -100\r\n// Выведите a в консоль (console.log)\r\n// ================================\r\n\r\nlet a;\r\n\r\n// Ваш код здесь\r\n\r\n\r\nconsole.log(a);', '-100', NULL, 'console', 'let a = -100;\nconsole.log(a);', NULL, '2026-05-24 18:55:13'),
(29, 10, 'javascript', 3, 'Задание 3: Число с обратным знаком', '<h3>📝 Задание 3: Число с обратным знаком</h3>\r\n<p>Создайте переменную <code>a</code> и запишите в нее любое положительное или отрицательное число (например, 50 или -30). Выведите в консоль это число с обратным знаком.</p>\r\n<p><strong>Пример:</strong> если a = 50, то должно вывести -50; если a = -30, то должно вывести 30</p>', '// ========== Задание 3 ==========\r\n// Создайте переменную a (выберите любое число)\r\n// Выведите в консоль число a с обратным знаком\r\n// Подсказка: используйте -a\r\n// ================================\r\n\r\nlet a = 50;\r\n\r\n// Ваш код здесь\r\n\r\n\r\nconsole.log(-a);', '-50', NULL, 'console', 'let a = 50;\nconsole.log(-a);', NULL, '2026-05-24 18:55:13'),
(30, 18, 'php', 1, 'Первая программа на PHP', '<p>Напишите PHP программу, которая выводит на экран строку <strong>\"Привет, мир!\"</strong>.</p>\r\n<p>Требования:</p>\r\n<ul>\r\n<li>Используйте теги <code>&lt;?php ?&gt;</code></li>\r\n<li>Используйте команду <code>echo</code> для вывода</li>\r\n<li>Строка должна быть точно: \"Привет, мир!\"</li>\r\n</ul>', '<?php\r\n// Напишите ваш код здесь\r\n\r\n?>', 'Привет, мир!', NULL, 'console', NULL, NULL, '2026-05-31 09:38:20'),
(31, 18, 'php', 2, 'Использование var_dump', '<p>Напишите программу, которая с помощью <code>var_dump()</code> выводит число <strong>12345</strong> и строку <strong>\"Hello\"</strong>.</p>\r\n<p>Требования:</p>\r\n<ul>\r\n<li>Используйте var_dump() для вывода числа</li>\r\n<li>Используйте var_dump() для вывода строки</li>\r\n<li>Вывод должен быть в указанном порядке</li>\r\n</ul>', '<?php\r\n// Выведите число 12345 через var_dump\r\n// Выведите строку \"Hello\" через var_dump\r\n?>', 'int(12345)\nstring(5) \"Hello\"', NULL, 'console', NULL, NULL, '2026-05-31 09:38:20'),
(32, 19, 'php', 1, 'Настройка вывода ошибок', '<p>Напишите PHP код, который включает вывод ВСЕХ ошибок и предупреждений.</p>\r\n<p>Требования:</p>\r\n<ul>\r\n<li>Используйте error_reporting() с правильным параметром</li>\r\n<li>Используйте ini_set() для включения display_errors</li>\r\n<li>После настройки выведите строку \"Готово\"</li>\r\n</ul>', '<?php\r\n// Включите вывод всех ошибок\r\n// Выведите \"Готово\"\r\n?>', 'Готово', NULL, 'console', NULL, NULL, '2026-05-31 09:38:20'),
(33, 19, 'php', 2, 'Отключение вывода ошибок', '<p>Напишите PHP код, который отключает вывод ошибок (для production сайта).</p>\r\n<p>Требования:</p>\r\n<ul>\r\n<li>Используйте error_reporting(0)</li>\r\n<li>Используйте ini_set() для отключения display_errors</li>\r\n<li>Выведите \"Production mode\"</li>\r\n</ul>', '<?php\r\n// Отключите вывод ошибок\r\n// Выведите \"Production mode\"\r\n?>', 'Production mode', NULL, 'console', NULL, NULL, '2026-05-31 09:38:20'),
(34, 20, 'php', 1, 'Однострочные комментарии', '<p>Напишите PHP код, который содержит:</p>\r\n<ul>\r\n<li>Однострочный комментарий перед выводом</li>\r\n<li>Команду echo для вывода \"Hello\"</li>\r\n<li>Комментарий после вывода с пояснением</li>\r\n</ul>', '<?php\r\n// Напишите комментарий здесь\r\n\r\n// Здесь должен быть вывод\r\n\r\n// Напишите комментарий после вывода\r\n?>', 'Hello', NULL, 'console', NULL, NULL, '2026-05-31 09:38:20'),
(35, 20, 'php', 2, 'Многострочные комментарии', '<p>Напишите PHP код, который:</p>\r\n<ul>\r\n<li>Содержит многострочный комментарий (3+ строк)</li>\r\n<li>Объявляет переменную $name со значением \"PHP\"</li>\r\n<li>Выводит значение переменной</li>\r\n</ul>', '<?php\r\n/*\r\n\r\n*/\r\n$name = \"PHP\";\r\n\r\n?>', 'PHP', NULL, 'console', NULL, NULL, '2026-05-31 09:38:20'),
(36, 21, 'php', 1, 'Создание и вывод переменных', '<p>Создайте переменную <code>$num</code> со значением 123 и выведите её.</p>', '<?php\r\n// Создайте переменную $num со значением 123\r\n// Выведите её\r\n?>', '123', NULL, 'console', NULL, NULL, '2026-05-31 09:38:20'),
(37, 21, 'php', 2, 'Изменение значения переменной', '<p>Создайте переменную <code>$a</code> со значением 10, выведите её. Затем измените значение на 20 и снова выведите.</p>', '<?php\r\n// Создайте $a = 10 и выведите\r\n// Измените на 20 и выведите\r\n?>', '10\n20', NULL, 'console', NULL, NULL, '2026-05-31 09:38:20'),
(38, 21, 'php', 3, 'Арифметика с переменными', '<p>Создайте переменные <code>$a = 10</code> и <code>$b = 2</code>. Выведите их:</p>\r\n<ul>\r\n<li>Сумму</li>\r\n<li>Разность</li>\r\n<li>Произведение</li>\r\n<li>Частное</li>\r\n</ul>\r\n<p>Каждый результат на новой строке.</p>', '<?php\r\n$a = 10;\r\n$b = 2;\r\n// Выведите сумму, разность, произведение, частное\r\n?>', '12\n8\n20\n5', NULL, 'console', NULL, NULL, '2026-05-31 09:38:20'),
(39, 22, 'php', 1, 'Приоритет операций', '<p>Вычислите значение выражения <code>5 + 5 * 3</code> и выведите результат.</p>', '<?php\r\n// Вычислите 5 + 5 * 3\r\n?>', '20', NULL, 'console', NULL, NULL, '2026-05-31 09:38:20'),
(40, 22, 'php', 2, 'Операции слева направо', '<p>Вычислите значение выражения <code>8 / 2 * 4</code> и выведите результат.</p>', '<?php\r\n// Вычислите 8 / 2 * 4\r\n?>', '16', NULL, 'console', NULL, NULL, '2026-05-31 09:38:20'),
(41, 22, 'php', 3, 'Использование скобок', '<p>Вычислите значение выражения <code>2 * (2 + 3)</code> и выведите результат.</p>', '<?php\r\n// Вычислите 2 * (2 + 3)\r\n?>', '10', NULL, 'console', NULL, NULL, '2026-05-31 09:38:20'),
(42, 22, 'php', 4, 'Вложенные скобки', '<p>Вычислите значение выражения <code>2 * (2 + 4 * (3 + 1))</code> и выведите результат.</p>', '<?php\r\n// Вычислите 2 * (2 + 4 * (3 + 1))\r\n?>', '36', NULL, 'console', NULL, NULL, '2026-05-31 09:38:20'),
(44, 36, 'python', 1, 'Вывод имени', 'Выведите строку с вашим именем (например, \"Иван\").', '# Ваш код', 'Иван', 'print(\"Иван\")', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(45, 36, 'python', 2, 'Вывод числа', 'Выведите любое число (например, 42).', '# Ваш код', '42', 'print(42)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(47, 36, 'python', 3, 'Многострочный комментарий', 'Заключите три строки в тройные кавычки.', 'test1 = \'abc\'\r\ntest2 = \'123\'\r\ntest3 = 123', 'OK', 'Используйте \"\"\" ... \"\"\"', 'console', '', NULL, '2026-06-12 07:48:47'),
(48, 37, 'python', 1, 'Строковая переменная', 'Создайте переменную со значением \'abc\'.', '# Ваш код', 'abc', 's = \'abc\'', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(49, 37, 'python', 2, 'Числовая переменная', 'Создайте переменную с двузначным числом (25).', '# Ваш код', '25', 'num = 25', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(50, 37, 'python', 3, 'Вывод строки', 'Создайте message = \"Привет\" и выведите.', 'message = \"Привет\"\n# код', 'Привет', 'print(message)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(51, 37, 'python', 4, 'Вывод числа', 'Создайте number = 100 и выведите.', 'number = 100\n# код', '100', 'print(number)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(52, 38, 'python', 1, 'Сумма 1+2+3', '', '', '6', 'print(1+2+3)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(53, 38, 'python', 2, '6-4', '', '', '2', 'print(6-4)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(54, 38, 'python', 3, '7*3', '', '', '21', 'print(7*3)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(55, 38, 'python', 4, '8/3', '', '', '2.6666666666666665', 'print(8/3)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(56, 38, 'python', 5, '10//3', '', '', '3', 'print(10//3)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(57, 38, 'python', 6, 'Сумма переменных (5+3)', 'a=5,b=3, выведите a+b', 'a=5;b=3', '8', 'print(a+b)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(58, 38, 'python', 7, 'Разность (10-6)', 'a=10,b=6, выведите a-b', 'a=10;b=6', '4', 'print(a-b)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(59, 38, 'python', 8, 'Произведение (7*3)', 'a=7,b=3', 'a=7;b=3', '21', 'print(a*b)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(60, 38, 'python', 9, 'Частное (15/6)', 'a=15,b=6', 'a=15;b=6', '2.5', 'print(a/b)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(61, 38, 'python', 10, 'Целочисленное деление (15//6)', 'a=15,b=6', 'a=15;b=6', '2', 'print(a//b)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(62, 39, 'python', 1, 'Инкремент/декремент', 'Перепишите: num=4; num=num+1; num=num-1; num=num+1; выведите результат.', 'num=4\n# ваш код', '5', 'num+=1; num-=1; num+=1; print(num)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(63, 39, 'python', 2, '2+3*4', '', '', '14', 'print(2+3*4)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(64, 39, 'python', 3, '10-6/3', '', '', '8.0', 'print(10-6/3)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(65, 39, 'python', 4, '10-6*3-5', '', '', '-13', 'print(10-6*3-5)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(66, 39, 'python', 5, '6*2/4', '', '', '3.0', 'print(6*2/4)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(67, 39, 'python', 6, '6/2*3', '', '', '9.0', 'print(6/2*3)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(68, 39, 'python', 7, '18/2/3*2', '', '', '6.0', 'print(18/2/3*2)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(69, 40, 'python', 1, '(2+3)*4', '', '', '20', 'print((2+3)*4)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(70, 40, 'python', 2, '(10-6)/3', '', '', '1.3333333333333333', 'print((10-6)/3)', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(71, 40, 'python', 3, '10-6*(3-5)', '', '', '22', 'print(10-6*(3-5))', 'console', NULL, NULL, '2026-06-12 07:48:47'),
(72, 40, 'python', 4, '4-1+(5+6*(2+7))', '', '', '62', 'print(4-1+(5+6*(2+7)))', 'console', NULL, NULL, '2026-06-12 07:48:47');

-- --------------------------------------------------------

--
-- Структура таблицы `assignment_progress`
--

CREATE TABLE `assignment_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `user_code` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `attempts` int(11) DEFAULT 0,
  `last_submitted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `topic_completed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `assignment_progress`
--

INSERT INTO `assignment_progress` (`id`, `user_id`, `assignment_id`, `user_code`, `is_correct`, `attempts`, `last_submitted_at`, `topic_completed`) VALUES
(1, 8, 29, '// ========== Задание 3 ==========\r\n// Создайте переменную a (выберите любое число)\r\n// Выведите в консоль число a с обратным знаком\r\n// Подсказка: используйте -a\r\n// ================================\r\n\r\nlet a = 50;\r\n\r\n// Ваш код здесь\r\n\r\n\r\nconsole.log(-a);', 0, 1, '2026-05-30 07:23:20', 0),
(2, 9, 30, '<?php\r\necho \"Привет, мир!\";\r\n?>', 1, 2, '2026-05-31 09:58:11', 0),
(5, 8, 31, '<?php\r\nvar_dump(12345);\r\nvar_dump(\"Hello\");\r\n?>', 1, 1, '2026-06-02 18:07:15', 0),
(7, 13, 3, '// ========== Задание 1 ==========\r\n// Создайте переменную a и запишите в нее сумму чисел 1, 2 и 3\r\n// Затем выведите значение a через alert()\r\n// ================================\r\n\r\nlet a = 1+2+3;\r\nalert(a);', 1, 8, '2026-06-13 11:09:15', 0),
(14, 13, 4, '// ========== Задание 2 ==========\r\n// Создайте переменные a=10 и b=2\r\n// Выведите сумму, разность, произведение и частное\r\n// ================================\r\n\r\na = 10;\r\nb = 2;\r\nalert(a+b);\r\nalert(a-b);\r\nalert(a*b);\r\nalert(a/b);\r\n\r\n// Проверка (не удаляйте):\r\nconsole.log(\"Проверка задания 2\");', 0, 11, '2026-06-13 11:59:04', 0),
(25, 13, 44, 'print(\'Иван\')', 1, 1, '2026-06-13 11:15:46', 0),
(26, 13, 45, 'print(\'42\')', 1, 1, '2026-06-13 11:16:05', 0),
(29, 13, 47, 'test1 = \'abc\'\r\ntest2 = \'123\'\r\ntest3 = \'123\'', 0, 1, '2026-06-13 11:17:08', 0),
(31, 13, 51, 'number = 100', 0, 1, '2026-06-14 07:37:39', 0),
(32, 13, 30, '<?php\r\necho \"Привет, мир!\";\r\n?>', 0, 2, '2026-06-14 07:50:07', 0),
(34, 13, 50, 'message = \"Привет\"\r\n# код', 0, 1, '2026-06-14 07:57:40', 0),
(37, 12, 1, '// Напишите свою первую программу здесь\r\nfunction sayHello() {\r\n    console.log(\"Привет, мир!\");\r\n}\r\n// Не удаляйте вызов функции ниже (он нужен для проверки)\r\nsayHello();', 1, 1, '2026-06-20 07:34:51', 0),
(38, 12, 6, '// ========== Задание 4 ==========\r\n// a = 10, b = 5\r\n// c = a - b\r\n// d = 7\r\n// result = c + d\r\n// Выведите result\r\n// ================================\r\n\r\nlet a = 10;\r\nlet b = 5;\r\nlet c = a - b;\r\nlet d = 7;\r\nlet result = c + d;\r\nconsole.log(result);\r\n', 1, 2, '2026-06-21 09:23:57', 0),
(39, 12, 4, '// ========== Задание 2 ==========\r\n// Создайте переменные a=10 и b=2\r\n// Выведите сумму, разность, произведение и частное\r\n// ================================\r\n\r\nlet a = 10;\r\nlet b = 2;\r\nconsole.log(a + b);\r\nconsole.log(a - b);\r\nconsole.log(a * b);\r\nconsole.log(a / b);\r\n\r\n// Проверка (не удаляйте):', 1, 15, '2026-06-21 09:23:07', 0),
(41, 12, 5, '// ========== Задание 3 ==========\r\n// Создайте переменные c=10 и d=5\r\n// Создайте переменную result = c + d\r\n// Выведите result через alert()\r\n// ================================\r\n\r\nlet c = 10;\r\nlet d = 5;\r\nlet result = c + d;\r\nconsole.log(result);\r\n\r\n', 1, 4, '2026-06-21 09:23:25', 0),
(59, 12, 3, '// ========== Задание 1 ==========\r\n// Создайте переменную a и запишите в нее сумму чисел 1, 2 и 3\r\n// Затем выведите значение a через console.log()\r\n// ================================\r\n\r\n// Ваш код здесь\r\n\r\nlet a = 1+2+3;\r\nconsole.log(a);\r\n// Проверка (не удаляйте):\r\n', 1, 1, '2026-06-21 09:28:52', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `assignment_submissions`
--

CREATE TABLE `assignment_submissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `user_code` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `feedback` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `assignment_submissions`
--

INSERT INTO `assignment_submissions` (`id`, `user_id`, `assignment_id`, `user_code`, `is_correct`, `feedback`, `submitted_at`) VALUES
(1, 12, 1, 'function sayHello() {\r\n    console.log(\"Привет, мир!\");\r\n}\r\nsayHello();', 1, '✅ Поздравляю! Задание выполнено правильно! Функция sayHello() выводит \"Привет, мир!\" в консоль.', '2026-05-24 16:30:45'),
(7, 13, 1, 'function sayHello() {\r\n    console.log(\"Привет, мир!\");\r\n}\r\nsayHello();', 1, '✅ Поздравляю! Задание выполнено правильно!\n\n📊 Результаты проверки:\n✓ Функция sayHello() объявлена правильно\n✓ Вывод в консоль работает корректно\n✓ Сообщение \'Привет, мир!\' выводится верно\n✓ Вызов функции присутствует\n\n\n🎉 Отличная работа! Продолжайте в том же духе!', '2026-05-24 16:35:05'),
(27, 11, 1, '// Напишите свою первую программу здесь\r\nfunction sayHello() {\r\n    console.log(\\\"Привет, мир!\\\");\r\n}\r\n\r\n// Не удаляйте вызов функции ниже (он нужен для проверки)\r\nsayHello();', 1, '✅ ЗАДАНИЕ ВЫПОЛНЕНО ВЕРНО!\n\n📊 Результат выполнения:\n────────────────────────────────────────\n✓ Функция sayHello() объявлена правильно\n✓ console.log() находится внутри функции\n✓ Сообщение \'Привет, мир!\' выводится верно\n✓ Вызов функции присутствует\n✓ Синтаксис кода верный\n\n★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★\n🎉 Отличная работа! Продолжайте в том же духе!\n★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★', '2026-05-24 17:51:48'),
(29, 14, 1, '// Напишите свою первую программу здесь\r\nfunction sayHello() {\r\n    console.log(\"Привет, мир!\");\r\n    \r\n}\r\n\r\n// Не удаляйте вызов функции ниже (он нужен для проверки)\r\nsayHello();', 1, '✅ ЗАДАНИЕ ВЫПОЛНЕНО ВЕРНО!\n\n📊 Результат выполнения:\n────────────────────────────────────────\n✓ Функция sayHello() объявлена правильно\n✓ console.log() находится внутри функции\n✓ Сообщение \'Привет, мир!\' выводится верно\n✓ Вызов функции присутствует\n✓ Синтаксис кода верный\n\n★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★\n🎉 Отличная работа! Продолжайте в том же духе!\n★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★', '2026-05-25 04:15:08'),
(30, 10, 27, '// ========== Задание 1 ==========\r\n// Создайте переменную a = 1.5\r\n// Создайте переменную b = 0.75\r\n// Выведите сумму a + b в консоль (console.log)\r\n// ================================\r\n\r\nlet a = 1.5;\r\nlet b = 0.75;\r\n\r\nconsole.log(a + b);', 0, '❌ ЗАДАНИЕ НЕ ВЫПОЛНЕНО\n\n🔍 Найдены ошибки:\n──────────────────────────────────────────────────\n1. ❌ Отсутствует ключевое слово \"function\"\n2. ❌ Функция \"sayHello\" не найдена\n4. ❌ В console.log() текст должен быть в кавычках\n6. ❌ Отсутствует вызов функции sayHello()\n\n──────────────────────────────────────────────────\n💡 ПОДСКАЗКИ:\n• Создайте функцию: function sayHello() { ... }\n• Используйте двойные (\"\") или одинарные (\'\') кавычки\n• Добавьте в конце кода: sayHello();\n\n══════════════════════════════════════════════════\n✅ ПРАВИЛЬНОЕ РЕШЕНИЕ:\n┌────────────────────────────────────────┐\n│ function sayHello() {                  │\n│     console.log(\"Привет, мир!\");       │\n│ }                                      │\n│ sayHello();                            │\n└────────────────────────────────────────┘\n', '2026-05-25 18:33:21'),
(35, 10, 3, '// ========== Задание 1 ==========\r\n// Создайте переменную a и запишите в нее сумму чисел 1, 2 и 3\r\n// Затем выведите значение a через alert()\r\n// ================================\r\n\r\nlet a = 1 + 2 + 3;\r\nalert(a);\r\n\r\n\r\n// Проверка (не удаляйте):\r\nconsole.log(\"Проверка задания 1\");', 1, '✅ Задание выполнено!', '2026-05-25 18:19:39'),
(37, 10, 7, '// ========== Задание 1 ==========\r\n// Вычислите: 5 + 5 * 3\r\n// Результат сохраните в переменную a\r\n// Выведите переменную a в консоль (console.log)\r\n// ================================\r\n\r\nlet a = 5 + 5 * 3;\r\nalert(a);\r\n\r\nconsole.log(a);', 1, '✅ Задание выполнено! 5 + 5 * 3 = 20', '2026-05-25 18:32:37');

-- --------------------------------------------------------

--
-- Структура таблицы `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `certificate_code` varchar(50) NOT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `certificates`
--

INSERT INTO `certificates` (`id`, `user_id`, `course_id`, `certificate_code`, `issued_at`, `file_path`) VALUES
(2, 8, 2, 'BCB71BFC9C9C83B6', '2026-05-25 16:44:55', 'certificates/certificate_8_2.html'),
(3, 8, 1, 'F77F9DA44BCCB29D', '2026-06-01 18:37:38', 'certificates/certificate_8_1.html'),
(4, 8, 3, 'DFB7139260E90C28', '2026-06-02 14:14:55', 'certificates/certificate_8_3.html'),
(5, 10, 1, '39DCD2D5DE4A7DFD', '2026-05-31 09:42:52', 'certificates/certificate_10_1.html'),
(6, 13, 1, '55B22C28BB2F30D9', '2026-06-16 16:29:42', 'certificates/certificate_13_1.html');

-- --------------------------------------------------------

--
-- Структура таблицы `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `course_id`, `user_id`, `message`, `file_path`, `file_type`, `created_at`) VALUES
(3, 1, 14, 'Здраствуйте', NULL, NULL, '2026-05-23 09:00:44'),
(4, 1, 14, 'Приветик', NULL, NULL, '2026-05-23 09:00:56'),
(5, 1, 13, 'Привет и хай', NULL, NULL, '2026-05-23 09:01:38'),
(6, 1, 9, 'привет', NULL, NULL, '2026-06-01 16:57:44'),
(7, 2, 15, 'Привет', NULL, NULL, '2026-06-12 12:40:49'),
(8, 2, 15, 'пгргшргигишг', NULL, NULL, '2026-06-12 12:41:00');

-- --------------------------------------------------------

--
-- Структура таблицы `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_name` varchar(50) NOT NULL,
  `course_description` text DEFAULT NULL,
  `total_topic` int(11) NOT NULL DEFAULT 0,
  `total_hours` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `is_public` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `courses`
--

INSERT INTO `courses` (`id`, `course_name`, `course_description`, `total_topic`, `total_hours`, `created_by`, `is_published`, `is_public`) VALUES
(1, 'PHP', 'Изучите серверный язык программирования для веб-разработки. Основы, работа с базами данных.', 5, 12, NULL, 1, 0),
(2, 'JavaScript', 'Освойте язык программирования для интерактивных веб-приложений. От основ до продвинутых концепций.', 5, 18, NULL, 1, 0),
(3, 'Python', 'Универсальный язык для веб-разработки, анализа данных, машинного обучения и автоматизации.', 5, 15, NULL, 1, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `course_teachers`
--

CREATE TABLE `course_teachers` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `course_teachers`
--

INSERT INTO `course_teachers` (`id`, `course_id`, `teacher_id`, `assigned_at`) VALUES
(1, 2, 10, '2026-05-07 18:08:09'),
(2, 1, 9, '2026-05-07 18:12:16'),
(3, 3, 13, '2026-05-23 08:59:44');

-- --------------------------------------------------------

--
-- Структура таблицы `private_messages`
--

CREATE TABLE `private_messages` (
  `id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL,
  `to_user_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `private_messages`
--

INSERT INTO `private_messages` (`id`, `from_user_id`, `to_user_id`, `message`, `file_path`, `file_type`, `is_read`, `created_at`) VALUES
(1, 15, 10, 'Здраствейте', NULL, NULL, 1, '2026-06-12 12:31:44'),
(2, 10, 15, 'Здраствуй, что-то не получается?', NULL, NULL, 0, '2026-06-15 17:45:30'),
(3, 16, 13, 'Здраствуйте, мне не понятна 3 тема', NULL, NULL, 1, '2026-06-15 18:10:34'),
(4, 16, 13, 'шмргшырмшгм', NULL, NULL, 1, '2026-06-15 18:10:39'),
(5, 16, 13, 'вышмргшвпмшгыв', NULL, NULL, 1, '2026-06-15 18:10:42'),
(6, 13, 16, 'Здраствуйте, пришлите конктретно что не понятно', NULL, NULL, 0, '2026-06-15 18:11:32'),
(7, 13, 16, '', 'uploads/private/images/6a3117ff3d60b.png', 'image/png', 0, '2026-06-16 09:31:43');

-- --------------------------------------------------------

--
-- Структура таблицы `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `role_description`) VALUES
(1, 'student', 'Обычный студент, может изучать курсы и выполнять задания'),
(2, 'teacher', 'Преподаватель, может создавать задания и проверять работы'),
(3, 'admin', 'Администратор, полный доступ ко всем функциям системы');

-- --------------------------------------------------------

--
-- Структура таблицы `support_messages`
--

CREATE TABLE `support_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','in_progress','resolved') DEFAULT 'new',
  `admin_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `support_messages`
--

INSERT INTO `support_messages` (`id`, `user_id`, `email`, `message`, `status`, `admin_response`, `created_at`) VALUES
(1, NULL, 'lera@gmail.com', 'Сайт говно', 'new', NULL, '2026-06-16 16:39:11'),
(2, NULL, 'lera@gmail.com', 'Сайт говно', 'resolved', 'Сама говно', '2026-06-16 16:39:16'),
(3, 13, 'lera@gmail.com', 'фапафпфп', 'in_progress', 'фцафца', '2026-06-16 16:44:39');

-- --------------------------------------------------------

--
-- Структура таблицы `topics`
--

CREATE TABLE `topics` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `topic_name` varchar(200) NOT NULL,
  `topic_description` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 30,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `topics`
--

INSERT INTO `topics` (`id`, `course_id`, `topic_name`, `topic_description`, `content`, `duration_minutes`, `image_path`) VALUES
(7, 2, 'Введение', 'Введение в язык JavaScript для новичков. В конце темы вас ждёт практическое задание!', '<h2>Введение в язык JavaScript для новичков</h2>\r\n\r\n<p>Язык JavaScript предназначен для выполнения в браузере наряду с HTML и CSS. Но, если эти языки предназначены для верстки структуры сайта, то JavaScript позволяет \"оживлять\" web-страницы - делать их реагирующими на действия пользователя или демонстрировать некоторую динамичность (к примеру, смена картинок в блоке или красивые плавно выпадающие менюшки).</p>\r\n\r\n<h2>Запуск JavaScript</h2>\r\n\r\n<p>JavaScript код пишется прямо на HTML странице внутри тега script. Этот тег можно размещать в любом месте страницы. Смотрите пример:</p>\r\n\r\n<pre>&lt;!DOCTYPE html&gt;\r\n&lt;html&gt;\r\n&lt;head&gt;\r\n&lt;meta charset=\"utf-8\"&gt;\r\n&lt;title&gt;Это заголовок тайтл&lt;/title&gt;\r\n&lt;script&gt;\r\nздесь пишем JavaScript код\r\n&lt;/script&gt;\r\n&lt;/head&gt;\r\n&lt;body&gt;\r\nЭто основное содержимое страницы.\r\n&lt;/body&gt;\r\n&lt;/html&gt;</pre>\r\n\r\n<p>Давайте напишем нашу первую программу на JavaScript. Вот она:</p>\r\n\r\n<pre>&lt;!DOCTYPE html&gt;\r\n&lt;html&gt;\r\n&lt;head&gt;\r\n&lt;meta charset=\"utf-8\"&gt;\r\n&lt;title&gt;Моя первая программа&lt;/title&gt;\r\n&lt;script&gt;\r\nconsole.log(\'Привет, мир!\');\r\n&lt;/script&gt;\r\n&lt;/head&gt;\r\n&lt;body&gt;\r\nмоя первая программа\r\n&lt;/body&gt;\r\n&lt;/html&gt;</pre>\r\n\r\n<p>Скопируйте этот код и разместите в HTML файле. Затем откройте этот файл в браузере - и вы увидите диалоговое окошко с текстом.</p>\r\n\r\n<p>Давайте разберем код написанной нами выше программы. Первым понятием, которое вам нужно узнать, являются <em>функции</em>. Функции позволяют выполнять некоторые действия. В нашем примере есть функция console.log(), которая выводит текст на экран в виде диалогового окошка.</p>\r\n\r\n<p>Функция состоит из имени (в нашем случае это console.log) и круглых скобок, написанных после этого имени. В этих круглых скобках следует писать <em>параметры функции</em>. В нашем случае параметром является текст, который выводится на экран.</p>\r\n\r\n<p>В нашем случае функция имеет один параметр, однако бывают функции, в которые нужно передавать несколько параметров. В этом случае эти параметры пишутся через запятую.</p>\r\n\r\n<p>В дальнейшем я не буду расписывать то, как подключается JavaScript, а буду просто писать JavaScript код, подразумевая, что вы его будете записывать в тегах script. С учетом этого замечания наша программа станет выглядеть вот так:</p>\r\n\r\n<pre>console.log(\'Привет, мир!\');</pre>\r\n\r\n<p>JavaScript код можно писать в отдельном файле, который затем будет подключен к HTML файлу. Давайте посмотрим, как это делается.</p>\r\n\r\n<p>Для начала создадим файл с нашим скриптом. У этого файла должно быть расширение .js. Для примера давайте назовем его script.js. Разместим в нем какой-нибудь код:</p>\r\n\r\n<pre>console.log(\'text\');</pre>\r\n\r\n<p>Давайте теперь подключим наш файл со скриптом к HTML файлу. Для этого в теге script в атрибуте src нужно указать путь к файлу со скриптом:</p>\r\n\r\n<pre>&lt;!DOCTYPE html&gt;\r\n&lt;html&gt;\r\n&lt;head&gt;\r\n&lt;meta charset=\"utf-8\"&gt;\r\n&lt;title&gt;&lt;/title&gt;\r\n&lt;script src=\"script.js\"&gt;&lt;/script&gt;\r\n&lt;/head&gt;\r\n&lt;body&gt;\r\n&lt;/body&gt;\r\n&lt;/html&gt;</pre>', 50, NULL),
(8, 2, 'Однократное объявление переменных и математические операции', 'Правила объявления переменных, математические операции с числами и переменными', '\r\n<h2>Одну и ту же переменную нельзя объявить несколько раз через let</h2>\r\n\r\n<p>К примеру, следующий код приведет к ошибке:</p>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">❌ Неправильно</div>\r\n    <pre><code>let a = 1;\r\nconsole.log(a);\r\n\r\nlet a = 2;\r\nconsole.log(a); // Ошибка! Переменная a уже объявлена</code></pre>\r\n</div>\r\n\r\n<p>Здесь есть два варианта решения проблемы. Можно просто ввести две разных переменных:</p>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">✅ Решение 1: Разные переменные</div>\r\n    <pre><code>let a = 1;\r\nconsole.log(a);\r\n\r\nlet b = 2;\r\nconsole.log(b);</code></pre>\r\n</div>\r\n\r\n<p>А можно вначале объявить переменную a, а затем делать операции с нею:</p>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">✅ Решение 2: Переиспользование переменной</div>\r\n    <pre><code>let a;\r\n\r\na = 1;\r\nconsole.log(a);\r\n\r\na = 2;\r\nconsole.log(a);</code></pre>\r\n</div>\r\n\r\n<h2>Математические операции с числами в JavaScript</h2>\r\n\r\n<h3>Сложение:</h3>\r\n<div class=\"code-block\">\r\n    <pre><code>let a = 1 + 2;\r\nconsole.log(a); // выведет 3</code></pre>\r\n</div>\r\n\r\n<h3>Вычитание:</h3>\r\n<div class=\"code-block\">\r\n    <pre><code>let b = 3 - 2;\r\nconsole.log(b); // выведет 1</code></pre>\r\n</div>\r\n\r\n<h3>Умножение:</h3>\r\n<div class=\"code-block\">\r\n    <pre><code>let c = 3 * 2;\r\nconsole.log(c); // выведет 6</code></pre>\r\n</div>\r\n\r\n<h3>Деление:</h3>\r\n<div class=\"code-block\">\r\n    <pre><code>let d = 4 / 2;\r\nconsole.log(d); // выведет 2</code></pre>\r\n</div>\r\n\r\n<div class=\"task-example\">\r\n    <h3>📝 Задание для закрепления:</h3>\r\n    <p>Создайте переменную a. Запишите в нее сумму чисел 1, 2 и 3. С помощью функции console.log выведите на экран содержимое переменной a.</p>\r\n</div>\r\n\r\n<p>Математические операции можно производить не только над числами, но и над переменными. Сложим, к примеру, значения двух переменных:</p>\r\n\r\n<div class=\"code-block\">\r\n    <pre><code>let a = 1;\r\nlet b = 2;\r\nconsole.log(a + b); // выведет 3</code></pre>\r\n</div>\r\n\r\n<p>Не обязательно сразу выводить результат операции, можно вначале записать его в какую-нибудь переменную, а уже затем вывести значение этой переменной:</p>\r\n\r\n<div class=\"code-block\">\r\n    <pre><code>let a = 1;\r\nlet b = 2;\r\nlet c = a + b; // запишем сумму в переменную c\r\nconsole.log(c);      // выведет 3</code></pre>\r\n</div>\r\n\r\n<div class=\"info-box\">\r\n    <h4>💡 Важно запомнить:</h4>\r\n    <ul>\r\n        <li>Переменную можно объявить только один раз с помощью <code>let</code></li>\r\n        <li>После объявления можно многократно изменять её значение</li>\r\n        <li>Математические операции: <code>+</code> (сложение), <code>-</code> (вычитание), <code>*</code> (умножение), <code>/</code> (деление)</li>\r\n        <li>Результат операции можно сохранить в новую переменную</li>\r\n    </ul>\r\n</div>\r\n\r\n<style>\r\n.code-block {\r\n    background: #1e1e1e;\r\n    border-radius: 8px;\r\n    margin: 15px 0;\r\n    overflow: hidden;\r\n}\r\n.code-label {\r\n    background: #2d2d2d;\r\n    color: #e2e8f0;\r\n    padding: 8px 15px;\r\n    font-size: 12px;\r\n    font-family: monospace;\r\n    border-bottom: 1px solid #3d3d3d;\r\n}\r\n.code-block pre {\r\n    margin: 0;\r\n    padding: 15px;\r\n    color: #e2e8f0;\r\n    font-family: \"JetBrains Mono\", monospace;\r\n    font-size: 14px;\r\n    overflow-x: auto;\r\n}\r\n.task-example {\r\n    background: #f0fdf4;\r\n    border-left: 4px solid #10b981;\r\n    padding: 15px;\r\n    margin: 20px 0;\r\n    border-radius: 8px;\r\n}\r\n.info-box {\r\n    background: #eff6ff;\r\n    border-left: 4px solid #3b82f6;\r\n    padding: 15px;\r\n    margin: 20px 0;\r\n    border-radius: 8px;\r\n}\r\nbody.theme-dark .task-example {\r\n    background: #1a3a2a;\r\n}\r\nbody.theme-dark .info-box {\r\n    background: #1e2a4a;\r\n}\r\n</style>\r\n', 50, NULL),
(9, 2, 'Приоритет математических операций', 'Правила приоритета операций в JavaScript, порядок выполнения вычислений, использование скобок', '<h2>Приоритет математических операций</h2>\r\n\r\n<p>В Python действует такой же приоритет, как в обычной математике:</p>\r\n<ul>\r\n    <li>Сначала выполняются действия в скобках.</li>\r\n    <li>Затем – возведение в степень.</li>\r\n    <li>Потом – умножение и деление (слева направо).</li>\r\n    <li>В последнюю очередь – сложение и вычитание.</li>\r\n</ul>\r\n\r\n<pre><code>res = 2 + 3 * 4   # 2 + 12 = 14\r\nprint(res)</code></pre>\r\n\r\n<h3>📝 Задания (вычислите без компьютера, затем проверьте кодом):</h3>\r\n<ul>\r\n    <li><code>2 + 3 * 4</code></li>\r\n    <li><code>10 - 6 / 3</code></li>\r\n    <li><code>10 - 6 * 3 - 5</code></li>\r\n</ul>', 50, NULL),
(10, 2, 'Работа с десятичными дробями и отрицательными числами', 'Десятичные дроби в JavaScript, отрицательные числа, операции с ними, смена знака', '\r\n<h2>Десятичные дроби в JavaScript</h2>\r\n\r\n<p>В JavaScript можно работать с десятичными дробями. При этом целая и дробная части отделяются друг от друга точкой. Смотрите пример:</p>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">Пример 1</div>\r\n    <pre><code>let a = 0.5;\r\nconsole.log(a); // выведет 0.5</code></pre>\r\n</div>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">Пример 2</div>\r\n    <pre><code>let a = 0.5 + 0.5;\r\nconsole.log(a); // выведет 1</code></pre>\r\n</div>\r\n\r\n<div class=\"task-example\">\r\n    <h3>📝 Задание 1:</h3>\r\n    <p>Запишите в переменную <code>a</code> число <strong>1.5</strong>, а в переменную <code>b</code> - число <strong>0.75</strong>. Найдите сумму значений этих переменных и выведите ее на экран.</p>\r\n</div>\r\n\r\n<h2>Отрицательные числа</h2>\r\n\r\n<p>Числа могут быть отрицательными. Для этого перед числом необходимо поставить знак минус:</p>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">Пример</div>\r\n    <pre><code>let a = -1;\r\nconsole.log(a); // выведет -1</code></pre>\r\n</div>\r\n\r\n<p>Знак минус можно писать как к числам, так и к переменным:</p>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">Пример с переменной</div>\r\n    <pre><code>let a = 1;\r\nlet b = -a; // записали в b содержимое a с обратным знаком\r\nconsole.log(b);   // выведет -1</code></pre>\r\n</div>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">Пример с выражением</div>\r\n    <pre><code>let a = 1;\r\nconsole.log(-a); // выведет -1</code></pre>\r\n</div>\r\n\r\n<div class=\"task-example\">\r\n    <h3>📝 Задание 2:</h3>\r\n    <p>Создайте переменную <code>a</code> со значением <strong>-100</strong>. Выведите это значение на экран.</p>\r\n</div>\r\n\r\n<div class=\"task-example\">\r\n    <h3>📝 Задание 3:</h3>\r\n    <p>Создайте переменную <code>a</code>, запишите в нее какое-нибудь положительное или отрицательное число. Выведите на экран это число с обратным знаком.</p>\r\n</div>\r\n\r\n<h2>Использование знака плюс</h2>\r\n\r\n<p>Подобно тому, как перед отрицательными числами ставится знак \"минус\", перед положительными числами можно ставить знак \"плюс\". Фактически этот плюс ничего не делает, но вполне является допустимым, смотрите пример:</p>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">Пример</div>\r\n    <pre><code>let a = +1;\r\nconsole.log(a); // выведет 1</code></pre>\r\n</div>\r\n\r\n<div class=\"info-box\">\r\n    <h4>💡 Важно запомнить:</h4>\r\n    <ul>\r\n        <li>Десятичные дроби пишутся через точку: <code>0.5</code>, <code>1.75</code></li>\r\n        <li>Отрицательные числа пишутся с минусом: <code>-5</code>, <code>-3.14</code></li>\r\n        <li>Можно менять знак переменной: <code>-a</code></li>\r\n        <li>Плюс перед положительным числом необязателен: <code>+5</code> то же самое, что <code>5</code></li>\r\n    </ul>\r\n</div>\r\n\r\n<style>\r\n.code-block {\r\n    background: #1e1e1e;\r\n    border-radius: 8px;\r\n    margin: 15px 0;\r\n    overflow: hidden;\r\n}\r\n.code-block .code-label {\r\n    background: #2d2d2d;\r\n    color: #e2e8f0;\r\n    padding: 8px 15px;\r\n    font-size: 12px;\r\n    font-family: monospace;\r\n    border-bottom: 1px solid #3d3d3d;\r\n}\r\n.code-block pre {\r\n    margin: 0;\r\n    padding: 15px;\r\n    color: #e2e8f0;\r\n    font-family: \"JetBrains Mono\", monospace;\r\n    font-size: 14px;\r\n    overflow-x: auto;\r\n}\r\n.task-example {\r\n    background: #f0fdf4;\r\n    border-left: 4px solid #10b981;\r\n    padding: 15px;\r\n    margin: 20px 0;\r\n    border-radius: 8px;\r\n}\r\n.info-box {\r\n    background: #eff6ff;\r\n    border-left: 4px solid #3b82f6;\r\n    padding: 15px;\r\n    margin: 20px 0;\r\n    border-radius: 8px;\r\n}\r\nbody.theme-dark .task-example {\r\n    background: #1a3a2a;\r\n}\r\nbody.theme-dark .info-box {\r\n    background: #1e2a4a;\r\n}\r\n</style>\r\n', 40, NULL),
(17, 2, 'Использование переменных', 'Объявление переменных, присваивание значений, работа с данными', '\r\n<h2>Использование переменных в JavaScript</h2>\r\n\r\n<p>Для того, чтобы использовать переменную, ее сначала необходимо <strong>объявить</strong>: написать перед ее именем ключевое слово <code>let</code>. Давайте объявим, например, переменную с именем <code>a</code>:</p>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">Объявление переменной</div>\r\n    <pre><code>let a;</code></pre>\r\n</div>\r\n\r\n<p>После объявления переменной в нее можно записать (говорят <strong>присвоить</strong> ей) какое-либо значение, например, какое-либо число или строку.</p>\r\n\r\n<p>Запись данных в переменную осуществляется с помощью операции присваивания <code>=</code>. Давайте, например, запишем в переменную <code>a</code> число <code>3</code>:</p>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">Присваивание значения</div>\r\n    <pre><code>let a = 3;</code></pre>\r\n</div>\r\n\r\n<p>А теперь давайте выведем содержимое этой переменной на экран с помощью функции <code>console.log</code>:</p>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">Вывод переменной</div>\r\n    <pre><code>let a = 3; // объявляем переменную и задаем ей значение\r\nconsole.log(a); // выведет 3</code></pre>\r\n</div>\r\n\r\n<p>Не обязательно записывать значение в переменную сразу после объявления. Можно сначала объявить переменную, а потом присвоить ей значение:</p>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">Объявление и присваивание отдельно</div>\r\n    <pre><code>let a; // объявим переменную\r\na = 3; // присвоим ей значение\r\nconsole.log(a); // выведем значение переменной на экран</code></pre>\r\n</div>\r\n\r\n<p>Как вы видите, <code>let</code> перед именем переменной пишется только один раз - при объявлении этой переменной. Затем, чтобы использовать переменную, нужно просто писать имя этой переменной.</p>\r\n\r\n<div class=\"task-example\">\r\n    <h3>📝 Задание для закрепления:</h3>\r\n    <p>Создайте переменную <code>num</code> и присвойте ей значение <code>123</code>. Выведите значение этой переменной на экран с помощью функции <code>console.log</code>.</p>\r\n</div>\r\n\r\n<p>Давайте объявим несколько переменных:</p>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">Несколько переменных</div>\r\n    <pre><code>let a = 1;\r\nlet b = 2;\r\nlet c = 3;</code></pre>\r\n</div>\r\n\r\n<p>Приведенный выше код можно упростить, написав <code>let</code> один раз и после него перечислить нужные переменные с их значениями, вот так:</p>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">Упрощенное объявление</div>\r\n    <pre><code>let a = 1, b = 2, c = 3;</code></pre>\r\n</div>\r\n\r\n<p>Можно вначале объявить все переменные, а потом присваивать им значения:</p>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">Объявление с последующим присваиванием</div>\r\n    <pre><code>let a, b, c; // объявляем все 3 переменные\r\n\r\n// Присваиваем переменным значения:\r\na = 1;\r\nb = 2;\r\nc = 3;</code></pre>\r\n</div>\r\n\r\n<p>Пусть у нас есть переменная, которой мы присвоили какое-то значение. Мы затем можем выполнить операцию присваивания еще раз и присвоить переменной другое значение:</p>\r\n\r\n<div class=\"code-block\">\r\n    <div class=\"code-label\">Изменение значения переменной</div>\r\n    <pre><code>let a; // объявляем переменную\r\na = 1; // записываем в нее значение 1\r\nconsole.log(a); // выведет 1\r\n\r\na = 2; // записываем теперь значение 2, затирая значение 1\r\nconsole.log(a); // выведет 2</code></pre>\r\n</div>\r\n\r\n<p>То есть значение переменной не является чем-то жестко привязанным к ней. Мы можем свободно записывать какие-то данные в переменную, прочитывать их, потом еще что-то записывать - и так далее.</p>\r\n\r\n<div class=\"info-box\">\r\n    <h4>💡 Важно запомнить:</h4>\r\n    <ul>\r\n        <li>Переменные объявляются с помощью ключевого слова <code>let</code></li>\r\n        <li>Оператор присваивания — это знак <code>=</code></li>\r\n        <li>Значение переменной можно изменять в любой момент</li>\r\n        <li>Имя переменной должно быть уникальным в рамках своей области видимости</li>\r\n    </ul>\r\n</div>\r\n', 45, NULL),
(18, 1, 'Введение в PHP', 'Основы PHP: что это такое, установка окружения, первая программа, синтаксис echo и var_dump', '<h2>Введение в PHP</h2>\r\n<p>Язык PHP предназначен для создания динамический сайтов. С его помощью, например, можно сделать форум, интернет-магазин, доску объявления и тому подобные вещи.</p>\r\n<p>Технически PHP нужен для генерации HTML кода. Как правило тексты вашего сайта хранятся в базе данных. PHP взаимодействует с базой через язык SQL, получает тексты и формирует из них итоговый HTML код, который и отправляется в браузер пользователя.</p>\r\n<h3>Установка окружения</h3>\r\n<p>Для запуска PHP локально на вашем компьютере необходима программа-сервер:</p>\r\n<ul>\r\n<li>Для Windows: <strong>OpenServer</strong></li>\r\n<li>Для Linux: <strong>LAMP</strong></li>\r\n<li>Для macOS: <strong>MAMP</strong></li>\r\n</ul>\r\n<p>Для начала можно использовать онлайн редактор: <a href=\"https://www.writephponline.com/\" target=\"_blank\">writephponline.com</a></p>\r\n<h3>Первая программа</h3>\r\n<p>PHP код пишется внутри специальных скобок &lt;?php ?&gt; прямо в HTML файлах:</p>\r\n<pre><code>&lt;!DOCTYPE html&gt;\r\n&lt;html&gt;\r\n&lt;head&gt;\r\n    &lt;meta charset=\"utf-8\"&gt;\r\n    &lt;title&gt;My first program&lt;/title&gt;\r\n&lt;/head&gt;\r\n&lt;body&gt;\r\n    &lt;?php\r\n        echo \'my first program\';\r\n    ?&gt;\r\n&lt;/body&gt;\r\n&lt;/html&gt;</code></pre>\r\n<p>Команда <strong>echo</strong> выполняет вывод заданной строки.</p>\r\n<p>Для корректного вывода кириллицы нужно указать кодировку:</p>\r\n<pre><code>&lt;meta charset=\"utf-8\"&gt;\r\n&lt;?php\r\n    echo \'моя первая программа\';\r\n?&gt;</code></pre>\r\n<p>Функция <strong>var_dump</strong> выводит тип данных и их размер:</p>\r\n<pre><code>&lt;?php\r\n    var_dump(\'12345\'); // выведет string(5) \"12345\"\r\n?&gt;</code></pre>', 45, NULL),
(19, 1, 'Вывод ошибок PHP', 'Типы ошибок в PHP: notice, warning, fatal. Настройка вывода ошибок для разработки.', '<h2>Вывод ошибок PHP</h2>\r\n<p>Ошибки бывают трех типов: <strong>notice</strong>, <strong>warning</strong> и <strong>fatal</strong>.</p>\r\n<ul>\r\n<li><strong>Notice</strong> - замечания о том, что в коде может быть что-то не так. Код продолжает работать.</li>\r\n<li><strong>Warning</strong> - предупреждения о том, что что-то пошло не так. Код запускается, но работает некорректно.</li>\r\n<li><strong>Fatal</strong> - критические ошибки, выполнение кода прекращается.</li>\r\n</ul>\r\n<h3>Включение вывода ошибок</h3>\r\n<p>По умолчанию нотисы и ворнинги выключены. Для разработки нужно включить вывод всех ошибок:</p>\r\n<pre><code>&lt;?php\r\n    error_reporting(E_ALL);\r\n    ini_set(\'display_errors\', \'on\');\r\n?&gt;</code></pre>\r\n<h3>Оптимальный код для запуска скриптов</h3>\r\n<pre><code>&lt;meta charset=\"utf-8\"&gt;\r\n&lt;?php\r\n    error_reporting(E_ALL);\r\n    ini_set(\'display_errors\', \'on\');\r\n    mb_internal_encoding(\'UTF-8\');\r\n    \r\n    echo \'my first program\';\r\n?&gt;</code></pre>\r\n<h3>Разделение кода</h3>\r\n<p>Для удобства проверки кода используйте два файла:</p>\r\n<p><strong>index.php</strong> (запускаемый файл):</p>\r\n<pre><code>&lt;meta charset=\"utf-8\"&gt;\r\n&lt;?php\r\n    error_reporting(E_ALL);\r\n    ini_set(\'display_errors\', \'on\');\r\n    mb_internal_encoding(\'UTF-8\');\r\n    include \'code.php\';\r\n?&gt;</code></pre>\r\n<p><strong>code.php</strong> (файл с вашим кодом для проверки):</p>\r\n<pre><code>&lt;?php\r\n    echo \'test\';\r\n?&gt;</code></pre>\r\n<p>На выложенном в интернет сайте вывод ошибок лучше отключать:</p>\r\n<pre><code>&lt;?php\r\n    error_reporting(0);\r\n    ini_set(\'display_errors\', \'off\');\r\n?&gt;</code></pre>', 30, NULL),
(20, 1, 'Комментарии в PHP', 'Однострочные и многострочные комментарии в PHP. Использование комментариев для документирования кода.', '<h2>Комментарии в PHP</h2>\r\n<p>Комментарии используются для пояснения кода и временного отключения участков кода.</p>\r\n<h3>Однострочные комментарии</h3>\r\n<p>Создаются с помощью <code>//</code> или <code>#</code>:</p>\r\n<pre><code>&lt;?php\r\n    echo \'hello\'; // это однострочный комментарий\r\n    # тоже комментарий\r\n?&gt;</code></pre>\r\n<h3>Многострочные комментарии</h3>\r\n<p>Создаются с помощью <code>/* */</code>:</p>\r\n<pre><code>&lt;?php\r\n    /*\r\n        это многострочный\r\n        комментарий\r\n    */\r\n    echo \'hello\';\r\n?&gt;</code></pre>\r\n<h3>Комментирование результата выполнения</h3>\r\n<p>В учебных целях результат вывода часто комментируют:</p>\r\n<pre><code>&lt;?php\r\n    echo \'hello\'; // выведет \'hello\'\r\n    echo 12345;    // выведет 12345\r\n?&gt;</code></pre>\r\n<p>Строки в комментариях берут в кавычки, числа выводятся без кавычек.</p>', 20, NULL),
(21, 1, 'Переменные в PHP', 'Основы работы с переменными: создание, присваивание значений, вывод. Правила именования переменных.', '<h2>Переменные в PHP</h2>\r\n<p>Переменная - это контейнер для хранения данных (строк, чисел и т.д.).</p>\r\n<h3>Правила именования переменных:</h3>\r\n<ul>\r\n<li>Перед именем переменной ставится знак <strong>$</strong></li>\r\n<li>Имя состоит из латинских букв, цифр и знаков подчеркивания</li>\r\n<li>Первый символ не может быть цифрой</li>\r\n<li>Имена чувствительны к регистру (<code>$a</code> и <code>$A</code> - разные переменные)</li>\r\n</ul>\r\n<h3>Присваивание значения</h3>\r\n<p>Знак <code>=</code> называется знаком присваивания:</p>\r\n<pre><code>&lt;?php\r\n    $a = 3;      // записываем число 3\r\n    $name = \'Иван\'; // записываем строку\r\n?&gt;</code></pre>\r\n<h3>Вывод переменных</h3>\r\n<pre><code>&lt;?php\r\n    $a = 3;\r\n    echo $a; // выведет 3\r\n?&gt;</code></pre>\r\n<h3>Изменение значения переменной</h3>\r\n<pre><code>&lt;?php\r\n    $a = 1;\r\n    echo $a; // выведет 1\r\n    $a = 2;  // затираем значение 1 на 2\r\n    echo $a; // выведет 2\r\n?&gt;</code></pre>\r\n<h3>Арифметические операции с переменными</h3>\r\n<pre><code>&lt;?php\r\n    $a = 1;\r\n    $b = 2;\r\n    echo $a + $b; // выведет 3\r\n    \r\n    $c = $a + $b; // результат можно сохранить в переменную\r\n    echo $c;      // выведет 3\r\n?&gt;</code></pre>', 35, NULL),
(22, 1, 'Математические операции в PHP', 'Арифметические операции: сложение, вычитание, умножение, деление. Приоритет операций. Использование скобок.', '<h2>Математические операции в PHP</h2>\r\n<p>PHP поддерживает базовые арифметические операции:</p>\r\n<ul>\r\n<li><strong>+</strong> - сложение</li>\r\n<li><strong>-</strong> - вычитание</li>\r\n<li><strong>*</strong> - умножение</li>\r\n<li><strong>/</strong> - деление</li>\r\n</ul>\r\n<h3>Приоритет операций</h3>\r\n<p>Приоритет такой же, как в математике: сначала умножение и деление, затем сложение и вычитание:</p>\r\n<pre><code>&lt;?php\r\n    $a = 2 * 2 + 3; // 4 + 3 = 7\r\n    echo $a;\r\n?&gt;</code></pre>\r\n<p>Умножение и деление имеют равный приоритет и выполняются слева направо:</p>\r\n<pre><code>&lt;?php\r\n    $a = 8 / 2 * 4; // (8/2)*4 = 4*4 = 16\r\n    $b = 8 * 2 / 4; // (8*2)/4 = 16/4 = 4\r\n    $c = 16 / 2 / 2 / 2; // (((16/2)/2)/2) = 2\r\n?&gt;</code></pre>\r\n<h3>Изменение приоритета с помощью скобок</h3>\r\n<p>Скобки позволяют изменить порядок выполнения операций:</p>\r\n<pre><code>&lt;?php\r\n    $a = 2 * (2 + 3); // 2 * 5 = 10\r\n?&gt;</code></pre>\r\n<p>Скобки могут быть вложенными:</p>\r\n<pre><code>&lt;?php\r\n    $a = 2 * (2 + 4 * (3 + 1)); // 2 * (2 + 4*4) = 2 * (2+16) = 36\r\n?&gt;</code></pre>', 40, NULL),
(36, 3, 'Вывод данных и комментарии', 'Функция print(), вывод строк и чисел. Однострочные и многострочные комментарии.', '<h2>Вывод данных на экран Python</h2>\r\n<p>Функция <code>print()</code> выводит информацию в консоль.</p>\r\n<pre><code>print(\'hello\')\r\nprint(1)</code></pre>\r\n<p><strong>Задания:</strong></p>\r\n<ul>\r\n<li>Выведите строку со своим именем.</li>\r\n<li>Выведите любое число.</li>\r\n</ul>\r\n\r\n<h2>Комментарии в Python</h2>\r\n<p>Однострочный комментарий: <code># текст</code></p>\r\n<pre><code># test\r\ntest = \'abc\'</code></pre>\r\n<p>Многострочный комментарий: <code>\"\"\" текст \"\"\"</code></p>\r\n<pre><code>\"\"\"\r\ntest1 = \'abc\'\r\ntest2 = \'123\'\r\ntest3 = 123\r\n\"\"\"</code></pre>\r\n<p><strong>Задания:</strong></p>\r\n<ul>\r\n<li>Закомментируйте строку <code>test = \'abc\'</code>.</li>\r\n<li>Заключите три строки в многострочный комментарий.</li>\r\n</ul>', 40, NULL),
(37, 3, 'Переменные и их вывод', 'Создание переменных, правила именования, вывод переменных через print().', '<h2>Переменные в Python</h2>\r\n<p>Переменная – это контейнер для данных. Создаётся присваиванием:</p>\r\n<pre><code>test = 1\r\nname = \'Иван\'</code></pre>\r\n<p>Имя может содержать латиницу, цифры (не первая), подчёркивание. Регистр важен.</p>\r\n<p>Значение можно менять:</p>\r\n<pre><code>a = 1\r\na = 2   # теперь a = 2</code></pre>\r\n\r\n<h2>Вывод переменной</h2>\r\n<pre><code>test = 1\r\nprint(test)   # 1</code></pre>\r\n<p><strong>Задания:</strong></p>\r\n<ul>\r\n<li>Придумайте имя переменной и запишите в неё значение \'abc\'.</li>\r\n<li>Запишите в переменную любое двузначное число.</li>\r\n<li>Выведите строковую переменную.</li>\r\n<li>Выведите числовую переменную.</li>\r\n</ul>', 35, NULL),
(38, 3, 'Математические операции', 'Сложение, вычитание, умножение, деление, деление нацело. Операции с переменными.', '<h2>Операции над числами</h2>\r\n<ul>\r\n<li><code>+</code> сложение</li>\r\n<li><code>-</code> вычитание</li>\r\n<li><code>*</code> умножение</li>\r\n<li><code>/</code> деление (float)</li>\r\n<li><code>//</code> целочисленное деление</li>\r\n</ul>\r\n<pre><code>print(1+2)   # 3\r\nprint(2-1)   # 1\r\nprint(3*2)   # 6\r\nprint(4/2)   # 2.0\r\nprint(10//3) # 3</code></pre>\r\n\r\n<h2>Операции с переменными</h2>\r\n<pre><code>a = 1\r\nb = 3\r\nprint(a + b)   # 4\r\nres = a + b\r\nprint(res)     # 4</code></pre>\r\n<p><strong>Задания:</strong></p>\r\n<ul>\r\n<li>Сумма 1,2,3.</li>\r\n<li>Разность 6-4.</li>\r\n<li>Произведение 7*3.</li>\r\n<li>Деление 8/3.</li>\r\n<li>Деление нацело 10//3.</li>\r\n<li>С переменными a=5,b=3 – сумма, разность, произведение, частное, целочисленное деление (a=15,b=6).</li>\r\n</ul>', 50, NULL),
(39, 3, 'Сокращённые операции и приоритет', 'Операторы +=, -=, *=, /=, //=. Приоритет арифметики. Равный приоритет умножения/деления.', '<h2>Сокращённые операции</h2>\r\n<pre><code>num = 4\r\nnum += 1   # 5\r\nnum -= 1   # 4\r\nnum *= 2   # 8\r\nnum /= 2   # 4.0\r\nnum //= 2  # 2.0</code></pre>\r\n\r\n<h2>Приоритет операций</h2>\r\n<p>Сначала умножение/деление, потом сложение/вычитание.</p>\r\n<pre><code>2 + 3 * 4   # 14\r\n10 - 6 / 3  # 8.0\r\n10 - 6 * 3 - 5  # -13</code></pre>\r\n\r\n<h2>Равный приоритет (слева направо)</h2>\r\n<pre><code>8 / 2 * 4   # 16\r\n8 * 2 / 4   # 4\r\n16 / 2 / 2 / 2  # 2</code></pre>\r\n<p><strong>Задания:</strong> переписать код с +=/-, вычислить выражения из теории.</p>', 45, NULL),
(40, 3, 'Группирующие скобки', 'Изменение порядка вычислений с помощью круглых скобок. Вложенные скобки.', '<h2>Скобки в выражениях</h2>\r\n<p>Выражение в скобках выполняется первым.</p>\r\n<pre><code>(2+3)*4   # 20\r\n(10-6)/3  # 1.333...\r\n10 - 6*(3-5)  # 22\r\n4 - 1 + (5 + 6*(2+7))  # 62</code></pre>\r\n<p>Скобки могут быть вложенными.</p>\r\n<p><strong>Задания:</strong> вычислите все четыре выражения.</p>', 30, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `topic_progress`
--

CREATE TABLE `topic_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `time_spent_minutes` int(11) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `topic_progress`
--

INSERT INTO `topic_progress` (`id`, `user_id`, `topic_id`, `is_completed`, `time_spent_minutes`, `completed_at`) VALUES
(7, 1, 7, 1, 50, NULL),
(8, 1, 8, 1, 65, NULL),
(16, 7, 7, 1, 0, NULL),
(17, 7, 8, 1, 0, NULL),
(18, 10, 7, 1, 0, NULL),
(19, 10, 8, 1, 0, NULL),
(24, 10, 9, 1, 0, NULL),
(32, 12, 7, 1, 0, NULL),
(33, 13, 7, 1, 0, NULL),
(34, 11, 7, 1, 0, NULL),
(35, 8, 7, 1, 0, NULL),
(36, 8, 8, 1, 0, NULL),
(37, 8, 9, 1, 0, NULL),
(38, 8, 10, 1, 0, NULL),
(39, 8, 17, 1, 0, NULL),
(49, 10, 10, 1, 0, NULL),
(50, 10, 17, 1, 0, NULL),
(52, 9, 18, 1, 0, NULL),
(53, 13, 18, 1, 0, NULL),
(54, 13, 19, 1, 0, NULL),
(58, 13, 8, 1, 0, NULL),
(59, 13, 9, 1, 0, NULL),
(60, 13, 20, 1, 0, NULL),
(61, 13, 10, 1, 0, NULL),
(62, 13, 17, 1, 0, NULL),
(64, 13, 21, 1, 0, NULL),
(66, 13, 22, 1, 0, NULL),
(67, 13, 36, 1, 0, NULL),
(68, 13, 37, 1, 0, NULL),
(70, 10, 18, 1, 0, NULL),
(71, 10, 19, 1, 0, NULL),
(72, 10, 20, 1, 0, NULL),
(73, 10, 21, 1, 0, NULL),
(74, 10, 22, 1, 0, NULL),
(76, 12, 8, 1, 0, '2026-06-21 09:28:52');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `theme` varchar(20) DEFAULT 'light',
  `font_size` varchar(20) DEFAULT 'medium',
  `avatar` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `theme`, `font_size`, `avatar`, `password`, `created_at`, `reset_token`, `reset_expires`) VALUES
(1, 'Иван Петров', 'ivan@example.com', 'light', 'medium', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-06 14:06:47', NULL, NULL),
(2, 'Мария Смирнова', 'maria@example.com', 'light', 'medium', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-06 14:06:47', NULL, NULL),
(3, 'Алексей Козлов', 'alex@example.com', 'light', 'medium', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-06 14:06:47', NULL, NULL),
(4, 'Фадеев Виктор', 'dudorov@bk.ru', 'light', 'medium', NULL, '$2y$10$rlesb.yJH31hatbr.ZF0YuNNaoCe7jsstfjc9kwONG6hRbPwGEK/2', '2026-04-06 14:06:47', NULL, NULL),
(5, 'Фурманов Кирилл', 'vitya.fadeev.09@bk.ru', 'light', 'medium', NULL, '$2y$10$5QTDaNu9y6rQdDZuFAs50OgbRHuc6n09r/.Om7wxdMxT24wOzrKxm', '2026-04-06 14:06:47', NULL, NULL),
(6, 'Согомонян Инна Эдуардовна', 'sogomonyan14@bk.ru', 'light', 'medium', NULL, '$2y$10$AqfO2i.tqHWyBYnmBE3GGOIaQZOxFa7eHl/tI1g3RH6Dt1YG7IjEK', '2026-04-06 14:06:47', NULL, NULL),
(7, 'Фадеев Виктор Юрьевич', 'top@top.com', 'light', 'medium', NULL, '$2y$10$f8t23Xlhk1ObiVvSlxUPkO9s2pr28zKljrEeSJjU3V.lRGzrNLPoy', '2026-04-06 14:06:47', NULL, NULL),
(8, 'Лол Лолович', 'lol@lol.com', 'light', 'medium', NULL, '$2y$10$4ZW.TGnwciYXw27WXSZ2qORGPgIMIAjMILUrfSh7/ddvHYWSr.vya', '2026-04-06 14:06:47', NULL, NULL),
(9, 'Инна Эдуардовна', 'inna@inna.com', 'light', 'medium', NULL, '$2y$10$oFIsl0B5NJ7s8RJbsz1SeO7sEeYr2r1Rdu/qwmVS8yHy2ZWBiQE/C', '2026-04-06 14:06:47', NULL, NULL),
(10, 'Иван Суслов', 'syslov@bk.ru', 'light', 'large', 'uploads/avatars/avatar_10_69d3ed61e12b0.jpg', '$2y$10$DHVOYbxH9pi/zS.AcsXxiOjHyKnJWP2EysLS7ZW88QYVbeReiUhp6', '2026-04-06 14:06:47', NULL, NULL),
(11, 'Виктор Корнеплод', 'viktorkorneplog@bk.ru', 'light', 'medium', NULL, '$2y$10$eOfBlv7AVAH7Y06v6vb7qurGjim.WI9SNdALtKQXg591vsdEuMnFK', '2026-04-19 18:11:56', NULL, NULL),
(12, 'Admin', 'admin@admin.com', 'light', 'small', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-05-03 13:55:04', NULL, NULL),
(13, 'Лера Смекалова', 'lera@gmail.com', 'light', 'medium', 'uploads/avatars/avatar_13_6a3adfc97af4c.png', '$2y$10$kaRwgaFERDdzFr6KF233q.gAKdq6mqEcwYel8cNx01tDRouioiZ/y', '2026-05-23 08:57:57', NULL, NULL),
(14, 'Ворон Воронов', 'voron@gmail.com', 'light', 'medium', NULL, '$2y$10$OE1HFRR0KzUL4YbIRPUg6OV0FpjriF4w5joQ28fZEBQODD6ePy6gu', '2026-05-23 08:58:43', NULL, NULL),
(15, 'Иван Сиплов', 'ivans@gmail.vom', 'light', 'medium', NULL, '$2y$10$UviJtwcBLyxIrmml3iieUuTzXdV7bTa1xNOddn3lpb7IQoJ1ALQ6m', '2026-06-12 12:21:35', NULL, NULL),
(16, 'Ахалеле Ахала', 'lera1@gmail.com', 'light', 'medium', NULL, '$2y$10$6XGSw87d09pCrbVQe5jGSuKs502Qb4H.6Z2r2vuUUFb9amrTzUviq', '2026-06-15 18:10:03', NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `user_progress`
--

CREATE TABLE `user_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `progress_percent` decimal(5,2) DEFAULT 0.00,
  `completed_topic` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `user_progress`
--

INSERT INTO `user_progress` (`id`, `user_id`, `course_id`, `progress_percent`, `completed_topic`) VALUES
(1, 1, 1, 75.50, 18),
(2, 1, 2, 45.25, 14),
(3, 1, 3, 20.00, 6),
(4, 2, 1, 30.00, 7),
(5, 2, 3, 10.50, 3),
(6, 3, 2, 90.75, 29),
(7, 3, 3, 85.00, 24),
(8, 4, 2, 0.00, 0),
(9, 4, 1, 0.00, 0),
(10, 4, 3, 0.00, 0),
(11, 5, 2, 0.00, 0),
(12, 5, 1, 16.67, 1),
(13, 5, 3, 0.00, 0),
(15, 6, 1, 100.00, 6),
(16, 6, 2, 0.00, 0),
(17, 6, 3, 0.00, 0),
(24, 7, 1, 0.00, 0),
(25, 7, 2, 40.00, 2),
(26, 7, 3, 0.00, 0),
(29, 8, 1, 100.00, 6),
(30, 8, 2, 100.00, 5),
(31, 8, 3, 100.00, 5),
(32, 9, 1, 20.00, 1),
(33, 9, 2, 0.00, 0),
(34, 9, 3, 0.00, 0),
(44, 10, 1, 100.00, 5),
(45, 10, 2, 100.00, 7),
(46, 10, 3, 60.00, 8),
(48, 3, 1, 0.00, 0),
(49, 2, 2, 0.00, 0),
(55, 11, 1, 16.67, 1),
(56, 11, 2, 20.00, 1),
(57, 11, 3, 0.00, 0),
(60, 13, 1, 100.00, 5),
(61, 13, 2, 20.00, 1),
(62, 13, 3, 40.00, 2),
(63, 14, 1, 0.00, 0),
(64, 14, 2, 0.00, 0),
(65, 14, 3, 0.00, 0),
(69, 12, 2, 40.00, 2),
(128, 15, 1, 0.00, 0),
(129, 15, 2, 0.00, 0),
(130, 15, 3, 0.00, 0),
(149, 16, 1, 0.00, 0),
(150, 16, 2, 0.00, 0),
(151, 16, 3, 0.00, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role_id`, `assigned_by`) VALUES
(1, 1, 1, 3),
(2, 2, 1, 3),
(3, 2, 2, 3),
(4, 3, 1, 3),
(5, 3, 3, 3),
(6, 4, 1, 4),
(7, 5, 1, 5),
(8, 6, 2, 6),
(9, 7, 1, 7),
(10, 8, 1, 8),
(11, 9, 2, 9),
(12, 10, 2, 10),
(13, 11, 1, 11),
(14, 12, 3, 12),
(15, 13, 2, 13),
(16, 14, 1, 14),
(17, 15, 2, 15),
(18, 16, 1, 16);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Индексы таблицы `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Индексы таблицы `assignment_progress`
--
ALTER TABLE `assignment_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_assignment` (`user_id`,`assignment_id`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- Индексы таблицы `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_assignment` (`user_id`,`assignment_id`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- Индексы таблицы `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_course` (`user_id`,`course_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Индексы таблицы `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_name` (`course_name`),
  ADD KEY `created_by` (`created_by`);

--
-- Индексы таблицы `course_teachers`
--
ALTER TABLE `course_teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Индексы таблицы `private_messages`
--
ALTER TABLE `private_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_user_id` (`from_user_id`),
  ADD KEY `to_user_id` (`to_user_id`);

--
-- Индексы таблицы `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Индексы таблицы `support_messages`
--
ALTER TABLE `support_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`);

--
-- Индексы таблицы `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Индексы таблицы `topic_progress`
--
ALTER TABLE `topic_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_topic` (`user_id`,`topic_id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Индексы таблицы `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_course` (`user_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Индексы таблицы `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_role` (`user_id`,`role_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_role` (`role_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT для таблицы `assignment_progress`
--
ALTER TABLE `assignment_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT для таблицы `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT для таблицы `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `course_teachers`
--
ALTER TABLE `course_teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `private_messages`
--
ALTER TABLE `private_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `support_messages`
--
ALTER TABLE `support_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT для таблицы `topic_progress`
--
ALTER TABLE `topic_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT для таблицы `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=192;

--
-- AUTO_INCREMENT для таблицы `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `assignment_progress`
--
ALTER TABLE `assignment_progress`
  ADD CONSTRAINT `assignment_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_progress_ibfk_2` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD CONSTRAINT `assignment_submissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_submissions_ibfk_2` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `course_teachers`
--
ALTER TABLE `course_teachers`
  ADD CONSTRAINT `course_teachers_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_teachers_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `topics`
--
ALTER TABLE `topics`
  ADD CONSTRAINT `topics_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `topic_progress`
--
ALTER TABLE `topic_progress`
  ADD CONSTRAINT `topic_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `topic_progress_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_progress`
--
ALTER TABLE `user_progress`
  ADD CONSTRAINT `user_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_progress_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
