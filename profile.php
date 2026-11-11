<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $skills = trim($_POST['skills'] ?? '');

    $stmt = $db->prepare("UPDATE users SET name = ?, bio = ?, skills = ? WHERE id = ?");
    $stmt->execute([$name, $bio, $skills, $userId]);
    $success = "Профиль успешно обновлен!";
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><title>Личный кабинет | Колледж.Банк</title><link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="top-bar">
            <div><strong>Колледж.Банк</strong></div>
            <div>
                <a href="offers.php" class="nav-link">Заявки</a>
                <a href="requests.php" class="nav-link">Отклики</a>
            </div>
            <div style="display: flex; align-items: center;">
                <a href="profile.php" class="nav-link active" style="margin-right: 15px;">Личный кабинет</a>
                <a href="logout.php" class="logout-btn">Выйти</a>
            </div>
        </div>

        <?php if ($success): ?><div class="msg success" style="margin-bottom:20px; text-align:center;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="content-box" style="text-align: left;">
                <h2 style="margin-bottom: 20px;">Мой профиль</h2>
                <p style="margin-bottom: 10px;"><strong>Имя:</strong> <?= htmlspecialchars($user['name']) ?></p>
                <p style="margin-bottom: 10px;"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p style="margin-bottom: 10px;"><strong>Дата регистрации:</strong> <?= date('d.m.Y', strtotime($user['created_at'])) ?></p>
                
                <hr style="margin: 20px 0; border: none; border-top: 1px solid var(--border);">
                
                <p style="margin-bottom: 10px;"><strong>О себе:</strong><br><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
                <p style="margin-bottom: 20px;"><strong>Навыки:</strong><br><?= nl2br(htmlspecialchars($user['skills'])) ?></p>
            </div>

            <div class="content-box" style="text-align: left;">
                <h2 style="margin-bottom: 20px;">Редактировать профиль</h2>
                <form method="POST" action="">
                    <input type="hidden" name="update_profile" value="1">
                    <label style="font-size: 14px; color: var(--text-muted);">Имя</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                    
                    <label style="font-size: 14px; color: var(--text-muted);">О себе</label>
                    <textarea name="bio" rows="3" style="width:100%; padding:10px; margin-bottom:15px; background:#f9fafb; border:1px solid var(--border); border-radius:6px; font-family:inherit; resize:vertical;"><?= htmlspecialchars($user['bio']) ?></textarea>
                    
                    <label style="font-size: 14px; color: var(--text-muted);">Навыки</label>
                    <textarea name="skills" rows="3" style="width:100%; padding:10px; margin-bottom:15px; background:#f9fafb; border:1px solid var(--border); border-radius:6px; font-family:inherit; resize:vertical;"><?= htmlspecialchars($user['skills']) ?></textarea>
                    
                    <button type="submit">Сохранить изменения</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>