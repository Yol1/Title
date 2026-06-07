<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) { header('Location: ' . BASE_PATH . '/index.php'); exit; }

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Заполните все поля';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Имя пользователя должно быть от 3 до 50 символов';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введите корректный email';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Пароли не совпадают';
    } else {
        $result = registerUser($username, $email, $password);
        if ($result['success']) {
            $success = 'Регистрация успешна! Теперь вы можете войти.';
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Регистрация - <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/style.css">
</head>
<body>
<header class="header"><div class="container header-content">
<a href="<?= BASE_PATH ?>/" class="logo"><?= e(SITE_NAME) ?></a>
<nav class="nav"><a href="<?= BASE_PATH ?>/">Главная</a><a href="<?= BASE_PATH ?>/login.php">Вход</a></nav>
</div></header>

<main class="container">
<div class="auth-container">
<div class="card auth-form">
<h2>Регистрация</h2>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<form method="POST">
<div class="form-group"><label for="username">Имя пользователя</label><input type="text" id="username" name="username" class="form-control" value="<?= e($_POST['username'] ?? '') ?>" required minlength="3" maxlength="50"></div>
<div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required></div>
<div class="form-group"><label for="password">Пароль</label><input type="password" id="password" name="password" class="form-control" required minlength="6"></div>
<div class="form-group"><label for="password_confirm">Подтвердите пароль</label><input type="password" id="password_confirm" name="password_confirm" class="form-control" required></div>
<button type="submit" class="btn btn-primary" style="width:100%">Зарегистрироваться</button>
</form>

<p style="text-align:center;margin-top:20px;color:var(--text-secondary);">Уже есть аккаунт? <a href="<?= BASE_PATH ?>/login.php">Войти</a></p>
</div></div></main>

<footer class="footer"><div class="container"><p>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. Все права защищены.</p></div></footer>
<script>window.BASE_PATH='<?= BASE_PATH ?>';</script>
</body></html>