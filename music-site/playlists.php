<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();
$currentUser = getCurrentUser();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser && isset($_POST['copy_playlist'])) {
    $playlistId = (int)$_POST['playlist_id'];
    $stmt = $db->prepare("SELECT * FROM playlists WHERE id = ? AND is_public = 1");
    $stmt->execute(array($playlistId));
    $original = $stmt->fetch();
    if ($original && $original['user_id'] != $currentUser['id']) {
        $newTitle = $original['title'] . ' (копия)';
        $stmt = $db->prepare("INSERT INTO playlists (user_id, title, description, is_public) VALUES (?, ?, ?, 0)");
        $stmt->execute(array($currentUser['id'], $newTitle, $original['description']));
        $newId = $db->lastInsertId();
        $stmt = $db->prepare("SELECT track_id FROM playlist_tracks WHERE playlist_id = ? ORDER BY position");
        $stmt->execute(array($playlistId));
        $tracks = $stmt->fetchAll();
        $pos = 1;
        foreach ($tracks as $t) {
            $stmt = $db->prepare("INSERT INTO playlist_tracks (playlist_id, track_id, position) VALUES (?, ?, ?)");
            $stmt->execute(array($newId, $t['track_id'], $pos));
            $pos++;
        }
        $success = 'Плейлист скопирован!';
    }
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

$where = array('p.is_public = 1');
$params = array();
if ($search) {
    $where[] = '(p.title LIKE ? OR p.description LIKE ? OR u.username LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$whereClause = implode(' AND ', $where);

$orderBy = 'p.created_at DESC';
if ($sort === 'popular') $orderBy = 'track_count DESC';
elseif ($sort === 'name') $orderBy = 'p.title ASC';

$countStmt = $db->prepare("SELECT COUNT(*) as total FROM playlists p JOIN users u ON p.user_id = u.id WHERE " . $whereClause);
$countStmt->execute($params);
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / $perPage);

$stmt = $db->prepare("SELECT p.*, u.username as owner_name, u.id as owner_id, COUNT(pt.track_id) as track_count FROM playlists p JOIN users u ON p.user_id = u.id LEFT JOIN playlist_tracks pt ON p.id = pt.playlist_id WHERE " . $whereClause . " GROUP BY p.id ORDER BY " . $orderBy . " LIMIT " . $offset . ", " . $perPage);
$stmt->execute($params);
$playlists = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Публичные плейлисты - <?php echo htmlspecialchars(SITE_NAME); ?></title>
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
<style>
.playlist-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:25px;margin-top:30px}
.playlist-card{background:var(--card-bg);border-radius:10px;padding:20px;transition:transform 0.2s}
.playlist-card:hover{transform:translateY(-5px)}
.playlist-card-header{display:flex;align-items:center;gap:15px;margin-bottom:15px}
.playlist-icon{width:60px;height:60px;background:linear-gradient(135deg,var(--primary-color),#148a3f);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:30px}
.playlist-card h3{margin:0;font-size:16px}
.filter-bar{display:flex;gap:15px;margin-bottom:30px;flex-wrap:wrap}
.filter-bar input{flex:1;min-width:200px}
.filter-bar select{min-width:150px}
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
<h1 style="margin:30px 0 20px">🎵 Публичные плейлисты</h1>
<?php if($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<form method="GET" class="filter-bar">
<input type="text" name="search" class="form-control" placeholder="Поиск по названию, описанию или автору..." value="<?php echo htmlspecialchars($search); ?>">
<select name="sort" class="form-control">
<option value="newest" <?php echo $sort==='newest'?'selected':''; ?>>Сначала новые</option>
<option value="popular" <?php echo $sort==='popular'?'selected':''; ?>>По популярности</option>
<option value="name" <?php echo $sort==='name'?'selected':''; ?>>По названию</option>
</select>
<button type="submit" class="btn btn-primary">Найти</button>
</form>

<?php if(empty($playlists)): ?>
<p style="color:var(--text-secondary);padding:40px 0;text-align:center">Публичных плейлистов пока нет</p>
<?php else: ?>
<div class="playlist-grid">
<?php foreach($playlists as $p): 
$isOwn = $currentUser && $p['owner_id'] == $currentUser['id'];
?>
<div class="playlist-card">
<div class="playlist-card-header">
<div class="playlist-icon">🎵</div>
<div>
<h3><?php echo htmlspecialchars($p['title']); ?></h3>
<p style="color:var(--text-secondary);font-size:12px;margin:0">Автор: <a href="<?php echo BASE_PATH; ?>/profile.php?id=<?php echo $p['owner_id']; ?>" style="color:var(--primary-color)"><?php echo htmlspecialchars($p['owner_name']); ?></a></p>
</div>
</div>
<?php if($p['description']): ?>
<p style="color:var(--text-secondary);font-size:14px;margin-bottom:15px"><?php echo htmlspecialchars(mb_substr($p['description'], 0, 100)); ?><?php echo mb_strlen($p['description']) > 100 ? '...' : ''; ?></p>
<?php endif; ?>
<div style="display:flex;justify-content:space-between;margin-bottom:15px;color:var(--text-secondary);font-size:13px">
<span>🎶 <?php echo $p['track_count']; ?> треков</span>
<span>📅 <?php echo date('d.m.Y', strtotime($p['created_at'])); ?></span>
</div>
<div style="display:flex;gap:10px">
<a href="<?php echo BASE_PATH; ?>/playlist.php?id=<?php echo $p['id']; ?>" class="btn btn-secondary" style="flex:1;text-align:center">Открыть</a>
<?php if($currentUser && !$isOwn): ?>
<form method="POST" style="flex:1"><input type="hidden" name="playlist_id" value="<?php echo $p['id']; ?>"><button type="submit" name="copy_playlist" class="btn btn-primary" style="width:100%">Копировать</button></form>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>

<?php if($totalPages > 1): ?>
<div style="display:flex;justify-content:center;gap:10px;margin:40px 0">
<?php if($page > 1): ?><a href="?page=<?php echo $page-1; ?><?php echo $search?'&search='.urlencode($search):''; ?><?php echo $sort!=='newest'?'&sort='.$sort:''; ?>" class="btn btn-secondary">← Назад</a><?php endif; ?>
<span style="align-self:center;color:var(--text-secondary)">Стр. <?php echo $page; ?> из <?php echo $totalPages; ?></span>
<?php if($page < $totalPages): ?><a href="?page=<?php echo $page+1; ?><?php echo $search?'&search='.urlencode($search):''; ?><?php echo $sort!=='newest'?'&sort='.$sort:''; ?>" class="btn btn-secondary">Вперед →</a><?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>
</main>

<footer class="footer"><div class="container"><p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>. Все права защищены.</p></div></footer>
<script>window.BASE_PATH='<?php echo BASE_PATH; ?>';</script>
</body>
</html>
