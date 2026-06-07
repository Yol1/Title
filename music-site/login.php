<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        $result = loginUser($email, $password);
        if ($result['success']) {
            header('Location: ' . BASE_PATH . '/index.php');
            exit;
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
<title>Вход - <?php echo htmlspecialchars(SITE_NAME); ?></title>
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
</head>
<body>
<header class="header">
<div class="container header-content">
<a href="<?php echo BASE_PATH; ?>/" class="logo"><?php echo htmlspecialchars(SITE_NAME); ?></a>
<nav class="nav">
<a href="<?php echo BASE_PATH; ?>/">Главная</a>
<a href="<?php echo BASE_PATH; ?>/register.php">Регистрация</a>
</nav>
</div>
</header>

<main class="container">
<div class="auth-container">
<div class="card auth-form">
<h2>Вход</h2>
<?php if($error): ?>
<div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST">
<div class="form-group">
<label for="email">Email</label>
<input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
</div>

<div class="form-group">
<label for="password">Пароль</label>
<input type="password" id="password" name="password" class="form-control" required>
</div>

<button type="submit" class="btn btn-primary" style="width:100%">Войти</button>
</form>

<p style="text-align:center;margin-top:20px;color:var(--text-secondary);">
Нет аккаунта? <a href="<?php echo BASE_PATH; ?>/register.php">Зарегистрироваться</a>
</p>
</div>
</div>
</main>

<footer class="footer">
<div class="container">
<p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>. Все права защищены.</p>
</div>
</footer>
<script>window.BASE_PATH='<?php echo BASE_PATH; ?>';</script>
</body>
</html>