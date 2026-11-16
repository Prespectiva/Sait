<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Каталог | Колледж.Банк</title>
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
    <div class="top-bar">
        <div><strong>Колледж.Банк</strong></div>
        <div>
            <a href="index.php" class="nav-link active">Заявки</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="requests.php" class="nav-link">Отклики</a>
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <a href="admin.php" class="nav-link" style="color: red;">Админ-панель</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div style="display: flex; align-items: center;">
            <button class="theme-toggle" onclick="toggleTheme()" title="Сменить тему">🌓</button>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php" class="nav-link" style="margin-right: 15px;">Личный кабинет</a>
                <a href="logout.php" class="logout-btn">Выйти</a>
            <?php else: ?>
                <a href="login.php" class="nav-link">Вход</a>
                <a href="register.php" class="nav-link active">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard-container">
        <h2>Каталог публикаций</h2>
        
        <div style="margin-top: 20px;">
            <?php if (empty($offers)): ?>
                <p style="color:var(--text-muted);">Нет активных публикаций.</p>
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
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>