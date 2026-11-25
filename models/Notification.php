<?php
class Notification {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create(int $user_id, string $body, string $link = ''): int {
        $sql = "INSERT INTO notifications (user_id, body, link) VALUES (:user_id, :body, :link) RETURNING notification_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["user_id" => $user_id, "body" => $body, "link" => $link ?: null]);
        return (int) $stmt->fetchColumn();
    }

    public function getForUser(int $user_id, int $limit = 50) {
        $sql = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countUnread(int $user_id): int {
        $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["user_id" => $user_id]);
        return (int) $stmt->fetchColumn();
    }

    public function markAllRead(int $user_id): void {
        $sql = "UPDATE notifications SET is_read = TRUE WHERE user_id = :user_id AND is_read = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["user_id" => $user_id]);
    }
}
