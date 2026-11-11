<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$error = '';
$success = '';

// --- CREATE (Создание) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $catId = (int)($_POST['category_id'] ?? 1);

    if ($title && $desc) {
        $stmt = $db->prepare("INSERT INTO offers (user_id, category_id, title, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $catId, $title, $desc]);
        $success = "Заявка успешно создана!";
    } else {
        $error = "Заполните все поля!";
    }
}

// --- UPDATE (Редактирование) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $offerId = (int)$_POST['offer_id'];
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    $stmt = $db->prepare("SELECT user_id FROM offers WHERE id = ?");
    $stmt->execute([$offerId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || (int)$row['user_id'] !== $userId) {
        http_response_code(403);
        exit('Доступ запрещен.');
    }

    if ($title && $desc) {
        $stmt = $db->prepare("UPDATE offers SET title = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $desc, $offerId, $userId]);
        $success = "Заявка обновлена!";
    }
}

// --- DELETE (Удаление) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $offerId = (int)$_POST['offer_id'];
    $stmt = $db->prepare("SELECT user_id FROM offers WHERE id = ?");
    $stmt->execute([$offerId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && (int)$row['user_id'] === $userId) {
        $stmt = $db->prepare("DELETE FROM offers WHERE id = ? AND user_id = ?");
        $stmt->execute([$offerId, $userId]);
        $success = "Заявка удалена.";
    } else {
        $error = "Вы не можете удалить чужую запись.";
    }
}

// --- READ (Подготовка данных) ---
$editOffer = null;
if (isset($_GET['edit'])) {
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
    WHERE offers.status = 'active'
    ORDER BY offers.id DESC
");
$stmt->execute();
$offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><title>Заявки | Колледж.Банк</title><link rel="stylesheet" href="style.css">
    <script>
        function toggleForm() {
            var formDiv = document.getElementById('createForm');
            if (formDiv.style.display === 'none') {
                formDiv.style.display = 'block';
            } else {
                formDiv.style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <div class="dashboard-container">
        <div class="top-bar">
            <div><strong>Колледж.Банк</strong></div>
            <div>
                <a href="offers.php" class="nav-link active">Заявки</a>
                <a href="requests.php" class="nav-link">Отклики</a>
            </div>
            <div style="display: flex; align-items: center;">
                <a href="profile.php" class="nav-link" style="margin-right: 15px;">Личный кабинет</a>
                <a href="logout.php" class="logout-btn">Выйти</a>
            </div>
        </div>

        <?php if ($error): ?><div class="msg error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="msg success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0;">Каталог заявок</h2>
            <?php if (!$editOffer): ?>
                <button onclick="toggleForm()" style="width: auto; padding: 10px 20px;">+ Создать заявку</button>
            <?php endif; ?>
        </div>

        <div class="content-box" id="createForm" style="margin-bottom: 20px; text-align: left; display: <?= $editOffer ? 'block' : 'none' ?>;">
            <h2><?= $editOffer ? 'Редактировать заявку' : 'Новая заявка' ?></h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?= $editOffer ? 'update' : 'create' ?>">
                <?php if ($editOffer): ?>
                    <input type="hidden" name="offer_id" value="<?= $editOffer['id'] ?>">
                <?php endif; ?>

                <label>Заголовок</label>
                <input type="text" name="title" value="<?= $editOffer ? htmlspecialchars($editOffer['title']) : '' ?>" required>
                
                <?php if (!$editOffer): ?>
                    <label>Категория</label>
                    <select name="category_id" style="width:100%; padding:10px; margin-bottom:15px; border-radius:6px; border:1px solid var(--border);">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <label>Описание</label>
                <textarea name="description" rows="3" style="width:100%; padding:10px; margin-bottom:15px; border-radius:6px; border:1px solid var(--border);" required><?= $editOffer ? htmlspecialchars($editOffer['description']) : '' ?></textarea>
                
                <button type="submit" style="width: 200px;"><?= $editOffer ? 'Сохранить' : 'Опубликовать' ?></button>
                <?php if ($editOffer): ?>
                    <a href="offers.php" style="margin-left: 15px;">Отмена</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="content-box" style="text-align: left;">
            <?php if (empty($offers)): ?>
                <p style="color:var(--text-muted); text-align: center;">Пока нет ни одной заявки.</p>
            <?php else: ?>
                <?php foreach ($offers as $offer): ?>
                    <div class="offer-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <h3><?= htmlspecialchars($offer['title']) ?></h3>
                            <span class="badge"><?= htmlspecialchars($offer['cat_name']) ?></span>
                        </div>
                        <p class="meta">Автор: <?= htmlspecialchars($offer['author_name']) ?> | Дата: <?= date('d.m.Y', strtotime($offer['created_at'])) ?></p>
                        <p><?= nl2br(htmlspecialchars($offer['description'])) ?></p>
                        
                        <!-- Блок действий -->
                        <div class="action-buttons">
                            <?php if ($offer['user_id'] == $userId): ?>
                                <!-- Если это МОЯ заявка: Редактировать и Удалить -->
                                <a href="offers.php?edit=<?= $offer['id'] ?>" class="btn-edit">Редактировать</a>
                                <form method="POST" action="" onsubmit="return confirm('Удалить эту заявку?');" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="offer_id" value="<?= $offer['id'] ?>">
                                    <button type="submit" class="btn-delete">Удалить</button>
                                </form>
                            <?php else: ?>
                                <!-- Если ЧУЖАЯ заявка: Откликнуться -->
                                <form method="POST" action="requests.php" style="margin: 0; display: flex; gap: 10px; width: 100%;">
                                    <input type="hidden" name="offer_id" value="<?= $offer['id'] ?>">
                                    <input type="text" name="message" placeholder="Ваше сообщение..." style="margin: 0; flex-grow: 1; padding: 8px 12px; height: 38px;">
                                    <button type="submit" style="width: auto; padding: 0 20px; height: 38px; white-space: nowrap;">Откликнуться</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>