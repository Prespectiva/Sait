<?php
class OfferModel {
    public function __construct(private PDO $pdo) {}

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
}
?>