<?php
require_once __DIR__ . '/../config/IdGenerator.php';

class Notification {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create(int $user_id, string $body, string $link = ''): int {
        $notification_id = IdGenerator::next($this->pdo, 'notifications', 'notification_id');
        $sql = "INSERT INTO notifications (notification_id, user_id, body, link) VALUES (:notification_id, :user_id, :body, :link)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["notification_id" => $notification_id, "user_id" => $user_id, "body" => $body, "link" => $link ?: null]);
        return $notification_id;
    }

    // Used by poll_notifications.php for browser push notifications —
    // only rows newer than the last one the client has already seen.
    public function getNewForUser(int $user_id, int $after_id) {
        $sql = "SELECT * FROM notifications WHERE user_id = :user_id AND notification_id > :after_id ORDER BY notification_id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["user_id" => $user_id, "after_id" => $after_id]);
        return $stmt->fetchAll();
    }

    public function getLatestId(int $user_id): int {
        $sql = "SELECT COALESCE(MAX(notification_id), 0) FROM notifications WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["user_id" => $user_id]);
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
