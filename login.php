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
            $_SESSION['role'] = $user['role'];
            header("Location: offers.php");
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
    <meta charset="UTF-8"><title>Вход | Колледж.Банк</title>
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        function toggleTheme() {
            const newTheme = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }
    </script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="card">
        <div style="text-align: right; margin-bottom: 10px;">
            <button class="theme-toggle" onclick="toggleTheme()" title="Сменить тему" style="margin: 0;">🌓</button>
        </div>
        <h2>Вход</h2>
        <?php if (isset($_GET['registered'])): ?>
            <div class="msg success">Успешная регистрация! Войдите.</div>
        <?php endif; ?>
        <?php if ($error): ?><div class="msg error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
        </form>
        <a href="register.php">Нет аккаунта? Зарегистрироваться</a>
    </div>
</body>
</html>