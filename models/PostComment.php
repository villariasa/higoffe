<?php
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
        $sql = "INSERT INTO post_comments (post_id, user_id, comment_text)
                VALUES (:post_id, :user_id, :comment_text) RETURNING comment_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["post_id" => $post_id, "user_id" => $user_id, "comment_text" => $comment_text]);
        return (int) $stmt->fetchColumn();
    }

    public function delete(int $comment_id): bool {
        $sql = "DELETE FROM post_comments WHERE comment_id = :comment_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["comment_id" => $comment_id]);
    }
}
