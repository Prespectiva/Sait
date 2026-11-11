<?php
require 'db.php';
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            header("Location: profile.php");
            exit;
        } else {
            $error = "Неверный Email или пароль!";
        }
    } else {
        $error = "Заполните все поля!";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход | Колледж.Банк</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="card">
        <h2>Вход в Колледж.Банк</h2>
        
        <?php if (isset($_GET['registered'])): ?>
            <div class="msg" style="background:#f0fdf4; color:#166534; border:1px solid #bbf7d0;">Успешная регистрация! Войдите.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="msg error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
        </form>
        <a href="register.php">Нет аккаунта? Зарегистрироваться</a>
    </div>
</body>
</html>