<?php
require_once __DIR__ . '/../config/IdGenerator.php';

class ProductAddon {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getByProduct(int $product_id) {
        $sql = "SELECT * FROM product_addons WHERE product_id = :product_id ORDER BY addon_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["product_id" => $product_id]);
        return $stmt->fetchAll();
    }

    public function getById(int $addon_id) {
        $sql = "SELECT * FROM product_addons WHERE addon_id = :addon_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["addon_id" => $addon_id]);
        return $stmt->fetch();
    }

    public function create(int $product_id, string $addon_label, float $addon_price) {
        $addon_id = IdGenerator::next($this->pdo, 'product_addons', 'addon_id');
        $sql = "INSERT INTO product_addons (addon_id, product_id, addon_label, addon_price)
                VALUES (:addon_id, :product_id, :addon_label, :addon_price)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["addon_id" => $addon_id, "product_id" => $product_id, "addon_label" => $addon_label, "addon_price" => $addon_price]);
        return $addon_id;
    }

    public function delete(int $addon_id) {
        $sql = "DELETE FROM product_addons WHERE addon_id = :addon_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["addon_id" => $addon_id]);
    }
}
