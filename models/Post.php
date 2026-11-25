<?php
class Post {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Feed query — joins cafe display info, a like count, and whether the
    // current viewer already liked each post. Pass $only_cafe_id to scope
    // to a single café's posts (used by cafes/view.php).
    public function getFeed(int $viewer_user_id, ?int $only_cafe_id = null) {
        $sql = "SELECT p.*, c.cafe_name, c.logo_url,
                       (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.post_id) AS like_count,
                       EXISTS (
                           SELECT 1 FROM post_likes pl2
                           WHERE pl2.post_id = p.post_id AND pl2.user_id = :viewer_user_id
                       ) AS liked_by_viewer
                FROM posts p
                JOIN cafes c ON p.cafe_id = c.cafe_id";
        $params = ["viewer_user_id" => $viewer_user_id];
        if ($only_cafe_id !== null) {
            $sql .= " WHERE p.cafe_id = :cafe_id";
            $params["cafe_id"] = $only_cafe_id;
        }
        $sql .= " ORDER BY p.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $post_id) {
        $sql = "SELECT * FROM posts WHERE post_id = :post_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["post_id" => $post_id]);
        return $stmt->fetch();
    }

    public function create(int $cafe_id, string $caption, string $image_url) {
        $sql = "INSERT INTO posts (cafe_id, caption, image_url)
                VALUES (:cafe_id, :caption, :image_url) RETURNING post_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["cafe_id" => $cafe_id, "caption" => $caption, "image_url" => $image_url]);
        return $stmt->fetchColumn();
    }

    public function delete(int $post_id) {
        $sql = "DELETE FROM posts WHERE post_id = :post_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["post_id" => $post_id]);
    }
}
