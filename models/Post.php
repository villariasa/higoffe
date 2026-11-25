<?php
class Post {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Main social feed — joins author info (user_follows-aware callers can
    // filter client-side or via $only_cafe_id/$only_author_id), a cafe tag
    // if the post has one, like count, comment count, and whether the
    // viewer already liked it. A post is either café-authored (cafe_id
    // set, authored by the owner) or a personal post (cafe_id NULL).
    public function getFeed(int $viewer_user_id, ?int $only_cafe_id = null, ?int $only_author_id = null) {
        $sql = "SELECT p.*,
                       u.full_name AS author_name, u.avatar_url AS author_avatar_url,
                       c.cafe_name, c.logo_url,
                       (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.post_id) AS like_count,
                       (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.post_id) AS comment_count,
                       EXISTS (
                           SELECT 1 FROM post_likes pl2
                           WHERE pl2.post_id = p.post_id AND pl2.user_id = :viewer_user_id
                       ) AS liked_by_viewer
                FROM posts p
                JOIN users u ON p.author_user_id = u.user_id
                LEFT JOIN cafes c ON p.cafe_id = c.cafe_id";
        $params = ["viewer_user_id" => $viewer_user_id];
        $conditions = [];
        if ($only_cafe_id !== null) {
            $conditions[] = "p.cafe_id = :cafe_id";
            $params["cafe_id"] = $only_cafe_id;
        }
        if ($only_author_id !== null) {
            $conditions[] = "p.author_user_id = :author_id";
            $params["author_id"] = $only_author_id;
        }
        if ($conditions) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
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

    // $cafe_id is NULL for a personal post; set it only when the author is
    // posting as the café they own (feed/create_post.php enforces that).
    public function create(int $author_user_id, ?int $cafe_id, string $caption, string $image_url) {
        $sql = "INSERT INTO posts (author_user_id, cafe_id, caption, image_url)
                VALUES (:author_user_id, :cafe_id, :caption, :image_url) RETURNING post_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "author_user_id" => $author_user_id,
            "cafe_id"        => $cafe_id,
            "caption"        => $caption,
            "image_url"      => $image_url,
        ]);
        return $stmt->fetchColumn();
    }

    public function delete(int $post_id) {
        $sql = "DELETE FROM posts WHERE post_id = :post_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["post_id" => $post_id]);
    }
}
