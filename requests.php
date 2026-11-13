<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$userId = (int)$_SESSION['user_id'];
$isAdmin = (($_SESSION['role'] ?? '') === 'admin');
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offer_id'])) {
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
        die("Ошибка CSRF-токена!");
    }
    
    $offerId = (int)$_POST['offer_id'];
    $receiverId = (int)$_POST['receiver_id'];
    $message = trim($_POST['message'] ?? '');

    if ($receiverId === $userId) {
        $error = "Вы не можете отправить заявку на свою публикацию.";
    } else {
        $stmt = $db->prepare("SELECT id FROM requests WHERE offer_id = ? AND sender_id = ?");
        $stmt->execute([$offerId, $userId]);
        if ($stmt->fetch()) {
            $error = "Вы уже отправляли заявку на это предложение.";
        } else {
            $stmt = $db->prepare("INSERT INTO requests (offer_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$offerId, $userId, $receiverId, $message]);
            $success = "Заявка успешно отправлена!";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
        die("Ошибка CSRF-токена!");
    }
    
    $reqId = (int)$_POST['request_id'];
    $action = $_POST['action'];

    if ($action === 'accept' || $action === 'reject') {
        $newStatus = ($action === 'accept') ? 'accepted' : 'rejected';
        $stmt = $db->prepare("UPDATE requests SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND receiver_id = ? AND status = 'pending'");
        $stmt->execute([$newStatus, $reqId, $userId]);
        if ($stmt->rowCount()) $success = "Статус обновлен.";
        else $error = "Ошибка: нельзя изменить этот статус.";
    } elseif ($action === 'cancel') {
        $stmt = $db->prepare("UPDATE requests SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ? AND sender_id = ? AND status = 'pending'");
        $stmt->execute([$reqId, $userId]);
        if ($stmt->rowCount()) $success = "Заявка отменена.";
        else $error = "Ошибка: нельзя отменить эту заявку.";
    }
}

$stmt = $db->prepare("
    SELECT r.*, o.title as offer_title, u.name as sender_name 
    FROM requests r 
    JOIN offers o ON r.offer_id = o.id 
    JOIN users u ON r.sender_id = u.id 
    WHERE r.receiver_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$userId]);
$incoming = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("
    SELECT r.*, o.title as offer_title, u.name as receiver_name 
    FROM requests r 
    JOIN offers o ON r.offer_id = o.id 
    JOIN users u ON r.receiver_id = u.id 
    WHERE r.sender_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$userId]);
$outgoing = $stmt->fetchAll(PDO::FETCH_ASSOC);

function statusBadge($status) {
    $colors = ['pending' => '#f59e0b', 'accepted' => '#10b981', 'rejected' => '#ef4444', 'cancelled' => '#6b7280'];
    $names = ['pending' => 'Ожидает', 'accepted' => 'Принята', 'rejected' => 'Отклонена', 'cancelled' => 'Отменена'];
    $color = $colors[$status] ?? '#000';
    $name = $names[$status] ?? $status;
    return "<span style='background:$color; color:white; padding:2px 8px; border-radius:4px; font-size:12px;'>$name</span>";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><title>Отклики | Колледж.Банк</title><link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="top-bar">
        <div><strong>Колледж.Банк</strong></div>
        <div>
            <a href="offers.php" class="nav-link">Заявки</a>
            <a href="requests.php" class="nav-link active">Отклики</a>
            <?php if ($isAdmin): ?><a href="admin.php" class="nav-link" style="color: red;">Админ-панель</a><?php endif; ?>
        </div>
        <div style="display: flex; align-items: center;">
            <a href="profile.php" class="nav-link" style="margin-right: 15px;">Личный кабинет</a>
            <a href="logout.php" class="logout-btn">Выйти</a>
        </div>
    </div>

    <div class="dashboard-container">
        <?php if ($error): ?><div class="msg error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="msg success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            
            <div>
                <h2 style="margin-bottom: 20px;">Входящие</h2>
                <?php if (empty($incoming)): ?><p style="color:var(--text-muted);">Нет входящих заявок.</p><?php endif; ?>
                
                <?php foreach ($incoming as $req): ?>
                    <div class="offer-card" style="border-left: 4px solid <?= $req['status']=='pending'?'#f59e0b':'#d1d5db' ?>;">
                        <h3><?= htmlspecialchars($req['offer_title']) ?></h3>
                        <p class="meta">От: <strong><?= htmlspecialchars($req['sender_name']) ?></strong> | <?= date('d.m.Y H:i', strtotime($req['created_at'])) ?></p>
                        <p style="margin-bottom:15px;"><em>"<?= htmlspecialchars($req['message'] ?: 'Без сообщения') ?>"</em></p>
                        
                        <div style="margin-bottom: 10px;">Статус: <?= statusBadge($req['status']) ?></div>

                        <?php if ($req['status'] === 'pending'): ?>
                            <div style="display:flex; gap:10px;">
                                <form method="POST">
                                    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" style="background:#10b981; width:auto; padding:6px 15px;">Принять</button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" style="background:#ef4444; width:auto; padding:6px 15px;">Отклонить</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div>
                <h2 style="margin-bottom: 20px;">Исходящие</h2>
                <?php if (empty($outgoing)): ?><p style="color:var(--text-muted);">Вы еще не отправляли заявки.</p><?php endif; ?>
                
                <?php foreach ($outgoing as $req): ?>
                    <div class="offer-card">
                        <h3><?= htmlspecialchars($req['offer_title']) ?></h3>
                        <p class="meta">Кому: <strong><?= htmlspecialchars($req['receiver_name']) ?></strong> | <?= date('d.m.Y H:i', strtotime($req['created_at'])) ?></p>
                        <p style="margin-bottom:15px;"><em>"<?= htmlspecialchars($req['message'] ?: 'Без сообщения') ?>"</em></p>
                        
                        <div style="margin-bottom: 10px;">Статус: <?= statusBadge($req['status']) ?></div>

                        <?php if ($req['status'] === 'pending'): ?>
                            <form method="POST">
                                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <input type="hidden" name="action" value="cancel">
                                <button type="submit" style="background:#6b7280; width:auto; padding:6px 15px;">Отменить заявку</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</body>
</html>