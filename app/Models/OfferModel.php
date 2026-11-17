<?php
class OfferModel {
    public function __construct(private PDO $pdo) {}

    // Для каталога (MVC)
    public function getActive(): array {
        $stmt = $this->pdo->prepare(
            'SELECT o.*, u.name as author_name, c.name as cat_name 
             FROM offers o 
             JOIN users u ON o.user_id = u.id 
             JOIN categories c ON o.category_id = c.id 
             WHERE o.status = ? 
             ORDER BY o.created_at DESC'
        );
        $stmt->execute(['active']);
        return $stmt->fetchAll();
    }

    // Для API: получить все
    public function getAllApi(): array {
        $stmt = $this->pdo->prepare(
            'SELECT o.*, u.name as author_name 
             FROM offers o 
             JOIN users u ON o.user_id = u.id 
             ORDER BY o.created_at DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Для API: получить по ID
    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT o.*, u.name as author_name 
             FROM offers o 
             JOIN users u ON o.user_id = u.id 
             WHERE o.id = ?'
        );
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    // Для API: создать
    public function create(string $title, string $description, int $userId, string $type): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO offers (title, description, user_id, type, category_id) 
             VALUES (?, ?, ?, ?, 1)'
        );
        $stmt->execute([$title, $description, $userId, $type]);
        return (int)$this->pdo->lastInsertId();
    }

    // Для API: проверка существования пользователя (Добавлено по замечанию)
    public function userExists(int $id): bool {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return (bool)$stmt->fetch();
    }
}
?>