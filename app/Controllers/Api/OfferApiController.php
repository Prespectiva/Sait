<?php
require_once __DIR__ . '/../../Models/OfferModel.php';

class OfferApiController {
    private OfferModel $model;

    public function __construct(PDO $pdo) {
        $this->model = new OfferModel($pdo);
    }

    private function response(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function index(): void {
        $offers = $this->model->getAllApi();
        $this->response($offers);
    }

    public function show(int $id): void {
        $offer = $this->model->getById($id);
        if (!$offer) {
            $this->response(['error' => 'Публикация не найдена'], 404);
        }
        $this->response($offer);
    }

    public function store(): void {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->response(['error' => 'Некорректный JSON'], 400);
        }

        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $userId = (int)($data['user_id'] ?? 0);
        $type = $data['type'] ?? '';

        if (empty($title) || empty($description) || !$userId || !in_array($type, ['help', 'skill', 'request'])) {
            $this->response(['error' => 'Проверьте введённые данные'], 400);
        }

        $newId = $this->model->create($title, $description, $userId, $type);
        
        $this->response([
            'message' => 'Публикация создана',
            'id' => $newId
        ], 201);
    }
}
?>