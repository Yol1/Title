<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = Database::getInstance();
$currentUser = getCurrentUser();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$search = trim($_GET['search'] ?? '');
$genre = trim($_GET['genre'] ?? '');

$where = []; $params = [];
if ($search) { $where[] = "(t.title LIKE ? OR t.artist LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($genre) { $where[] = "t.genre = ?"; $params[] = $genre; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare("SELECT COUNT(*) as total FROM tracks t $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetch()['total'];

$pagination = ['page'=>$page, 'perPage'=>$perPage, 'total'=>$total, 'totalPages'=>ceil($total/$perPage), 'offset'=>($page-1)*$perPage, 'hasPrev'=>$page>1, 'hasNext'=>$page<ceil($total/$perPage)];

$stmt = $db->prepare("SELECT t.*, u.username as uploader, (SELECT AVG(rating) FROM reviews WHERE track_id = t.id) as avg_rating FROM tracks t JOIN users u ON t.user_id = u.id $whereClause ORDER BY t.created_at DESC LIMIT {$pagination['offset']}, {$pagination['perPage']}");
$stmt->execute($params);
$tracks = $stmt->fetchAll();

$genreStmt = $db->query("SELECT DISTINCT genre FROM tracks WHERE genre IS NOT NULL ORDER BY genre");
$genres = $genreStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Все треки - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/style.css">
</head>
<body>
<header class="header"><div class="container header-content">
<a href="<?= BASE_PATH ?>/" class="logo"><?= e(SITE_NAME) ?></a>
<nav class="nav"><a href="<?= BASE_PATH ?>/">Главная</a><a href="<?= BASE_PATH ?>/tracks.php">Треки</a><?php if($currentUser):?><a href="<?= BASE_PATH ?>/upload.php">Загрузить</a><a href="<?= BASE_PATH ?>/profile.php">Профиль</a><a href="<?= BASE_PATH ?>/logout.php">Выход</a><?php else:?><a href="<?= BASE_PATH ?>/login.php">Вход</a><?php endif;?></nav>
</div></header>

<main class="container">
<h1 style="margin:30px 0 20px;">Все треки</h1>
<form method="GET" style="display:flex;gap:10px;margin-bottom:30px;">
<input type="text" name="search" class="form-control" placeholder="Поиск..." value="<?= e($search) ?>" style="flex:1">
<select name="genre" class="form-control" style="max-width:200px"><option value="">Все жанры</option><?php foreach($genres as $g):?><option value="<?=e($g)?>" <?= $genre===$g?'selected':'' ?>><?=e($g)?></option><?php endforeach;?></select>
<button type="submit" class="btn btn-primary">Найти</button>
</form>

<div class="tracks-grid">
<?php foreach($tracks as $track):?>
<div class="card track-card">
<a href="<?= BASE_PATH ?>/track.php?id=<?=$track['id']?>"><img src="<?=$track['cover_path']? BASE_PATH.'/'.$track['cover_path']:BASE_PATH.'/assets/images/default-cover.svg'?>" class="track-cover" onerror="this.src='<?= BASE_PATH ?>/assets/images/default-cover.svg'"></a>
<h3 class="track-title"><a href="<?= BASE_PATH ?>/track.php?id=<?=$track['id']?>"><?=e($track['title'])?></a></h3>
<p class="track-artist"><?=e($track['artist'])?></p>
<div style="display:flex;justify-content:space-between;margin-top:10px;color:var(--text-secondary);font-size:14px"><span>⭐ <?=number_format($track['avg_rating']??0,1)?></span><span>▶️ <?=$track['play_count']?></span></div>
</div>
<?php endforeach;?>
</div>

<?php if(empty($tracks)):?><p style="text-align:center;color:var(--text-secondary);padding:40px 0;">Треки не найдены</p><?php endif;?>

<?php if($pagination['totalPages']>1):?>
<div style="display:flex;justify-content:center;gap:10px;margin:40px 0">
<?php if($pagination['hasPrev']):?><a href="?page=<?=$page-1?><?=$search?'&search='.urlencode($search):''?><?=$genre?'&genre='.urlencode($genre):''?>" class="btn btn-secondary">← Назад</a><?php endif;?>
<span style="align-self:center;color:var(--text-secondary)">Стр. <?=$page?> из <?=$pagination['totalPages']?></span>
<?php if($pagination['hasNext']):?><a href="?page=<?=$page+1?><?=$search?'&search='.urlencode($search):''?><?=$genre?'&genre='.urlencode($genre):''?>" class="btn btn-secondary">Вперед →</a><?php endif;?>
</div>
<?php endif;?>
</main>

<footer class="footer"><div class="container"><p>&copy; <?=date('Y')?> <?=e(SITE_NAME)?>. Все права защищены.</p></div></footer>
<script>window.BASE_PATH='<?= BASE_PATH ?>';</script>
</body></html>