<?php
require_once __DIR__ . '/../config/IdGenerator.php';

// Extra photos for a multi-image post (posts.image_url already holds the
// first one). Kept as its own small model rather than folded into
// Post.php, the same way OrderItem.php owns order_item_addons — a join
// table small enough not to need its own page, but queried on its own.
class PostImage {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create(int $post_id, string $image_url, int $position): int {
        $post_image_id = IdGenerator::next($this->pdo, 'post_images', 'post_image_id');
        $sql = "INSERT INTO post_images (post_image_id, post_id, image_url, position)
                VALUES (:post_image_id, :post_id, :image_url, :position)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "post_image_id" => $post_image_id,
            "post_id"       => $post_id,
            "image_url"     => $image_url,
            "position"      => $position,
        ]);
        return $post_image_id;
    }

    public function getForPost(int $post_id) {
        $sql = "SELECT * FROM post_images WHERE post_id = :post_id ORDER BY position ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["post_id" => $post_id]);
        return $stmt->fetchAll();
    }

    // Batch fetch for the feed — one query for every post on the page
    // instead of one per post.
    public function getForPosts(array $post_ids): array {
        if (empty($post_ids)) return [];
        $placeholders = implode(',', array_fill(0, count($post_ids), '?'));
        $sql = "SELECT * FROM post_images WHERE post_id IN ({$placeholders}) ORDER BY post_id, position ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($post_ids));
        $rows = $stmt->fetchAll();
        $byPost = [];
        foreach ($rows as $row) {
            $byPost[(int) $row['post_id']][] = $row;
        }
        return $byPost;
    }
}
