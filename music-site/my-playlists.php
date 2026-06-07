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

// Создание плейлиста
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_playlist'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    
    if (!empty($title)) {
        $stmt = $db->prepare("INSERT INTO playlists (user_id, title, description, is_public) VALUES (?, ?, ?, ?)");
        $stmt->execute([$currentUser['id'], $title, $description, $isPublic]);
        $success = 'Плейлист "' . e($title) . '" создан!';
    } else {
        $error = 'Введите название плейлиста';
    }
}

// Удаление плейлиста
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_playlist'])) {
    $playlistId = (int)$_POST['playlist_id'];
    $stmt = $db->prepare("DELETE FROM playlists WHERE id = ? AND user_id = ?");
    $stmt->execute([$playlistId, $currentUser['id']]);
    $success = 'Плейлист удалён';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Мои плейлисты - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/style.css">
<style>
.playlist-card { background:var(--card-bg); border-radius:10px; padding:20px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:center }
.playlist-info h3 { margin-bottom:5px }
.playlist-info p { color:var(--text-secondary); font-size:14px }
.btn-delete { background:#f44; color:#fff; border:none; padding:8px 15px; border-radius:5px; cursor:pointer }
.btn-delete:hover { background:#d33 }
</style>
</head>
<body>
<header class="header"><div class="container header-content">
<a href="<?= BASE_PATH ?>/" class="logo"><?= e(SITE_NAME) ?></a>
<nav class="nav">
<a href="<?= BASE_PATH ?>/">Главная</a>
<a href="<?= BASE_PATH ?>/tracks.php">Треки</a>
<a href="<?= BASE_PATH ?>/upload.php">Загрузить</a>
<a href="<?= BASE_PATH ?>/profile.php">Профиль</a>
<a href="<?= BASE_PATH ?>/logout.php">Выход</a>
</nav>
</div></header>

<main class="container">
<h1 style="margin:30px 0 20px">🎵 Мои плейлисты</h1>

<?php if($error): ?><div class="alert alert-error"><?=e($error)?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?=e($success)?></div><?php endif; ?>

<!-- Форма создания плейлиста -->
<div class="card" style="margin-bottom:30px;max-width:600px">
<h2>Создать плейлист</h2>
<form method="POST" style="margin-top:20px">
<div class="form-group"><label>Название *</label><input type="text" name="title" class="form-control" required placeholder="Мой крутой плейлист"></div>
<div class="form-group"><label>Описание</label><textarea name="description" class="form-control" rows="3" placeholder="О чём этот плейлист..."></textarea></div>
<div class="form-group" style="display:flex;align-items:center;gap:10px">
<input type="checkbox" name="is_public" id="is_public" value="1" checked>
<label for="is_public" style="margin:0">Публичный (виден всем)</label>
</div>
<button type="submit" name="create_playlist" class="btn btn-primary">Создать</button>
</form>
</div>

<!-- Список плейлистов -->
<h2>Ваши плейлисты</h2>
<?php
$stmt = $db->prepare("SELECT p.*, COUNT(pt.track_id) as track_count FROM playlists p LEFT JOIN playlist_tracks pt ON p.id = pt.playlist_id WHERE p.user_id = ? GROUP BY p.id ORDER BY p.created_at DESC");
$stmt->execute([$currentUser['id']]);
$playlists = $stmt->fetchAll();
?>

<?php if(empty($playlists)): ?>
<p style="color:var(--text-secondary);padding:20px;background:var(--card-bg);border-radius:10px">У вас пока нет плейлистов. Создайте первый!</p>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:15px">
<?php foreach($playlists as $p): ?>
<div class="playlist-card">
<div class="playlist-info">
<h3><a href="<?= BASE_PATH ?>/playlist.php?id=<?=$p['id']?>" style="color:var(--primary-color)">🎵 <?=e($p['title'])?></a></h3>
<p><?=$p['description'] ? e($p['description']) : 'Без описания'?> • <?=$p['track_count']?> треков • <?=$p['is_public'] ? '🌍 Публичный' : '🔒 Приватный'?></p>
</div>
<div style="display:flex;gap:10px">
<a href="<?= BASE_PATH ?>/playlist.php?id=<?=$p['id']?>" class="btn btn-secondary">Открыть</a>
<form method="POST" style="display:inline" onsubmit="return confirm('Удалить плейлист?')">
<input type="hidden" name="playlist_id" value="<?=$p['id']?>">
<button type="submit" name="delete_playlist" class="btn-delete">Удалить</button>
</form>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</main>

<footer class="footer"><div class="container"><p>&copy; <?=date('Y')?> <?=e(SITE_NAME)?>. Все права защищены.</p></div></footer>
<script>window.BASE_PATH='<?= BASE_PATH ?>';</script>
</body></html>