<?php
require_once __DIR__ . '/../config/IdGenerator.php';

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
                       tc.cafe_name AS tagged_cafe_name,
                       tp.product_name AS tagged_product_name, tp.image_url AS tagged_product_image,
                       tp.base_price AS tagged_product_price, tp.cafe_id AS tagged_product_cafe_id,
                       (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.post_id) AS like_count,
                       (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.post_id) AS comment_count,
                       EXISTS (
                           SELECT 1 FROM post_likes pl2
                           WHERE pl2.post_id = p.post_id AND pl2.user_id = :viewer_user_id
                       ) AS liked_by_viewer
                FROM posts p
                JOIN users u ON p.author_user_id = u.user_id
                LEFT JOIN cafes c ON p.cafe_id = c.cafe_id
                LEFT JOIN cafes tc ON p.tagged_cafe_id = tc.cafe_id
                LEFT JOIN products tp ON p.product_id = tp.product_id AND tp.is_deleted = FALSE";
        $params = ["viewer_user_id" => $viewer_user_id];
        $conditions = ["p.is_deleted = FALSE"];
        if ($only_cafe_id !== null) {
            // A café's page shows posts authored AS the café plus
            // check-ins tagged at it — including cafe_only posts, since
            // the café page IS their intended audience.
            $conditions[] = "(p.cafe_id = :cafe_id OR p.tagged_cafe_id = :cafe_id)";
            $params["cafe_id"] = $only_cafe_id;
        } else {
            // Global feed / profiles: cafe_only posts are hidden from
            // everyone except their own author.
            $conditions[] = "(p.visibility = 'public' OR p.author_user_id = :viewer_vis)";
            $params["viewer_vis"] = $viewer_user_id;
        }
        if ($only_author_id !== null) {
            $conditions[] = "p.author_user_id = :author_id";
            $params["author_id"] = $only_author_id;
        }
        $sql .= " WHERE " . implode(" AND ", $conditions);
        $sql .= " ORDER BY p.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Single post with the same author/café/likes/comments metadata the
    // feed query returns — used by the permalink page (feed/post.php).
    public function getByIdWithMeta(int $viewer_user_id, int $post_id) {
        $sql = "SELECT p.*,
                       u.full_name AS author_name, u.avatar_url AS author_avatar_url,
                       c.cafe_name, c.logo_url,
                       tc.cafe_name AS tagged_cafe_name,
                       tp.product_name AS tagged_product_name, tp.image_url AS tagged_product_image,
                       tp.base_price AS tagged_product_price, tp.cafe_id AS tagged_product_cafe_id,
                       (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.post_id) AS like_count,
                       (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.post_id) AS comment_count,
                       EXISTS (
                           SELECT 1 FROM post_likes pl2
                           WHERE pl2.post_id = p.post_id AND pl2.user_id = :viewer_user_id
                       ) AS liked_by_viewer
                FROM posts p
                JOIN users u ON p.author_user_id = u.user_id
                LEFT JOIN cafes c ON p.cafe_id = c.cafe_id
                LEFT JOIN cafes tc ON p.tagged_cafe_id = tc.cafe_id
                LEFT JOIN products tp ON p.product_id = tp.product_id AND tp.is_deleted = FALSE
                WHERE p.post_id = :post_id AND p.is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["viewer_user_id" => $viewer_user_id, "post_id" => $post_id]);
        return $stmt->fetch();
    }

    public function getById(int $post_id) {
        $sql = "SELECT * FROM posts WHERE post_id = :post_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["post_id" => $post_id]);
        return $stmt->fetch();
    }

    // $cafe_id is NULL for a personal post; set it only when the author is
    // posting as the café they own (feed/create_post.php enforces that).
    // $product_id optionally tags the drink/menu item the post is about.
    public function create(int $author_user_id, ?int $cafe_id, string $caption, string $image_url, ?int $product_id = null, ?int $tagged_cafe_id = null, string $visibility = 'public') {
        $visibility = $visibility === 'cafe_only' ? 'cafe_only' : 'public';
        $post_id = IdGenerator::next($this->pdo, 'posts', 'post_id');
        $sql = "INSERT INTO posts (post_id, author_user_id, cafe_id, tagged_cafe_id, product_id, visibility, caption, image_url)
                VALUES (:post_id, :author_user_id, :cafe_id, :tagged_cafe_id, :product_id, :visibility, :caption, :image_url)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "post_id"        => $post_id,
            "author_user_id" => $author_user_id,
            "cafe_id"        => $cafe_id,
            "tagged_cafe_id" => $tagged_cafe_id,
            "product_id"     => $product_id,
            "visibility"     => $visibility,
            "caption"        => $caption,
            "image_url"      => $image_url,
        ]);
        return $post_id;
    }

    // Soft delete: the row is flagged, not destroyed — likes/comments
    // stay intact and the post is recoverable straight from the DB.
    public function delete(int $post_id) {
        $sql = "UPDATE posts SET is_deleted = TRUE WHERE post_id = :post_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["post_id" => $post_id]);
    }
}
