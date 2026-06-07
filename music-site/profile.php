<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();
$currentUser = getCurrentUser();

if (!$currentUser) {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

$userId = isset($_GET['id']) ? (int)$_GET['id'] : $currentUser['id'];
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute(array($userId));
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    die('Пользователь не найден');
}

$stmt = $db->prepare("SELECT t.*, (SELECT AVG(rating) FROM reviews WHERE track_id = t.id) as avg_rating FROM tracks t WHERE t.user_id = ? ORDER BY t.created_at DESC");
$stmt->execute(array($userId));
$userTracks = $stmt->fetchAll();

$stmt = $db->prepare("SELECT * FROM playlists WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute(array($userId));
$playlists = $stmt->fetchAll();

$isOwn = $userId === $currentUser['id'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Профиль <?php echo htmlspecialchars($user['username']); ?></title>
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
<style>
.profile-header{display:flex;align-items:center;gap:30px;padding:40px 0;border-bottom:1px solid var(--border-color);margin-bottom:30px}
.profile-avatar{width:150px;height:150px;border-radius:50%;background:var(--card-bg);display:flex;align-items:center;justify-content:center;font-size:60px}
.track-list-item{display:flex;align-items:center;gap:15px;padding:15px;background:var(--card-bg);border-radius:10px;margin-bottom:15px}
</style>
</head>
<body>
<header class="header"><div class="container header-content">
<a href="<?php echo BASE_PATH; ?>/" class="logo"><?php echo htmlspecialchars(SITE_NAME); ?></a>
<nav class="nav">
<a href="<?php echo BASE_PATH; ?>/">Главная</a>
<a href="<?php echo BASE_PATH; ?>/tracks.php">Треки</a>
<a href="<?php echo BASE_PATH; ?>/upload.php">Загрузить</a>
<a href="<?php echo BASE_PATH; ?>/profile.php">Профиль</a>
<a href="<?php echo BASE_PATH; ?>/logout.php">Выход</a>
</nav>
</div></header>

<main class="container">
<div class="profile-header">
<div class="profile-avatar"><?php echo $user['avatar'] ? '<img src="'.BASE_PATH.'/'.$user['avatar'].'" style="width:100%;height:100%;border-radius:50%">' : '👤'; ?></div>
<div><h1><?php echo htmlspecialchars($user['username']); ?></h1><p style="color:var(--text-secondary)"><?php echo htmlspecialchars($user['email']); ?> • На сайте с <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
<div style="display:flex;gap:30px;margin-top:15px">
<div style="text-align:center"><div style="font-size:24px;font-weight:bold;color:var(--primary-color)"><?php echo count($userTracks); ?></div><div style="color:var(--text-secondary);font-size:14px">Треков</div></div>
<div style="text-align:center"><div style="font-size:24px;font-weight:bold;color:var(--primary-color)"><?php echo count($playlists); ?></div><div style="color:var(--text-secondary);font-size:14px">Плейлистов</div></div>
</div></div>
</div>

<?php if($isOwn): ?>
<div style="margin-bottom:30px">
<a href="<?php echo BASE_PATH; ?>/my-playlists.php" class="btn btn-primary">🎵 Управление плейлистами</a>
</div>
<?php endif; ?>

<h2>Треки</h2>
<?php if(empty($userTracks)): ?>
<p style="color:var(--text-secondary)"><?php echo $isOwn ? 'Вы ещё не загрузили треки. <a href="'.BASE_PATH.'/upload.php" style="color:var(--primary-color)">Загрузить</a>' : 'У пользователя нет треков'; ?></p>
<?php else: ?>
<?php foreach($userTracks as $t): ?>
<div class="track-list-item">
<img src="<?php echo $t['cover_path'] ? BASE_PATH.'/'.$t['cover_path'] : BASE_PATH.'/assets/images/default-cover.svg'; ?>" style="width:60px;height:60px;border-radius:5px" onerror="this.src='<?php echo BASE_PATH; ?>/assets/images/default-cover.svg'">
<div style="flex:1"><strong><a href="<?php echo BASE_PATH; ?>/track.php?id=<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['title']); ?></a></strong><br><small style="color:var(--text-secondary)"><?php echo htmlspecialchars($t['artist']); ?></small></div>
<div style="color:var(--text-secondary);font-size:14px"><span>⭐ <?php echo number_format($t['avg_rating'] ?? 0, 1); ?></span> <span>▶️ <?php echo $t['play_count']; ?></span></div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if(!empty($playlists)): ?>
<h2 style="margin-top:40px">Плейлисты</h2>
<div class="tracks-grid">
<?php foreach($playlists as $p): ?>
<div class="card"><h3>🎵 <?php echo htmlspecialchars($p['title']); ?></h3><?php if($p['description']): ?><p style="color:var(--text-secondary);font-size:14px"><?php echo htmlspecialchars($p['description']); ?></p><?php endif; ?><a href="<?php echo BASE_PATH; ?>/playlist.php?id=<?php echo $p['id']; ?>" class="btn btn-secondary" style="margin-top:10px">Открыть</a></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</main>

<footer class="footer"><div class="container"><p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>. Все права защищены.</p></div></footer>
<script>window.BASE_PATH='<?php echo BASE_PATH; ?>';</script>
</body>
</html>
