<?php
require_once __DIR__ . '/../config/IdGenerator.php';

class PostComment {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getForPost(int $post_id) {
        $sql = "SELECT pc.*, u.full_name AS commenter_name, u.avatar_url AS commenter_avatar_url
                FROM post_comments pc
                JOIN users u ON pc.user_id = u.user_id
                WHERE pc.post_id = :post_id
                ORDER BY pc.created_at ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["post_id" => $post_id]);
        return $stmt->fetchAll();
    }

    public function create(int $post_id, int $user_id, string $comment_text): int {
        $comment_id = IdGenerator::next($this->pdo, 'post_comments', 'comment_id');
        $sql = "INSERT INTO post_comments (comment_id, post_id, user_id, comment_text)
                VALUES (:comment_id, :post_id, :user_id, :comment_text)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["comment_id" => $comment_id, "post_id" => $post_id, "user_id" => $user_id, "comment_text" => $comment_text]);
        return $comment_id;
    }

    public function delete(int $comment_id): bool {
        $sql = "DELETE FROM post_comments WHERE comment_id = :comment_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["comment_id" => $comment_id]);
    }
}
