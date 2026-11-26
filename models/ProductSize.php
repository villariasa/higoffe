<?php
require_once __DIR__ . '/../config/IdGenerator.php';

class ProductSize {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getByProduct(int $product_id) {
        $sql = "SELECT * FROM product_sizes WHERE product_id = :product_id ORDER BY size_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["product_id" => $product_id]);
        return $stmt->fetchAll();
    }

    public function getById(int $size_id) {
        $sql = "SELECT * FROM product_sizes WHERE size_id = :size_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["size_id" => $size_id]);
        return $stmt->fetch();
    }

    public function create(int $product_id, string $size_label, float $price_addon) {
        $size_id = IdGenerator::next($this->pdo, 'product_sizes', 'size_id');
        $sql = "INSERT INTO product_sizes (size_id, product_id, size_label, price_addon)
                VALUES (:size_id, :product_id, :size_label, :price_addon)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["size_id" => $size_id, "product_id" => $product_id, "size_label" => $size_label, "price_addon" => $price_addon]);
        return $size_id;
    }

    public function delete(int $size_id) {
        $sql = "DELETE FROM product_sizes WHERE size_id = :size_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["size_id" => $size_id]);
    }
}
