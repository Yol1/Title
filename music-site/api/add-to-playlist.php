<?php
session_start();
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/auth.php";

header("Content-Type: application/json");

$currentUser = getCurrentUser();
if (!$currentUser) {
    echo json_encode(array("success" => false, "error" => "Требуется авторизация"));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(array("success" => false, "error" => "Неверный метод"));
    exit;
}

$trackId = isset($_POST["track_id"]) ? (int)$_POST["track_id"] : 0;
$playlistId = isset($_POST["playlist_id"]) ? (int)$_POST["playlist_id"] : 0;

if ($trackId <= 0 || $playlistId <= 0) {
    echo json_encode(array("success" => false, "error" => "Неверные данные"));
    exit;
}

$db = Database::getInstance();

$stmt = $db->prepare("SELECT user_id FROM playlists WHERE id = ? AND user_id = ?");
$stmt->execute(array($playlistId, $currentUser["id"]));
$playlist = $stmt->fetch();

if (!$playlist) {
    echo json_encode(array("success" => false, "error" => "Плейлист не найден"));
    exit;
}

$stmt = $db->prepare("SELECT id FROM tracks WHERE id = ?");
$stmt->execute(array($trackId));
$track = $stmt->fetch();

if (!$track) {
    echo json_encode(array("success" => false, "error" => "Трек не найден"));
    exit;
}

$stmt = $db->prepare("SELECT id FROM playlist_tracks WHERE playlist_id = ? AND track_id = ?");
$stmt->execute(array($playlistId, $trackId));
$exists = $stmt->fetch();

if ($exists) {
    echo json_encode(array("success" => false, "error" => "Трек уже в плейлисте"));
    exit;
}

$stmt = $db->prepare("SELECT MAX(position) as max_pos FROM playlist_tracks WHERE playlist_id = ?");
$stmt->execute(array($playlistId));
$result = $stmt->fetch();
$maxPos = $result ? (int)$result["max_pos"] : 0;

$stmt = $db->prepare("INSERT INTO playlist_tracks (playlist_id, track_id, position) VALUES (?, ?, ?)");
$stmt->execute(array($playlistId, $trackId, $maxPos + 1));

echo json_encode(array("success" => true, "message" => "Трек добавлен в плейлист"));
