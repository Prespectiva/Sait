<?php
require 'db.php';
session_start();

$isAuth = isset($_SESSION['user_id']);
$userId = $isAuth ? (int)$_SESSION['user_id'] : 0;
$isAdmin = $isAuth && (($_SESSION['role'] ?? '') === 'admin');
$msg = '';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if ($isAuth && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
        die("Ошибка CSRF-токена!");
    }

    $action = $_POST['action'];
    $id = (int)($_POST['offer_id'] ?? 0);
    
    if ($action === 'create') {
        $stmt = $db->prepare("INSERT INTO offers (user_id, category_id, type, title, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, (int)$_POST['category_id'], $_POST['type'], $_POST['title'], $_POST['description']]);
        $msg = "Заявка создана!";
    } elseif ($action === 'update') {
        $stmt = $db->prepare("SELECT user_id FROM offers WHERE id = ?");
        $stmt->execute([$id]);
        if ($isAdmin || $stmt->fetchColumn() == $userId) {
            $db->prepare("UPDATE offers SET title=?, description=?, type=?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
               ->execute([$_POST['title'], $_POST['description'], $_POST['type'], $id]);
            $msg = "Заявка обновлена!";
        }
    } elseif ($action === 'delete') {
        $stmt = $db->prepare("SELECT user_id FROM offers WHERE id = ?");
        $stmt->execute([$id]);
        if ($isAdmin || $stmt->fetchColumn() == $userId) {
            $db->prepare("DELETE FROM offers WHERE id = ?")->execute([$id]);
            $msg = "Заявка удалена.";
        }
    }
}

$q = trim($_GET['q'] ?? '');
$fType = $_GET['type'] ?? '';
$fCat = (int)($_GET['category'] ?? 0);
$sort = $_GET['sort'] ?? 'date_desc';

$sqlBase = "FROM offers o 
            JOIN users u ON o.user_id = u.id 
            JOIN categories c ON o.category_id = c.id 
            WHERE o.status = 'active'";
$params = [];

if ($q) { $sqlBase .= " AND (o.title LIKE ? OR o.description LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($fType) { $sqlBase .= " AND o.type = ?"; $params[] = $fType; }
if ($fCat) { $sqlBase .= " AND o.category_id = ?"; $params[] = $fCat; }

$limit = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$countStmt = $db->prepare("SELECT COUNT(*) " . $sqlBase);
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);
$offset = ($page - 1) * $limit;

$sorts = ['date_desc' => 'o.created_at DESC', 'date_asc' => 'o.created_at ASC', 'title_asc' => 'o.title ASC', 'title_desc' => 'o.title DESC'];
$sql = "SELECT o.*, u.name as author_name, c.name as cat_name " . $sqlBase;
$sql .= " ORDER BY " . ($sorts[$sort] ?? $sorts['date_desc']);
$sql .= " LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$categories = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

$edit = null;
if ($isAuth && isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM offers WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><title>Заявки | Колледж.Банк</title><link rel="stylesheet" href="style.css">
    <script>function toggleForm() { var f = document.getElementById('createForm'); f.style.display = (f.style.display === 'none') ? 'block' : 'none'; }</script>
</head>
<body>
    <div class="top-bar">
        <div><strong>Колледж.Банк</strong></div>
        <div>
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
        <?php if ($msg): ?><div class="msg success"><?= $msg ?></div><?php endif; ?>

        <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0;">Каталог публикаций</h2>
            <?php if ($isAuth && !$edit): ?><button onclick="toggleForm()" style="width: auto; padding: 10px 20px;">+ Создать</button><?php endif; ?>
        </div>

        <div class="content-box" style="margin-bottom: 20px; padding: 15px;">
            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                
                <div style="flex: 1 1 200px;">
                    <label style="font-size:12px; display:block; margin-bottom:5px;">Поиск</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" style="margin:0; width:100%;">
                </div>
                
                <div style="flex: 1 1 120px;">
                    <label style="font-size:12px; display:block; margin-bottom:5px;">Тип</label>
                    <select name="type" style="margin:0; width:100%;">
                        <option value="">Все</option>
                        <option value="help" <?= $fType=='help'?'selected':'' ?>>Помощь</option>
                        <option value="skill" <?= $fType=='skill'?'selected':'' ?>>Навык</option>
                        <option value="request" <?= $fType=='request'?'selected':'' ?>>Запрос</option>
                    </select>
                </div>
                
                <div style="flex: 1 1 120px;">
                    <label style="font-size:12px; display:block; margin-bottom:5px;">Категория</label>
                    <select name="category" style="margin:0; width:100%;">
                        <option value="">Все</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $fCat==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="flex: 1 1 150px;">
                    <label style="font-size:12px; display:block; margin-bottom:5px;">Сортировка</label>
                    <select name="sort" style="margin:0; width:100%;">
                        <option value="date_desc" <?= $sort=='date_desc'?'selected':'' ?>>Сначала новые</option>
                        <option value="date_asc" <?= $sort=='date_asc'?'selected':'' ?>>Сначала старые</option>
                        <option value="title_asc" <?= $sort=='title_asc'?'selected':'' ?>>А-Я</option>
                        <option value="title_desc" <?= $sort=='title_desc'?'selected':'' ?>>Я-А</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; flex: 0 0 auto;">
                    <button type="submit" style="height: 38px; width: auto; padding: 0 20px;">Найти</button>
                    <a href="offers.php" class="btn-edit" style="height: 38px; margin: 0; display: flex; align-items: center; padding: 0 20px; box-sizing: border-box;">Сброс</a>
                </div>
            </form>
        </div>

        <?php if ($isAuth): ?>
        <div class="content-box" id="createForm" style="margin-bottom: 20px; display: <?= $edit ? 'block' : 'none' ?>;">
            <h2><?= $edit ? 'Редактировать' : 'Новая заявка' ?></h2>
            <form method="POST">
                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
                <?php if ($edit): ?><input type="hidden" name="offer_id" value="<?= $edit['id'] ?>"><?php endif; ?>
                
                <input type="text" name="title" value="<?= $edit ? htmlspecialchars($edit['title']) : '' ?>" placeholder="Заголовок" required>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                    <select name="type" style="margin:0;">
                        <option value="help" <?= $edit && $edit['type']=='help'?'selected':'' ?>>Помощь</option>
                        <option value="skill" <?= $edit && $edit['type']=='skill'?'selected':'' ?>>Навык</option>
                        <option value="request" <?= $edit && $edit['type']=='request'?'selected':'' ?>>Запрос</option>
                    </select>
                    <?php if (!$edit): ?>
                        <select name="category_id" style="margin:0;">
                            <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
                
                <textarea name="description" rows="3" placeholder="Описание" required><?= $edit ? htmlspecialchars($edit['description']) : '' ?></textarea>
                <button type="submit" style="width: 150px;"><?= $edit ? 'Сохранить' : 'Опубликовать' ?></button>
            </form>
        </div>
        <?php endif; ?>

        <?php if (empty($offers)): ?>
            <p style="text-align:center; color:var(--text-muted);">Ничего не найдено.</p>
        <?php else: ?>
            <?php foreach ($offers as $o): ?>
                <div class="offer-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <h3>
                            <span class="badge" style="background:var(--accent); color:white; margin-right:8px; text-transform:uppercase; font-size:10px;">
                                <?= htmlspecialchars($o['type']) ?>
                            </span> 
                            <?= htmlspecialchars($o['title']) ?>
                        </h3>
                        <span class="badge"><?= htmlspecialchars($o['cat_name']) ?></span>
                    </div>
                    <p class="meta">Автор: <?= htmlspecialchars($o['author_name']) ?> | <?= date('d.m.Y', strtotime($o['created_at'])) ?></p>
                    <p><?= nl2br(htmlspecialchars($o['description'])) ?></p>
                    
                    <?php if ($isAuth): ?>
                    <div class="action-buttons">
                        <?php if ($o['user_id'] == $userId || $isAdmin): ?>
                            <a href="offers.php?edit=<?= $o['id'] ?>" class="btn-edit">Изменить</a>
                            <form method="POST" onsubmit="return confirm('Удалить?');" style="margin: 0;">
                                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="offer_id" value="<?= $o['id'] ?>">
                                <button type="submit" class="btn-delete">Удалить</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="requests.php" style="margin: 0; display: flex; gap: 10px; width: 100%;">
                                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                                <input type="hidden" name="offer_id" value="<?= $o['id'] ?>">
                                <input type="hidden" name="receiver_id" value="<?= $o['user_id'] ?>">
                                <input type="text" name="message" placeholder="Ваше сообщение..." style="margin: 0; height: 38px;">
                                <button type="submit" style="width: auto; padding: 0 15px; height: 38px;">Отправить заявку</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <?php if ($totalPages > 1): ?>
            <div style="display:flex; justify-content:center; gap:10px; margin-top:20px;">
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                       class="btn-edit" style="<?= $i == $page ? 'background:var(--accent); color:white;' : '' ?>">
                       <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>