<?php
require_once 'config/config.php';

if (!$is_logged_in || !$is_teacher) { 
    header('Location: index.php'); 
    exit; 
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if (!$course_id) die('Курс не указан');

if (!canManageCourse($pdo, $user_id, $course_id, $is_admin)) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration = (int)($_POST['duration_minutes'] ?? 0);
    $content = $_POST['content'] ?? '';
    
    // Обработка загрузки изображения
    $image_path = null;
    if (isset($_FILES['topic_image']) && $_FILES['topic_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['topic_image']['tmp_name']);
        finfo_close($finfo);
        
        if (in_array($mime, $allowed)) {
            $ext = pathinfo($_FILES['topic_image']['name'], PATHINFO_EXTENSION);
            $filename = 'topic_' . uniqid() . '.' . $ext;
            $upload_dir = 'uploads/topics/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $destination = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['topic_image']['tmp_name'], $destination)) {
                $image_path = $destination;
            } else {
                $error = 'Ошибка загрузки изображения';
            }
        } else {
            $error = 'Неподдерживаемый формат изображения. Разрешены JPEG, PNG, GIF, WEBP.';
        }
    }
    
    if (empty($name)) $error = 'Введите название темы';
    elseif (empty($description)) $error = 'Введите описание';
    elseif ($duration <= 0) $error = 'Длительность должна быть >0';
    else {
        $stmt = $pdo->prepare("INSERT INTO topics (course_id, topic_name, topic_description, duration_minutes, content, image_path) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$course_id, $name, $description, $duration, $content, $image_path]);
        $topic_id = $pdo->lastInsertId();
        
        // Перенаправляем на страницу управления заданиями
        header("Location: edit_assignments.php?topic_id=$topic_id");
        exit;
    }
}

$current_theme = $_SESSION['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить тему | CodeLeans</title>
    <link rel="stylesheet" href="style/codeleans.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style/global.css">
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <style>
        .form-container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }
        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
        }
        .image-preview {
            margin-top: 10px;
            max-width: 200px;
        }
        .image-preview img {
            width: 100%;
            border-radius: 10px;
        }
        .btn {
            background: #3b82f6;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        body.theme-dark .form-container {
            background: #1e293b;
        }
        body.theme-dark .form-group label {
            color: #e2e8f0;
        }
    </style>
</head>
<body class="<?= getBodyThemeClass() ?> font-<?= htmlspecialchars($current_font_size) ?>">
    <div class="container">
        <a href="manage_course.php?id=<?= $course_id ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Назад к управлению курсом
        </a>
        
        <div class="form-container">
            <h1><i class="fas fa-plus-circle"></i> Добавить тему</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Название темы</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Изображение темы (необязательно)</label>
                    <input type="file" name="topic_image" accept="image/jpeg,image/png,image/gif,image/webp" id="imageInput">
                    <div class="image-preview" id="imagePreview"></div>
                </div>
                
                <div class="form-group">
                    <label>Длительность (минуты)</label>
                    <input type="number" name="duration_minutes" min="1" required>
                </div>
                
                <div class="form-group">
                    <label>Содержание темы</label>
                    <textarea name="content" id="editor" rows="15"></textarea>
                </div>
                
                <button type="submit" class="btn">Создать тему и перейти к заданиям</button>
            </form>
        </div>
    </div>
    
    <script>
        // Предпросмотр изображения
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    const img = document.createElement('img');
                    img.src = ev.target.result;
                    preview.appendChild(img);
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        // Инициализация CKEditor
        ClassicEditor
            .create(document.querySelector('#editor'), {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'mediaEmbed', 'undo', 'redo'],
                heading: {
                    options: [
                        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                        { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                    ]
                }
            })
            .catch(error => {
                console.error(error);
            });
    </script>
</body>
</html>