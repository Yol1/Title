<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = Database::getInstance();
$currentUser = getCurrentUser();

// Последние треки
$stmt = $db->prepare("SELECT t.*, u.username as uploader, (SELECT AVG(rating) FROM reviews WHERE track_id = t.id) as avg_rating FROM tracks t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 12");
$stmt->execute();
$tracks = $stmt->fetchAll();

// Топ популярных треков
$stmt = $db->prepare("SELECT t.*, u.username as uploader, (SELECT AVG(rating) FROM reviews WHERE track_id = t.id) as avg_rating FROM tracks t JOIN users u ON t.user_id = u.id ORDER BY t.play_count DESC LIMIT 20");
$stmt->execute();
$topTracks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(SITE_NAME) ?> - Музыкальная платформа</title>
<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/style.css">
</head>
<body>
<header class="header"><div class="container header-content">
<a href="<?= BASE_PATH ?>/" class="logo"><?= e(SITE_NAME) ?></a>
<nav class="nav">
<a href="<?= BASE_PATH ?>/">Главная</a>
<a href="<?= BASE_PATH ?>/tracks.php">Треки</a>
<?php if ($currentUser): ?>
<a href="<?= BASE_PATH ?>/upload.php">Загрузить</a>
<a href="<?= BASE_PATH ?>/profile.php">Профиль</a>
<a href="<?= BASE_PATH ?>/logout.php">Выход</a>
<?php else: ?>
<a href="<?= BASE_PATH ?>/login.php">Вход</a>
<a href="<?= BASE_PATH ?>/register.php" class="btn btn-primary">Регистрация</a>
<?php endif; ?>
</nav>
</div></header>

<main class="container">
<section class="hero" style="padding:60px 0;text-align:center;">
<h1 style="font-size:48px;margin-bottom:20px;">Добро пожаловать на <?= e(SITE_NAME) ?></h1>
<p style="color:var(--text-secondary);font-size:18px;margin-bottom:30px;">Слушайте музыку, делитесь своими треками и оставляйте рецензии</p>
<?php if (!$currentUser): ?>
<a href="<?= BASE_PATH ?>/register.php" class="btn btn-primary">Начать бесплатно</a>
<?php else: ?>
<a href="<?= BASE_PATH ?>/upload.php" class="btn btn-primary">Загрузить трек</a>
<?php endif; ?>
</section>

<!-- Топ популярных треков -->
<?php if (!empty($topTracks)): ?>
<section class="top-tracks" style="margin-bottom:60px;">
<h2 style="margin-bottom:20px;">🔥 Топ-20 популярных треков</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:25px;">
<?php foreach ($topTracks as $index => $track): ?>
<div class="card track-card" style="position:relative;">
<div style="position:absolute;top:10px;left:10px;background:var(--primary-color);color:#fff;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;"><?=$index + 1?></div>
<a href="<?= BASE_PATH ?>/track.php?id=<?=$track['id']?>">
<img src="<?=$track['cover_path'] ? BASE_PATH . '/' . $track['cover_path'] : BASE_PATH . '/assets/images/default-cover.svg'?>" alt="<?=e($track['title'])?>" class="track-cover" onerror="this.src='<?= BASE_PATH ?>/assets/images/default-cover.svg'">
</a>
<h3 class="track-title"><a href="<?= BASE_PATH ?>/track.php?id=<?=$track['id']?>"><?=e($track['title'])?></a></h3>
<p class="track-artist"><?=e($track['artist'])?></p>
<div style="display:flex;justify-content:space-between;margin-top:10px;color:var(--text-secondary);font-size:14px;">
<span>⭐ <?=number_format($track['avg_rating'] ?? 0, 1)?></span>
<span>▶️ <?=$track['play_count']?></span>
</div>
</div>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>

<!-- Последние треки -->
<section class="latest-tracks">
<h2 style="margin-bottom:20px;">Последние треки</h2>
<div class="tracks-grid">
<?php foreach ($tracks as $track): ?>
<div class="card track-card">
<a href="<?= BASE_PATH ?>/track.php?id=<?=$track['id']?>">
<img src="<?=$track['cover_path'] ? BASE_PATH . '/' . $track['cover_path'] : BASE_PATH . '/assets/images/default-cover.svg'?>" alt="<?=e($track['title'])?>" class="track-cover" onerror="this.src='<?= BASE_PATH ?>/assets/images/default-cover.svg'">
</a>
<h3 class="track-title"><a href="<?= BASE_PATH ?>/track.php?id=<?=$track['id']?>"><?=e($track['title'])?></a></h3>
<p class="track-artist"><?=e($track['artist'])?></p>
<div style="display:flex;justify-content:space-between;margin-top:10px;color:var(--text-secondary);font-size:14px;">
<span>⭐ <?=number_format($track['avg_rating'] ?? 0, 1)?></span>
<span>▶️ <?=$track['play_count']?></span>
</div>
</div>
<?php endforeach; ?>
</div>
<?php if (empty($tracks)): ?>
<p style="text-align:center;color:var(--text-secondary);padding:40px 0;">Треков пока нет. Будьте первым, кто загрузит музыку!</p>
<?php endif; ?>
</section>
</main>

<footer class="footer"><div class="container"><p>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. Все права защищены.</p></div></footer>
<script src="<?= BASE_PATH ?>/assets/js/main.js"></script>
<script>window.BASE_PATH='<?= BASE_PATH ?>';</script>
</body></html>