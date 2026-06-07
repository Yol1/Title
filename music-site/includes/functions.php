<?php
/**
 * Вспомогательные функции
 */

require_once __DIR__ . '/config.php';

/**
 * Безопасный вывод данных
 */
function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Форматирование размера файла
 */
function formatFileSize(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Форматирование длительности трека
 */
function formatDuration(int $seconds): string {
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    
    return sprintf('%d:%02d', $minutes, $remainingSeconds);
}

/**
 * Генерация уникального имени файла
 */
function generateUniqueFilename(string $originalName): string {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $uniqueName = bin2hex(random_bytes(16)) . '.' . strtolower($extension);
    
    return $uniqueName;
}

/**
 * Проверка типа загружаемого файла
 */
function isValidFileType(string $mimeType, string $type = 'music'): bool {
    $allowedTypes = $type === 'music' ? ALLOWED_MUSIC_TYPES : ALLOWED_IMAGE_TYPES;
    
    return in_array($mimeType, $allowedTypes, true);
}

/**
 * Получение количества прослушиваний трека
 */
function getTrackPlayCount(PDO $db, int $trackId): int {
    $stmt = $db->prepare("SELECT play_count FROM tracks WHERE id = ?");
    $stmt->execute([$trackId]);
    $result = $stmt->fetch();
    
    return $result ? (int)$result['play_count'] : 0;
}

/**
 * Увеличение счетчика прослушиваний
 */
function incrementPlayCount(PDO $db, int $trackId): void {
    $stmt = $db->prepare("UPDATE tracks SET play_count = play_count + 1 WHERE id = ?");
    $stmt->execute([$trackId]);
}

/**
 * Увеличение счетчика скачиваний
 */
function incrementDownloadCount(PDO $db, int $trackId): void {
    $stmt = $db->prepare("UPDATE tracks SET download_count = download_count + 1 WHERE id = ?");
    $stmt->execute([$trackId]);
}

/**
 * Получение среднего рейтинга трека
 */
function getTrackRating(PDO $db, int $trackId): float {
    $stmt = $db->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE track_id = ?");
    $stmt->execute([$trackId]);
    $result = $stmt->fetch();
    
    return $result && $result['avg_rating'] ? round($result['avg_rating'], 1) : 0;
}

/**
 * Получение количества рецензий трека
 */
function getReviewCount(PDO $db, int $trackId): int {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM reviews WHERE track_id = ?");
    $stmt->execute([$trackId]);
    $result = $stmt->fetch();
    
    return $result ? (int)$result['count'] : 0;
}

/**
 * Пагинация
 */
function paginate(int $total, int $page, int $perPage): array {
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    
    return [
        'page' => $page,
        'perPage' => $perPage,
        'total' => $total,
        'totalPages' => $totalPages,
        'offset' => $offset,
        'hasPrev' => $page > 1,
        'hasNext' => $page < $totalPages
    ];
}
