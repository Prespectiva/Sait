<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Controllers/Api/OfferApiController.php';

try {
    $pdo = getPdo();
    $controller = new OfferApiController($pdo);

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $controller->show((int)$_GET['id']);
        } else {
            $controller->index();
        }
    } elseif ($method === 'POST') {
        $controller->store();
    } else {
        http_response_code(405);
        header('Allow: GET, POST');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Внутренняя ошибка сервера'], JSON_UNESCAPED_UNICODE);
}
?>