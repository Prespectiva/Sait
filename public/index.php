<?php
session_start(); // Добавили, чтобы шапка видела, кто вошел
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Controllers/OfferController.php';

$pdo = getPdo();
$model = new OfferModel($pdo);
$controller = new OfferController($model);

$controller->index();
?>