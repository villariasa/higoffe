<?php
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
        $sql = "INSERT INTO user_follows (follower_user_id, followed_user_id) VALUES (:follower, :followed)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["follower" => $follower_user_id, "followed" => $followed_user_id]);
        return true;
    }

    public function countFollowers(int $user_id): int {
        $sql = "SELECT COUNT(*) FROM user_follows WHERE followed_user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["user_id" => $user_id]);
        return (int) $stmt->fetchColumn();
    }

    public function countFollowing(int $user_id): int {
        $sql = "SELECT COUNT(*) FROM user_follows WHERE follower_user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["user_id" => $user_id]);
        return (int) $stmt->fetchColumn();
    }
}
