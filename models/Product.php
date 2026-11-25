<?php
class Product {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getByCafe(int $cafe_id) {
        $sql = "SELECT * FROM products WHERE cafe_id = :cafe_id ORDER BY product_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["cafe_id" => $cafe_id]);
        return $stmt->fetchAll();
    }

    public function getAvailableByCafe(int $cafe_id) {
        $sql = "SELECT * FROM products WHERE cafe_id = :cafe_id AND is_available = TRUE ORDER BY product_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["cafe_id" => $cafe_id]);
        return $stmt->fetchAll();
    }

    // "Trending drinks" row on the Home screen — available products
    // ranked by how often they've actually been ordered, newest first as
    // the tiebreak so a fresh menu still fills the row.
    public function getTrending(int $limit = 10) {
        $sql = "SELECT p.*, c.cafe_name,
                       (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.product_id = p.product_id) AS times_ordered
                FROM products p
                JOIN cafes c ON p.cafe_id = c.cafe_id
                WHERE p.is_available = TRUE
                ORDER BY times_ordered DESC, p.created_at DESC
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById(int $product_id) {
        $sql = "SELECT * FROM products WHERE product_id = :product_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["product_id" => $product_id]);
        return $stmt->fetch();
    }

    public function create(int $cafe_id, string $product_name, string $description, float $base_price, string $image_url) {
        $sql = "INSERT INTO products (cafe_id, product_name, description, base_price, image_url)
                VALUES (:cafe_id, :product_name, :description, :base_price, :image_url)
                RETURNING product_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "cafe_id"      => $cafe_id,
            "product_name" => $product_name,
            "description"  => $description,
            "base_price"   => $base_price,
            "image_url"    => $image_url,
        ]);
        return $stmt->fetchColumn();
    }

    public function update(int $product_id, string $product_name, string $description, float $base_price, string $image_url, bool $is_available) {
        $sql = "UPDATE products
                SET product_name = :product_name, description = :description, base_price = :base_price,
                    image_url = :image_url, is_available = :is_available
                WHERE product_id = :product_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "product_name" => $product_name,
            "description"  => $description,
            "base_price"   => $base_price,
            "image_url"    => $image_url,
            "is_available" => $is_available,
            "product_id"   => $product_id,
        ]);
    }

    public function delete(int $product_id) {
        $sql = "DELETE FROM products WHERE product_id = :product_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["product_id" => $product_id]);
    }
}
