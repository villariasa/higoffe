<?php
class PostLike {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Toggle like/unlike for this user on this post. Returns the new
    // liked state (true = now liked).
    public function toggle(int $post_id, int $user_id): bool {
        $check = $this->pdo->prepare("SELECT like_id FROM post_likes WHERE post_id = :post_id AND user_id = :user_id");
        $check->execute(["post_id" => $post_id, "user_id" => $user_id]);
        $existing = $check->fetch();

        if ($existing) {
            $del = $this->pdo->prepare("DELETE FROM post_likes WHERE like_id = :like_id");
            $del->execute(["like_id" => $existing['like_id']]);
            return false;
        }

        $ins = $this->pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (:post_id, :user_id)");
        $ins->execute(["post_id" => $post_id, "user_id" => $user_id]);
        return true;
    }
}
