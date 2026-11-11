<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
    header('Location: offers.php');
    exit;
}

$users = $db->query("SELECT id, name, email, role, created_at FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><title>Админ-панель | Колледж.Банк</title><link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="top-bar">
        <div><strong>Колледж.Банк (Админ)</strong></div>
        <div>
            <a href="offers.php" class="nav-link">Заявки</a>
            <a href="requests.php" class="nav-link">Отклики</a>
            <a href="admin.php" class="nav-link active" style="color: red;">Админ-панель</a>
        </div>
        <div style="display: flex; align-items: center;">
            <a href="profile.php" class="nav-link" style="margin-right: 15px;">Личный кабинет</a>
            <a href="logout.php" class="logout-btn">Выйти</a>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="content-box" style="text-align: left;">
            <h2>Управление пользователями</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Дата</th>
                </tr>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td style="font-weight: bold; color: <?= $u['role'] == 'admin' ? 'red' : 'green' ?>;">
                            <?= $u['role'] ?>
                        </td>
                        <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>