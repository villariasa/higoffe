<?php
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
        $sql = "INSERT INTO product_addons (product_id, addon_label, addon_price)
                VALUES (:product_id, :addon_label, :addon_price) RETURNING addon_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["product_id" => $product_id, "addon_label" => $addon_label, "addon_price" => $addon_price]);
        return $stmt->fetchColumn();
    }

    public function delete(int $addon_id) {
        $sql = "DELETE FROM product_addons WHERE addon_id = :addon_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["addon_id" => $addon_id]);
    }
}
