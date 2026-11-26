<?php
require_once __DIR__ . '/../config/IdGenerator.php';

class CafeReview {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // "Verified buyer" gate: a customer may only review a café where they
    // have at least one Completed order.
    public function canReview(int $customer_user_id, int $cafe_id): bool {
        $sql = "SELECT 1 FROM orders o
                JOIN order_statuses os ON o.status_id = os.status_id
                WHERE o.customer_user_id = :customer_user_id
                  AND o.cafe_id = :cafe_id
                  AND os.status_label = 'Completed'
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["customer_user_id" => $customer_user_id, "cafe_id" => $cafe_id]);
        return (bool) $stmt->fetch();
    }

    // Insert-or-update: re-reviewing replaces the customer's prior review.
    public function upsert(int $cafe_id, int $customer_user_id, int $rating, string $review_text): void {
        // On conflict (re-review), the existing row keeps its own
        // review_id — this generated one is only used if it's a fresh insert.
        $review_id = IdGenerator::next($this->pdo, 'cafe_reviews', 'review_id');
        $sql = "INSERT INTO cafe_reviews (review_id, cafe_id, customer_user_id, rating, review_text)
                VALUES (:review_id, :cafe_id, :customer_user_id, :rating, :review_text)
                ON CONFLICT (cafe_id, customer_user_id)
                DO UPDATE SET rating = EXCLUDED.rating, review_text = EXCLUDED.review_text, created_at = NOW()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "review_id"        => $review_id,
            "cafe_id"          => $cafe_id,
            "customer_user_id" => $customer_user_id,
            "rating"           => $rating,
            "review_text"      => $review_text,
        ]);
    }

    public function getForCafe(int $cafe_id, int $limit = 50) {
        $sql = "SELECT r.*, u.full_name AS reviewer_name, u.avatar_url AS reviewer_avatar_url
                FROM cafe_reviews r
                JOIN users u ON r.customer_user_id = u.user_id
                WHERE r.cafe_id = :cafe_id
                ORDER BY r.created_at DESC
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":cafe_id", $cafe_id, PDO::PARAM_INT);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getOwn(int $cafe_id, int $customer_user_id) {
        $sql = "SELECT * FROM cafe_reviews WHERE cafe_id = :cafe_id AND customer_user_id = :customer_user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["cafe_id" => $cafe_id, "customer_user_id" => $customer_user_id]);
        return $stmt->fetch();
    }
}
