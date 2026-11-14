<?php
require 'db.php';
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $password) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = "Этот Email уже занят!";
        } else {
            $countUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $role = ($countUsers == 0) ? 'admin' : 'user';

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hash, $role]);
            
            header("Location: login.php?registered=1");
            exit;
        }
    } else {
        $error = "Заполните все поля корректно!";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><title>Регистрация | Колледж.Банк</title>
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
        <h2>Регистрация</h2>
        <?php if ($error): ?><div class="msg error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="name" placeholder="Ваше имя" required>
            <input type="email" name="email" placeholder="Email (логин)" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Создать аккаунт</button>
        </form>
        <a href="login.php">Уже есть аккаунт? Войти</a>
    </div>
</body>
</html>