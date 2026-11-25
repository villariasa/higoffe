<?php
class Lookup {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getOrderStatuses() {
        $sql = "SELECT * FROM order_statuses ORDER BY status_id";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getOrderStatusLabel(int $status_id): string {
        $sql = "SELECT status_label FROM order_statuses WHERE status_id = :status_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["status_id" => $status_id]);
        return (string) $stmt->fetchColumn();
    }
}
