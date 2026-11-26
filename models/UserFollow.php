<?php
require_once __DIR__ . '/../config/IdGenerator.php';

// User-to-user following — separate from models/Follow.php (customer
// follows café) on purpose: two simple tables beat one clever
// polymorphic one.
class UserFollow {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function isFollowing(int $follower_user_id, int $followed_user_id): bool {
        $sql = "SELECT 1 FROM user_follows WHERE follower_user_id = :follower AND followed_user_id = :followed";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["follower" => $follower_user_id, "followed" => $followed_user_id]);
        return (bool) $stmt->fetch();
    }

    public function toggle(int $follower_user_id, int $followed_user_id): bool {
        if ($follower_user_id === $followed_user_id) {
            return false; // can't follow yourself
        }
        if ($this->isFollowing($follower_user_id, $followed_user_id)) {
            $sql = "DELETE FROM user_follows WHERE follower_user_id = :follower AND followed_user_id = :followed";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(["follower" => $follower_user_id, "followed" => $followed_user_id]);
            return false;
        }
        $user_follow_id = IdGenerator::next($this->pdo, 'user_follows', 'user_follow_id');
        $sql = "INSERT INTO user_follows (user_follow_id, follower_user_id, followed_user_id) VALUES (:user_follow_id, :follower, :followed)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["user_follow_id" => $user_follow_id, "follower" => $follower_user_id, "followed" => $followed_user_id]);
        return true;
    }

    public function countFollowers(int $user_id): int {
        $sql = "SELECT COUNT(*) FROM user_follows WHERE followed_user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["user_id" => $user_id]);
        return (int) $stmt->fetchColumn();
    }

    // "Suggested to follow" (desktop right rail): most-followed users the
    // viewer doesn't follow yet (and isn't).
    public function getSuggestions(int $viewer_user_id, int $limit = 5): array {
        $sql = "SELECT u.user_id, u.full_name, u.avatar_url,
                       (SELECT COUNT(*) FROM user_follows f2 WHERE f2.followed_user_id = u.user_id) AS follower_count
                FROM users u
                WHERE u.user_id != :viewer
                  AND u.role != 'admin'
                  AND u.is_suspended = FALSE
                  AND NOT EXISTS (
                      SELECT 1 FROM user_follows f
                      WHERE f.follower_user_id = :viewer2 AND f.followed_user_id = u.user_id
                  )
                ORDER BY follower_count DESC, u.user_id ASC
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":viewer", $viewer_user_id, PDO::PARAM_INT);
        $stmt->bindValue(":viewer2", $viewer_user_id, PDO::PARAM_INT);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countFollowing(int $user_id): int {
        $sql = "SELECT COUNT(*) FROM user_follows WHERE follower_user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["user_id" => $user_id]);
        return (int) $stmt->fetchColumn();
    }
}
