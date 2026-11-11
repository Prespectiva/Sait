<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$success = '';
$error = '';

// Обработка отправки отклика
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offer_id'])) {
    $offerId = (int)$_POST['offer_id'];
    $message = trim($_POST['message'] ?? '');
    
    // Проверяем, не откликался ли уже этот пользователь
    $stmt = $db->prepare("SELECT id FROM requests WHERE offer_id = ? AND user_id = ?");
    $stmt->execute([$offerId, $userId]);
    
    if (!$stmt->fetch()) {
        $stmt = $db->prepare("INSERT INTO requests (offer_id, user_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$offerId, $userId, $message]);
        $success = "Отклик успешно отправлен!";
    } else {
        $error = "Вы уже откликались на эту заявку.";
    }
}

// Получаем список моих откликов (где я отправитель)
$stmt = $db->prepare("
    SELECT requests.*, offers.title as offer_title, users.name as author_name 
    FROM requests 
    JOIN offers ON requests.offer_id = offers.id 
    JOIN users ON offers.user_id = users.id
    WHERE requests.user_id = ?
    ORDER BY requests.created_at DESC
");
$stmt->execute([$userId]);
$myRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><title>Отклики | Колледж.Банк</title><link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="top-bar">
            <div><strong>Колледж.Банк</strong></div>
            <div>
                <a href="offers.php" class="nav-link">Заявки</a>
                <a href="requests.php" class="nav-link active">Отклики</a>
            </div>
            <div style="display: flex; align-items: center;">
                <a href="profile.php" class="nav-link" style="margin-right: 15px;">Личный кабинет</a>
                <a href="logout.php" class="logout-btn">Выйти</a>
            </div>
        </div>

        <?php if ($error): ?><div class="msg error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="msg success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div class="content-box" style="text-align: left;">
            <h2>Мои отклики</h2>
            <?php if (empty($myRequests)): ?>
                <p style="color:var(--text-muted); text-align: center; margin-top: 20px;">Вы пока не откликались на заявки.</p>
            <?php else: ?>
                <?php foreach ($myRequests as $req): ?>
                    <div class="offer-card">
                        <h3>Заявка: <?= htmlspecialchars($req['offer_title']) ?></h3>
                        <p class="meta">Автор заявки: <?= htmlspecialchars($req['author_name']) ?> | Статус: <strong><?= htmlspecialchars($req['status']) ?></strong></p>
                        <p><strong>Ваше сообщение:</strong> <?= nl2br(htmlspecialchars($req['message'] ?: 'Без сообщения')) ?></p>
                        <p style="font-size:12px; color:var(--text-muted); margin-top:10px;">Дата: <?= date('d.m.Y', strtotime($req['created_at'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>