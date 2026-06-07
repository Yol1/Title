<?php
/**
 * API для скачивания треков
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = Database::getInstance();
$trackId = (int)($_GET['id'] ?? 0);

if (!$trackId) {
    http_response_code(400);
    die('Неверный ID трека');
}

// Получение информации о треке
$stmt = $db->prepare("SELECT * FROM tracks WHERE id = ?");
$stmt->execute([$trackId]);
$track = $stmt->fetch();

if (!$track) {
    http_response_code(404);
    die('Трек не найден');
}

$filePath = __DIR__ . '/../' . $track['file_path'];

if (!file_exists($filePath)) {
    http_response_code(404);
    die('Файл не найден');
}

// Увеличение счетчика скачиваний
incrementDownloadCount($db, $trackId);

// Отправка файла
header('Content-Type: audio/mpeg');
header('Content-Disposition: attachment; filename="' . basename($track['title']) . '.mp3"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($filePath);
exit;
