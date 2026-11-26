<?php
require_once __DIR__ . '/../config/IdGenerator.php';

class Follow {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function isFollowing(int $customer_user_id, int $cafe_id): bool {
        $sql = "SELECT 1 FROM follows WHERE customer_user_id = :customer_user_id AND cafe_id = :cafe_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["customer_user_id" => $customer_user_id, "cafe_id" => $cafe_id]);
        return (bool) $stmt->fetch();
    }

    public function toggle(int $customer_user_id, int $cafe_id): bool {
        if ($this->isFollowing($customer_user_id, $cafe_id)) {
            $sql = "DELETE FROM follows WHERE customer_user_id = :customer_user_id AND cafe_id = :cafe_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(["customer_user_id" => $customer_user_id, "cafe_id" => $cafe_id]);
            return false;
        }
        $follow_id = IdGenerator::next($this->pdo, 'follows', 'follow_id');
        $sql = "INSERT INTO follows (follow_id, customer_user_id, cafe_id) VALUES (:follow_id, :customer_user_id, :cafe_id)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["follow_id" => $follow_id, "customer_user_id" => $customer_user_id, "cafe_id" => $cafe_id]);
        return true;
    }

    public function getFollowedCafeIds(int $customer_user_id): array {
        $sql = "SELECT cafe_id FROM follows WHERE customer_user_id = :customer_user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["customer_user_id" => $customer_user_id]);
        return array_column($stmt->fetchAll(), 'cafe_id');
    }
}
