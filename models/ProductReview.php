<?php
require_once __DIR__ . '/../config/IdGenerator.php';

class ProductReview {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Verified-buyer gate: the customer must have a Completed order whose
    // items actually include this product.
    public function canReview(int $customer_user_id, int $product_id): bool {
        $sql = "SELECT 1 FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                JOIN order_statuses os ON o.status_id = os.status_id
                WHERE o.customer_user_id = :customer_user_id
                  AND oi.product_id = :product_id
                  AND os.status_label = 'Completed'
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["customer_user_id" => $customer_user_id, "product_id" => $product_id]);
        return (bool) $stmt->fetch();
    }

    public function upsert(int $product_id, int $customer_user_id, int $rating, string $review_text): void {
        $review_id = IdGenerator::next($this->pdo, 'product_reviews', 'review_id');
        $sql = "INSERT INTO product_reviews (review_id, product_id, customer_user_id, rating, review_text)
                VALUES (:review_id, :product_id, :customer_user_id, :rating, :review_text)
                ON CONFLICT (product_id, customer_user_id)
                DO UPDATE SET rating = EXCLUDED.rating, review_text = EXCLUDED.review_text, created_at = NOW()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "review_id"        => $review_id,
            "product_id"       => $product_id,
            "customer_user_id" => $customer_user_id,
            "rating"           => $rating,
            "review_text"      => $review_text,
        ]);
    }

    public function getForProduct(int $product_id, int $limit = 50) {
        $sql = "SELECT r.*, u.full_name AS reviewer_name, u.avatar_url AS reviewer_avatar_url
                FROM product_reviews r
                JOIN users u ON r.customer_user_id = u.user_id
                WHERE r.product_id = :product_id
                ORDER BY r.created_at DESC
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":product_id", $product_id, PDO::PARAM_INT);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getOwn(int $product_id, int $customer_user_id) {
        $sql = "SELECT * FROM product_reviews WHERE product_id = :product_id AND customer_user_id = :customer_user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["product_id" => $product_id, "customer_user_id" => $customer_user_id]);
        return $stmt->fetch();
    }
}
