<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();
$currentUser = getCurrentUser();
$playlistId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $db->prepare("SELECT p.*, u.username as owner_name FROM playlists p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->execute(array($playlistId));
$playlist = $stmt->fetch();

if (!$playlist) {
    http_response_code(404);
    die('Плейлист не найден');
}

if (!$playlist['is_public'] && (!$currentUser || $playlist['user_id'] !== $currentUser['id'])) {
    http_response_code(403);
    die('Доступ запрещён');
}

$stmt = $db->prepare("SELECT t.*, u.username as uploader, pt.position FROM playlist_tracks pt JOIN tracks t ON pt.track_id = t.id JOIN users u ON t.user_id = u.id WHERE pt.playlist_id = ? ORDER BY pt.position ASC");
$stmt->execute(array($playlistId));
$tracks = $stmt->fetchAll();

$isOwner = $currentUser && $playlist['user_id'] === $currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwner && isset($_POST['remove_track'])) {
    $trackId = (int)$_POST['remove_track'];
    $stmt = $db->prepare("DELETE FROM playlist_tracks WHERE playlist_id = ? AND track_id = ?");
    $stmt->execute(array($playlistId, $trackId));
    header('Location: ' . BASE_PATH . '/playlist.php?id=' . $playlistId);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwner && isset($_POST['delete_playlist'])) {
    $stmt = $db->prepare("DELETE FROM playlists WHERE id = ?");
    $stmt->execute(array($playlistId));
    header('Location: ' . BASE_PATH . '/profile.php');
    exit;
}

$trackCount = count($tracks);
$visibility = $playlist['is_public'] ? 'Публичный' : 'Приватный';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($playlist['title']); ?></title>
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
<style>
.playlist-header{display:flex;gap:30px;padding:40px 0;align-items:flex-end}
.playlist-cover{width:200px;height:200px;background:linear-gradient(135deg,var(--primary-color),#148a3f);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:60px}
.track-list-item{display:flex;align-items:center;gap:15px;padding:12px 15px;border-radius:5px}
.track-list-item:hover{background:var(--card-bg)}
</style>
</head>
<body>
<header class="header"><div class="container header-content">
<a href="<?php echo BASE_PATH; ?>/" class="logo"><?php echo htmlspecialchars(SITE_NAME); ?></a>
<nav class="nav">
<a href="<?php echo BASE_PATH; ?>/">Главная</a>
<a href="<?php echo BASE_PATH; ?>/tracks.php">Треки</a>
<a href="<?php echo BASE_PATH; ?>/playlists.php">Плейлисты</a>
<?php if($currentUser): ?>
<a href="<?php echo BASE_PATH; ?>/my-playlists.php">Мои плейлисты</a>
<a href="<?php echo BASE_PATH; ?>/upload.php">Загрузить</a>
<a href="<?php echo BASE_PATH; ?>/profile.php">Профиль</a>
<a href="<?php echo BASE_PATH; ?>/logout.php">Выход</a>
<?php else: ?>
<a href="<?php echo BASE_PATH; ?>/login.php">Вход</a>
<?php endif; ?>
</nav>
</div></header>

<main class="container">
<div class="playlist-header">
<div class="playlist-cover">🎵</div>
<div>
<p style="text-transform:uppercase;font-size:12px;font-weight:600">Плейлист</p>
<h1><?php echo htmlspecialchars($playlist['title']); ?></h1>
<p style="color:var(--text-secondary)"><?php echo $trackCount; ?> треков • <?php echo $visibility; ?> • Создал <a href="<?php echo BASE_PATH; ?>/profile.php?id=<?php echo $playlist['user_id']; ?>"><?php echo htmlspecialchars($playlist['owner_name']); ?></a></p>
<?php if($playlist['description']): ?>
<p style="margin-top:15px;color:var(--text-secondary)"><?php echo htmlspecialchars($playlist['description']); ?></p>
<?php endif; ?>
</div>
</div>

<?php if($isOwner): ?>
<div style="margin-bottom:20px;display:flex;gap:10px">
<button class="btn btn-primary" onclick="alert('Для добавления трека перейдите на страницу трека и нажмите «В плейлист»')">ℹ️ Как добавить трек</button>
<form method="POST" style="display:inline" onsubmit="return confirm('Удалить плейлист?')"><button type="submit" name="delete_playlist" class="btn btn-secondary" style="border-color:#f44;color:#f44">Удалить</button></form>
</div>
<?php endif; ?>

<div class="track-list">
<?php if(empty($tracks)): ?>
<p style="color:var(--text-secondary);padding:40px 0;text-align:center">В плейлисте нет треков</p>
<?php else: ?>
<?php 
$index = 0;
foreach($tracks as $t): 
    $index++;
    $coverSrc = $t['cover_path'] ? BASE_PATH . '/' . $t['cover_path'] : BASE_PATH . '/assets/images/default-cover.svg';
    $trackUrl = BASE_PATH . '/track.php?id=' . $t['id'];
?>
<div class="track-list-item">
<span style="width:30px;text-align:center;color:var(--text-secondary)"><?php echo $index; ?></span>
<img src="<?php echo $coverSrc; ?>" style="width:50px;height:50px;border-radius:5px" onerror="this.src='<?php echo BASE_PATH; ?>/assets/images/default-cover.svg'">
<div style="flex:1"><strong><a href="<?php echo $trackUrl; ?>"><?php echo htmlspecialchars($t['title']); ?></a></strong><br><small style="color:var(--text-secondary)"><?php echo htmlspecialchars($t['artist']); ?></small></div>
<?php if($isOwner): ?>
<form method="POST" style="display:inline"><button type="submit" name="remove_track" value="<?php echo $t['id']; ?>" style="background:none;border:none;cursor:pointer;font-size:18px;color:var(--text-secondary)">❌</button></form>
<?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</main>

<footer class="footer"><div class="container"><p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>. Все права защищены.</p></div></footer>
<script>window.BASE_PATH='<?php echo BASE_PATH; ?>';</script>
</body>
</html>
