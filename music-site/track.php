<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();
$currentUser = getCurrentUser();
$trackId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $db->prepare("SELECT t.*, u.username as uploader, u.id as uploader_id FROM tracks t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$stmt->execute(array($trackId));
$track = $stmt->fetch();

if (!$track) {
    http_response_code(404);
    die('Трек не найден');
}

incrementPlayCount($db, $trackId);
$stmt = $db->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.track_id = ? ORDER BY r.created_at DESC");
$stmt->execute(array($trackId));
$reviews = $stmt->fetchAll();
$avgRating = getTrackRating($db, $trackId);
$reviewCount = getReviewCount($db, $trackId);

$userReview = null;
if ($currentUser) {
    $stmt = $db->prepare("SELECT * FROM reviews WHERE track_id = ? AND user_id = ?");
    $stmt->execute(array($trackId, $currentUser['id']));
    $userReview = $stmt->fetch();
}

$userPlaylists = array();
if ($currentUser) {
    $stmt = $db->prepare("SELECT id, title FROM playlists WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute(array($currentUser['id']));
    $userPlaylists = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser && !$userReview) {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    if ($rating >= 1 && $rating <= 10) {
        $stmt = $db->prepare("INSERT INTO reviews (track_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute(array($trackId, $currentUser['id'], $rating, $comment));
        header('Location: ' . BASE_PATH . '/track.php?id=' . $trackId);
        exit;
    }
}

$coverSrc = $track['cover_path'] ? BASE_PATH . '/' . $track['cover_path'] : BASE_PATH . '/assets/images/default-cover.svg';
$trackTitle = htmlspecialchars($track['title']);
$trackArtist = htmlspecialchars($track['artist']);
$trackAlbum = $track['album'] ? htmlspecialchars($track['album']) : '';
$trackGenre = $track['genre'] ? htmlspecialchars($track['genre']) : '';
$uploaderName = htmlspecialchars($track['uploader']);
$uploadDate = date('d.m.Y H:i', strtotime($track['created_at']));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title><?php echo $trackTitle; ?> - <?php echo $trackArtist; ?></title>
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
<style>
.track-page{display:grid;grid-template-columns:1fr 2fr;gap:40px;padding:40px 0}
.track-stats{display:flex;gap:20px;margin:20px 0;padding:15px 0;border-top:1px solid var(--border-color);border-bottom:1px solid var(--border-color)}
.review-card{background:var(--card-bg);border-radius:10px;padding:20px;margin-bottom:20px}
.btn-add-playlist{background:var(--primary-color);color:#fff;border:none;padding:12px 20px;border-radius:8px;cursor:pointer;font-size:14px;display:flex;align-items:center;gap:8px}
.btn-add-playlist:hover{opacity:0.9}
.modal-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);display:none;align-items:center;justify-content:center;z-index:1000}
.modal-overlay.active{display:flex}
.modal{background:var(--card-bg);border-radius:12px;padding:25px;max-width:400px;width:90%;max-height:80vh;overflow-y:auto}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
.modal-close{background:none;border:none;font-size:24px;cursor:pointer;color:var(--text-secondary)}
.playlist-option{display:flex;align-items:center;gap:10px;padding:12px;border-radius:8px;cursor:pointer;transition:background 0.2s}
.playlist-option:hover{background:var(--primary-color);color:#fff}
.new-playlist-link{display:block;text-align:center;padding:12px;color:var(--primary-color);text-decoration:none;border:2px dashed var(--border-color);border-radius:8px;margin-top:10px}
.new-playlist-link:hover{border-color:var(--primary-color)}
@media(max-width:768px){.track-page{grid-template-columns:1fr}}
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
<div class="track-page">
<div>
<img src="<?php echo $coverSrc; ?>" style="width:100%;border-radius:10px" onerror="this.src='<?php echo BASE_PATH; ?>/assets/images/default-cover.svg'">
<audio controls autoplay style="width:100%;margin-top:20px"><source src="<?php echo BASE_PATH; ?>/<?php echo htmlspecialchars($track['file_path']); ?>" type="audio/mpeg"></audio>
<div style="margin-top:15px;display:flex;gap:10px;flex-wrap:wrap">
<a href="<?php echo BASE_PATH; ?>/api/download.php?id=<?php echo $trackId; ?>" class="btn btn-secondary" style="flex:1;text-align:center">⬇️ Скачать</a>
<?php if($currentUser): ?>
<button class="btn-add-playlist" onclick="openPlaylistModal()" style="flex:1;justify-content:center">
<span>➕</span> <span>В плейлист</span>
</button>
<?php endif; ?>
</div>
</div>

<div class="track-info">
<h1><?php echo $trackTitle; ?></h1>
<p style="font-size:20px;color:var(--text-secondary)"><?php echo $trackArtist; ?></p>
<?php if($trackAlbum): ?><p style="color:var(--text-secondary)">Альбом: <?php echo $trackAlbum; ?></p><?php endif; ?>
<?php if($trackGenre): ?><p style="color:var(--text-secondary)">Жанр: <?php echo $trackGenre; ?></p><?php endif; ?>

<div class="track-stats">
<div>⭐ <?php echo number_format($avgRating, 1); ?>/10 (<?php echo $reviewCount; ?> рец.)</div>
<div>▶️ <?php echo $track['play_count']; ?></div>
<div>⬇️ <?php echo $track['download_count']; ?></div>
<div>📁 <?php echo formatFileSize($track['file_size']); ?></div>
</div>

<p style="color:var(--text-secondary);font-size:14px">Загрузил: <a href="<?php echo BASE_PATH; ?>/profile.php?id=<?php echo $track['uploader_id']; ?>"><?php echo $uploaderName; ?></a> | <?php echo $uploadDate; ?></p>

<?php if($currentUser && !$userReview): ?>
<div style="margin-top:30px;padding:20px;background:var(--card-bg);border-radius:10px">
<h3>Оставить рецензию</h3>
<form method="POST">
<div class="form-group"><label>Оценка (1-10)</label><input type="number" name="rating" class="form-control" min="1" max="10" required></div>
<div class="form-group"><label>Комментарий</label><textarea name="comment" class="form-control" rows="4"></textarea></div>
<button type="submit" class="btn btn-primary">Отправить</button>
</form>
</div>
<?php elseif($userReview): ?><div class="alert alert-success">Вы уже оставили рецензию: <?php echo $userReview['rating']; ?>/10</div><?php endif; ?>

<h3 style="margin-top:40px">Рецензии (<?php echo $reviewCount; ?>)</h3>
<?php if(empty($reviews)): ?>
<p style="color:var(--text-secondary)">Рецензий пока нет</p>
<?php else: ?>
<?php foreach($reviews as $r): ?>
<div class="review-card">
<div style="display:flex;justify-content:space-between;margin-bottom:10px">
<strong><?php echo htmlspecialchars($r['username']); ?></strong>
<span style="color:var(--primary-color)">⭐ <?php echo $r['rating']; ?>/10</span>
</div>
<?php if($r['comment']): ?>
<p><?php echo nl2br(htmlspecialchars($r['comment'])); ?></p>
<?php endif; ?>
<small style="color:var(--text-secondary)"><?php echo date('d.m.Y H:i', strtotime($r['created_at'])); ?></small>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>
</main>

<?php if($currentUser): ?>
<div class="modal-overlay" id="playlistModal" onclick="if(event.target===this) closePlaylistModal()">
<div class="modal">
<div class="modal-header">
<h3 style="margin:0">Добавить в плейлист</h3>
<button class="modal-close" onclick="closePlaylistModal()">×</button>
</div>
<div id="playlistList">
<?php if(empty($userPlaylists)): ?>
<p style="color:var(--text-secondary);text-align:center;padding:20px 0">У вас пока нет плейлистов</p>
<a href="<?php echo BASE_PATH; ?>/my-playlists.php" class="new-playlist-link">➕ Создать плейлист</a>
<?php else: ?>
<?php foreach($userPlaylists as $pl): ?>
<label class="playlist-option">
<input type="radio" name="playlist_id" value="<?php echo $pl['id']; ?>" onchange="addToPlaylist(<?php echo $pl['id']; ?>)">
<span>🎵 <?php echo htmlspecialchars($pl['title']); ?></span>
</label>
<?php endforeach; ?>
<a href="<?php echo BASE_PATH; ?>/my-playlists.php" class="new-playlist-link">➕ Создать новый плейлист</a>
<?php endif; ?>
</div>
<div id="addToPlaylistResult" style="margin-top:15px"></div>
</div>
</div>
<?php endif; ?>

<footer class="footer"><div class="container"><p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>. Все права защищены.</p></div></footer>
<script src="<?php echo BASE_PATH; ?>/assets/js/main.js"></script>
<script>
window.BASE_PATH = '<?php echo BASE_PATH; ?>';
window.trackId = <?php echo $trackId; ?>;

function openPlaylistModal() {
    document.getElementById('playlistModal').classList.add('active');
}

function closePlaylistModal() {
    document.getElementById('playlistModal').classList.remove('active');
    document.getElementById('addToPlaylistResult').innerHTML = '';
}

function addToPlaylist(playlistId) {
    var formData = new FormData();
    formData.append('track_id', window.trackId);
    formData.append('playlist_id', playlistId);
    
    fetch(window.BASE_PATH + '/api/add-to-playlist.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        var resultDiv = document.getElementById('addToPlaylistResult');
        if (data.success) {
            resultDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            setTimeout(closePlaylistModal, 1500);
        } else {
            resultDiv.innerHTML = '<div class="alert alert-error">' + data.error + '</div>';
        }
    })
    .catch(function(error) {
        document.getElementById('addToPlaylistResult').innerHTML = '<div class="alert alert-error">Ошибка соединения</div>';
    });
}
</script>
</body>
</html>
