<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();
$db = Database::getInstance();
$currentUser = getCurrentUser();
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $artist = trim($_POST['artist'] ?? '');
    $album = trim($_POST['album'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    
    if (empty($title) || empty($artist)) { $error = 'Название и исполнитель обязательны'; }
    elseif (isset($_FILES['music_file']) && $_FILES['music_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['music_file'];
        if ($file['size'] > MAX_FILE_SIZE) { $error = 'Файл слишком большой'; }
        else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            if (!isValidFileType($mimeType, 'music')) { $error = 'Недопустимый формат (MP3, WAV, OGG)'; }
            else {
                $uploadFilename = bin2hex(random_bytes(16)) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $uploadPath = MUSIC_UPLOAD_DIR . $uploadFilename;
                $relativePath = 'assets/uploads/music/' . $uploadFilename;
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $coverPath = null;
                    if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
                        $cover = $_FILES['cover_file'];
                        $coverFinfo = new finfo(FILEINFO_MIME_TYPE);
                        $coverMimeType = $coverFinfo->file($cover['tmp_name']);
                        if (isValidFileType($coverMimeType, 'image')) {
                            $coverFilename = bin2hex(random_bytes(16)) . '.' . pathinfo($cover['name'], PATHINFO_EXTENSION);
                            $coverPath = 'assets/uploads/covers/' . $coverFilename;
                            move_uploaded_file($cover['tmp_name'], COVERS_UPLOAD_DIR . $coverFilename);
                        }
                    }
                    $stmt = $db->prepare("INSERT INTO tracks (user_id, title, artist, album, genre, file_path, cover_path, file_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$currentUser['id'], $title, $artist, $album ?: null, $genre ?: null, $relativePath, $coverPath, $file['size']]);
                    $success = 'Трек успешно загружен!'; $_POST = [];
                } else { $error = 'Ошибка при загрузке'; }
            }
        }
    } else { $error = 'Выберите файл'; }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Загрузить трек - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/style.css">
</head>
<body>
<header class="header"><div class="container header-content">
<a href="<?= BASE_PATH ?>/" class="logo"><?= e(SITE_NAME) ?></a>
<nav class="nav"><a href="<?= BASE_PATH ?>/">Главная</a><a href="<?= BASE_PATH ?>/profile.php">Профиль</a><a href="<?= BASE_PATH ?>/logout.php">Выход</a></nav>
</div></header>

<main class="container"><div class="card auth-form" style="max-width:600px;margin:40px auto">
<h2>Загрузить трек</h2>
<?php if($error):?><div class="alert alert-error"><?=e($error)?></div><?php endif;?>
<?php if($success):?><div class="alert alert-success"><?=e($success)?></div><?php endif;?>

<form method="POST" enctype="multipart/form-data">
<div class="form-group"><label>Название *</label><input type="text" name="title" class="form-control" value="<?=e($_POST['title']??'')?>" required></div>
<div class="form-group"><label>Исполнитель *</label><input type="text" name="artist" class="form-control" value="<?=e($_POST['artist']??'')?>" required></div>
<div class="form-group"><label>Альбом</label><input type="text" name="album" class="form-control" value="<?=e($_POST['album']??'')?>" ></div>
<div class="form-group"><label>Жанр</label><input type="text" name="genre" class="form-control" value="<?=e($_POST['genre']??'')?>" placeholder="Рок, Поп..."></div>
<div class="form-group"><label>Аудиофайл *</label><input type="file" name="music_file" class="form-control" accept="audio/*" required><small style="color:var(--text-secondary)">Макс. 50MB. MP3, WAV, OGG</small></div>
<div class="form-group"><label>Обложка</label><input type="file" name="cover_file" class="form-control" accept="image/*"><small style="color:var(--text-secondary)">JPG, PNG, GIF</small></div>
<button type="submit" class="btn btn-primary" style="width:100%">Загрузить</button>
</form>
</div></main>

<footer class="footer"><div class="container"><p>&copy; <?=date('Y')?> <?=e(SITE_NAME)?>. Все права защищены.</p></div></footer>
<script>window.BASE_PATH='<?= BASE_PATH ?>';</script>
</body></html>