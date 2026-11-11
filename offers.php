<?php
require 'db.php';
session_start();

$isAuth = isset($_SESSION['user_id']);
$userId = $isAuth ? (int)$_SESSION['user_id'] : 0;
$isAdmin = $isAuth && (($_SESSION['role'] ?? '') === 'admin');
$error = '';
$success = '';

// Логика создания/удаления только для авторизованных
if ($isAuth) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $catId = (int)($_POST['category_id'] ?? 1);
        if ($title && $desc) {
            $stmt = $db->prepare("INSERT INTO offers (user_id, category_id, title, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $catId, $title, $desc]);
            $success = "Заявка создана!";
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
        $offerId = (int)$_POST['offer_id'];
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $stmt = $db->prepare("SELECT user_id FROM offers WHERE id = ?");
        $stmt->execute([$offerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && ($isAdmin || (int)$row['user_id'] === $userId)) {
            $stmt = $db->prepare("UPDATE offers SET title = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$title, $desc, $offerId]);
            $success = "Заявка обновлена!";
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
        $offerId = (int)$_POST['offer_id'];
        $stmt = $db->prepare("SELECT user_id FROM offers WHERE id = ?");
        $stmt->execute([$offerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && ($isAdmin || (int)$row['user_id'] === $userId)) {
            $stmt = $db->prepare("DELETE FROM offers WHERE id = ?");
            $stmt->execute([$offerId]);
            $success = "Заявка удалена.";
        }
    }
}

$editOffer = null;
if ($isAuth && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM offers WHERE id = ?");
    $stmt->execute([$editId]);
    $editOffer = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $db->prepare("
    SELECT offers.*, users.name as author_name, categories.name as cat_name 
    FROM offers 
    JOIN users ON offers.user_id = users.id 
    JOIN categories ON offers.category_id = categories.id
    WHERE offers.status = 'active' ORDER BY offers.id DESC
");
$stmt->execute();
$offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$categories = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><title>Заявки | Колледж.Банк</title><link rel="stylesheet" href="style.css">
    <?php if ($isAuth): ?>
    <script>function toggleForm() { var f = document.getElementById('createForm'); f.style.display = (f.style.display === 'none') ? 'block' : 'none'; }</script>
    <?php endif; ?>
</head>
<body>
    <div class="top-bar">
        <div><strong>Колледж.Банк</strong></div>
        <div>
            <!-- Вкладка Главная убрана -->
            <a href="offers.php" class="nav-link active">Заявки</a>
            <?php if ($isAuth): ?>
                <a href="requests.php" class="nav-link">Отклики</a>
                <?php if ($isAdmin): ?><a href="admin.php" class="nav-link" style="color: red;">Админ-панель</a><?php endif; ?>
            <?php endif; ?>
        </div>
        <div style="display: flex; align-items: center;">
            <?php if ($isAuth): ?>
                <a href="profile.php" class="nav-link" style="margin-right: 15px;">Личный кабинет</a>
                <a href="logout.php" class="logout-btn">Выйти</a>
            <?php else: ?>
                <a href="login.php" class="nav-link">Вход</a>
                <a href="register.php" class="nav-link active">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard-container">
        <?php if ($error): ?><div class="msg error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="msg success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0;">Каталог заявок</h2>
            <?php if ($isAuth && !$editOffer): ?>
                <button onclick="toggleForm()" style="width: auto; padding: 10px 20px;">+ Создать</button>
            <?php endif; ?>
        </div>

        <?php if ($isAuth): ?>
        <div class="content-box" id="createForm" style="margin-bottom: 20px; text-align: left; display: <?= $editOffer ? 'block' : 'none' ?>;">
            <h2><?= $editOffer ? 'Редактировать' : 'Новая заявка' ?></h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?= $editOffer ? 'update' : 'create' ?>">
                <?php if ($editOffer): ?><input type="hidden" name="offer_id" value="<?= $editOffer['id'] ?>"><?php endif; ?>
                <input type="text" name="title" value="<?= $editOffer ? htmlspecialchars($editOffer['title']) : '' ?>" placeholder="Заголовок" required>
                <?php if (!$editOffer): ?>
                    <select name="category_id"><?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option><?php endforeach; ?></select>
                <?php endif; ?>
                <textarea name="description" rows="3" placeholder="Описание" required><?= $editOffer ? htmlspecialchars($editOffer['description']) : '' ?></textarea>
                <button type="submit" style="width: 150px;"><?= $editOffer ? 'Сохранить' : 'Опубликовать' ?></button>
            </form>
        </div>
        <?php endif; ?>

        <div style="text-align: left;">
            <?php if (empty($offers)): ?>
                <p style="color:var(--text-muted); text-align: center; margin-top: 20px;">Пока нет ни одной заявки.</p>
            <?php else: ?>
                <?php foreach ($offers as $offer): ?>
                    <div class="offer-card">
                        <div style="display: flex; justify-content: space-between;">
                            <h3><?= htmlspecialchars($offer['title']) ?></h3>
                            <span class="badge"><?= htmlspecialchars($offer['cat_name']) ?></span>
                        </div>
                        <p class="meta">Автор: <?= htmlspecialchars($offer['author_name']) ?> | Дата: <?= date('d.m.Y', strtotime($offer['created_at'])) ?></p>
                        <p><?= nl2br(htmlspecialchars($offer['description'])) ?></p>
                        
                        <?php if ($isAuth): ?>
                        <div class="action-buttons">
                            <?php if ($offer['user_id'] == $userId || $isAdmin): ?>
                                <a href="offers.php?edit=<?= $offer['id'] ?>" class="btn-edit">Редактировать</a>
                                <form method="POST" action="" onsubmit="return confirm('Удалить?');" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="offer_id" value="<?= $offer['id'] ?>">
                                    <button type="submit" class="btn-delete">Удалить</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($offer['user_id'] != $userId): ?>
                                <form method="POST" action="requests.php" style="margin: 0; display: flex; gap: 10px; width: 100%;">
                                    <input type="hidden" name="offer_id" value="<?= $offer['id'] ?>">
                                    <input type="text" name="message" placeholder="Сообщение..." style="margin: 0; height: 38px;">
                                    <button type="submit" style="width: auto; padding: 0 20px; height: 38px;">Откликнуться</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>