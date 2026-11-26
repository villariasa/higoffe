<?php
require_once __DIR__ . '/../config/IdGenerator.php';

class Report {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create(int $reporter_user_id, string $target_type, int $target_id, string $reason): int {
        $report_id = IdGenerator::next($this->pdo, 'reports', 'report_id');
        $sql = "INSERT INTO reports (report_id, reporter_user_id, target_type, target_id, reason)
                VALUES (:report_id, :reporter_user_id, :target_type, :target_id, :reason)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "report_id"        => $report_id,
            "reporter_user_id" => $reporter_user_id,
            "target_type"      => $target_type,
            "target_id"        => $target_id,
            "reason"           => $reason,
        ]);
        return $report_id;
    }

    public function getPending() {
        $sql = "SELECT r.*, u.full_name AS reporter_name
                FROM reports r
                JOIN users u ON r.reporter_user_id = u.user_id
                WHERE r.status = 'pending'
                ORDER BY r.created_at ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function resolve(int $report_id): bool {
        $sql = "UPDATE reports SET status = 'resolved' WHERE report_id = :report_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["report_id" => $report_id]);
    }
}
