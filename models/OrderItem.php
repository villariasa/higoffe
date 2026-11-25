<?php
class OrderItem {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create(int $order_id, int $product_id, ?int $size_id, int $quantity, float $unit_price, float $line_total): int {
        $sql = "INSERT INTO order_items (order_id, product_id, size_id, quantity, unit_price, line_total)
                VALUES (:order_id, :product_id, :size_id, :quantity, :unit_price, :line_total)
                RETURNING order_item_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "order_id"   => $order_id,
            "product_id" => $product_id,
            "size_id"    => $size_id,
            "quantity"   => $quantity,
            "unit_price" => $unit_price,
            "line_total" => $line_total,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function getForOrder(int $order_id) {
        $sql = "SELECT oi.*, p.product_name, ps.size_label
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                LEFT JOIN product_sizes ps ON oi.size_id = ps.size_id
                WHERE oi.order_id = :order_id
                ORDER BY oi.order_item_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["order_id" => $order_id]);
        return $stmt->fetchAll();
    }

    // order_item_addons doesn't get its own Model file (plan §3 doesn't
    // list one) — it's small enough to live here alongside the item it
    // belongs to, avoiding an extra class for a two-column join table.
    public function addAddon(int $order_item_id, int $addon_id, float $addon_price): void {
        $sql = "INSERT INTO order_item_addons (order_item_id, addon_id, addon_price)
                VALUES (:order_item_id, :addon_id, :addon_price)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["order_item_id" => $order_item_id, "addon_id" => $addon_id, "addon_price" => $addon_price]);
    }

    public function getAddonsForItem(int $order_item_id) {
        $sql = "SELECT oia.*, pa.addon_label
                FROM order_item_addons oia
                JOIN product_addons pa ON oia.addon_id = pa.addon_id
                WHERE oia.order_item_id = :order_item_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["order_item_id" => $order_item_id]);
        return $stmt->fetchAll();
    }
}
