<?php
require_once __DIR__ . '/../config/IdGenerator.php';

class ProductLike {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Toggle like/unlike. Returns the new liked state (true = now liked).
    public function toggle(int $product_id, int $user_id): bool {
        $check = $this->pdo->prepare("SELECT like_id FROM product_likes WHERE product_id = :product_id AND user_id = :user_id");
        $check->execute(["product_id" => $product_id, "user_id" => $user_id]);
        $existing = $check->fetch();

        if ($existing) {
            $del = $this->pdo->prepare("DELETE FROM product_likes WHERE like_id = :like_id");
            $del->execute(["like_id" => $existing['like_id']]);
            return false;
        }

        $like_id = IdGenerator::next($this->pdo, 'product_likes', 'like_id');
        $ins = $this->pdo->prepare("INSERT INTO product_likes (like_id, product_id, user_id) VALUES (:like_id, :product_id, :user_id)");
        $ins->execute(["like_id" => $like_id, "product_id" => $product_id, "user_id" => $user_id]);
        return true;
    }

    public function count(int $product_id): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM product_likes WHERE product_id = :product_id");
        $stmt->execute(["product_id" => $product_id]);
        return (int) $stmt->fetchColumn();
    }
}
