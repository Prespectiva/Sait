<?php
function getPdo(): PDO {
    // Путь к базе: из папки config поднимаемся на уровень выше
    $pdo = new PDO('sqlite:' . __DIR__ . '/../database.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}
?>