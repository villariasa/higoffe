<?php
class Order {
    private PDO $pdo;

    // Allowed status transitions — see plan §5. Enforced in PHP before the
    // DB is touched, then enforced again by the DB-side WHERE guard below
    // so a stale/duplicate click from two devices can't double-advance.
    private array $allowedTransitions = [
        'Pending'          => ['Confirmed', 'Cancelled'],
        'Confirmed'        => ['Preparing', 'Cancelled'],
        'Preparing'        => ['Ready for Pickup', 'Out for Delivery'],
        'Out for Delivery' => ['Completed'],
        'Ready for Pickup' => ['Completed'],
    ];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getById(int $order_id) {
        $sql = "SELECT o.*, os.status_label
                FROM orders o
                JOIN order_statuses os ON o.status_id = os.status_id
                WHERE o.order_id = :order_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["order_id" => $order_id]);
        return $stmt->fetch();
    }

    public function create(int $conversation_id, int $customer_user_id, int $cafe_id, string $fulfillment_type, string $delivery_address, float $subtotal, float $additional_charges): int {
        $total_amount = $subtotal + $additional_charges;
        $sql = "INSERT INTO orders (conversation_id, customer_user_id, cafe_id, status_id, fulfillment_type, delivery_address, subtotal, additional_charges, total_amount)
                VALUES (:conversation_id, :customer_user_id, :cafe_id,
                        (SELECT status_id FROM order_statuses WHERE status_label = 'Pending'),
                        :fulfillment_type, :delivery_address, :subtotal, :additional_charges, :total_amount)
                RETURNING order_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "conversation_id"    => $conversation_id,
            "customer_user_id"   => $customer_user_id,
            "cafe_id"            => $cafe_id,
            "fulfillment_type"   => $fulfillment_type,
            "delivery_address"   => $delivery_address,
            "subtotal"           => $subtotal,
            "additional_charges" => $additional_charges,
            "total_amount"       => $total_amount,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function getForSellerByStatus(int $cafe_id, string $status_label) {
        $sql = "SELECT o.*, u.full_name AS customer_name
                FROM orders o
                JOIN order_statuses os ON o.status_id = os.status_id
                JOIN users u ON o.customer_user_id = u.user_id
                WHERE o.cafe_id = :cafe_id AND os.status_label = :status_label
                ORDER BY o.created_at ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["cafe_id" => $cafe_id, "status_label" => $status_label]);
        return $stmt->fetchAll();
    }

    public function getForCustomer(int $customer_user_id) {
        $sql = "SELECT o.*, os.status_label, c.cafe_name
                FROM orders o
                JOIN order_statuses os ON o.status_id = os.status_id
                JOIN cafes c ON o.cafe_id = c.cafe_id
                WHERE o.customer_user_id = :customer_user_id
                ORDER BY o.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["customer_user_id" => $customer_user_id]);
        return $stmt->fetchAll();
    }

    // Guarded atomic transition. Returns false if the transition is
    // illegal, or if the order's status already moved on since the
    // caller last read it (rowCount() === 0) — see plan §5 / §8 scenario 2.
    public function transition(int $order_id, string $from_label, string $to_label): bool {
        if (!in_array($to_label, $this->allowedTransitions[$from_label] ?? [], true)) {
            return false;
        }
        $sql = "UPDATE orders o
                SET status_id = (SELECT status_id FROM order_statuses WHERE status_label = :to_label),
                    updated_at = NOW()
                WHERE o.order_id = :order_id
                  AND o.status_id = (SELECT status_id FROM order_statuses WHERE status_label = :from_label)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["to_label" => $to_label, "from_label" => $from_label, "order_id" => $order_id]);
        return $stmt->rowCount() === 1;
    }

    public function getAllowedNextStatuses(string $from_label): array {
        return $this->allowedTransitions[$from_label] ?? [];
    }

    // Admin counts dashboard
    public function countAll(): int {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    }

    public function sumRevenueThisWeek(): float {
        $sql = "SELECT COALESCE(SUM(total_amount), 0) FROM orders
                WHERE created_at >= NOW() - INTERVAL '7 days'
                  AND status_id = (SELECT status_id FROM order_statuses WHERE status_label = 'Completed')";
        return (float) $this->pdo->query($sql)->fetchColumn();
    }
}
