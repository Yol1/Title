<?php
/**
 * Конфигурационный файл
 */

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'music_site');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Настройки приложения
define('SITE_NAME', 'Leviafan');
define('SITE_URL', 'http://localhost');
define('BASE_PATH', '/music-site');

// Пути к файлам
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('MUSIC_UPLOAD_DIR', UPLOAD_DIR . 'music/');
define('COVERS_UPLOAD_DIR', UPLOAD_DIR . 'covers/');

// Ограничения
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50 MB
define('ALLOWED_MUSIC_TYPES', ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp3']);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Настройки сессии
define('SESSION_LIFETIME', 86400); // 24 часа в секундах

// Ошибки (включить в разработке, выключить в продакшене)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Времяzone
date_default_timezone_set('Europe/Moscow');
