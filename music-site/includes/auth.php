<?php
/**
 * Функции авторизации и аутентификации
 */

require_once __DIR__ . '/database.php';

/**
 * Регистрация пользователя
 */
function registerUser(string $username, string $email, string $password): array {
    $db = Database::getInstance();
    
    // Проверка существования пользователя
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Пользователь с таким именем или email уже существует'];
    }
    
    // Хеширование пароля
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Создание пользователя
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    
    try {
        $stmt->execute([$username, $email, $passwordHash]);
        return ['success' => true, 'user_id' => $db->lastInsertId()];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Ошибка при регистрации: ' . $e->getMessage()];
    }
}

/**
 * Вход пользователя
 */
function loginUser(string $email, string $password): array {
    $db = Database::getInstance();
    
    $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Неверный email или пароль'];
    }
    
    // Создание сессии
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['logged_in'] = true;
    
    return ['success' => true, 'user' => $user];
}

/**
 * Выход пользователя
 */
function logoutUser(): void {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Проверка авторизации
 */
function isLoggedIn(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Получение текущего пользователя
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT id, username, email, avatar, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    return $stmt->fetch() ?: null;
}

/**
 * Требовать авторизацию (редирект на login)
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}
