<?php
require_once __DIR__ . '/../config/IdGenerator.php';

class Product {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getByCafe(int $cafe_id) {
        $sql = "SELECT * FROM products WHERE cafe_id = :cafe_id AND is_deleted = FALSE ORDER BY product_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["cafe_id" => $cafe_id]);
        return $stmt->fetchAll();
    }

    // Social aggregates appended wherever products are displayed.
    // Expects the products table aliased as `p`.
    private const SOCIAL_COLS = ", (SELECT ROUND(AVG(pr.rating)::numeric, 1) FROM product_reviews pr WHERE pr.product_id = p.product_id) AS rating_avg
                                 , (SELECT COUNT(*) FROM product_reviews pr2 WHERE pr2.product_id = p.product_id) AS rating_count
                                 , (SELECT COUNT(*) FROM product_likes plk WHERE plk.product_id = p.product_id) AS like_count";

    public function getAvailableByCafe(int $cafe_id, ?int $viewer_user_id = null) {
        $liked_col = $viewer_user_id !== null
            ? ", EXISTS (SELECT 1 FROM product_likes plv WHERE plv.product_id = p.product_id AND plv.user_id = :viewer) AS liked_by_viewer"
            : "";
        $sql = "SELECT p.*" . self::SOCIAL_COLS . $liked_col . "
                FROM products p
                WHERE p.cafe_id = :cafe_id AND p.is_available = TRUE AND p.is_deleted = FALSE
                ORDER BY p.product_id";
        $stmt = $this->pdo->prepare($sql);
        $params = ["cafe_id" => $cafe_id];
        if ($viewer_user_id !== null) {
            $params["viewer"] = $viewer_user_id;
        }
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Product detail page: one product with café info + social aggregates.
    public function getByIdWithMeta(int $product_id, int $viewer_user_id) {
        $sql = "SELECT p.*, c.cafe_name, c.logo_url AS cafe_logo_url" . self::SOCIAL_COLS . ",
                       EXISTS (SELECT 1 FROM product_likes plv WHERE plv.product_id = p.product_id AND plv.user_id = :viewer) AS liked_by_viewer
                FROM products p
                JOIN cafes c ON p.cafe_id = c.cafe_id
                WHERE p.product_id = :product_id AND p.is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["product_id" => $product_id, "viewer" => $viewer_user_id]);
        return $stmt->fetch();
    }

    // "Trending drinks" row on the Home screen — available products
    // ranked by how often they've actually been ordered, newest first as
    // the tiebreak so a fresh menu still fills the row.
    public function getTrending(int $limit = 10) {
        $sql = "SELECT p.*, c.cafe_name" . self::SOCIAL_COLS . ",
                       (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.product_id = p.product_id) AS times_ordered
                FROM products p
                JOIN cafes c ON p.cafe_id = c.cafe_id
                WHERE p.is_available = TRUE AND p.is_deleted = FALSE
                ORDER BY times_ordered DESC, p.created_at DESC
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById(int $product_id) {
        $sql = "SELECT * FROM products WHERE product_id = :product_id AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["product_id" => $product_id]);
        return $stmt->fetch();
    }

    public function create(int $cafe_id, string $product_name, string $description, float $base_price, string $image_url) {
        $product_id = IdGenerator::next($this->pdo, 'products', 'product_id');
        $sql = "INSERT INTO products (product_id, cafe_id, product_name, description, base_price, image_url)
                VALUES (:product_id, :cafe_id, :product_name, :description, :base_price, :image_url)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "product_id"   => $product_id,
            "cafe_id"      => $cafe_id,
            "product_name" => $product_name,
            "description"  => $description,
            "base_price"   => $base_price,
            "image_url"    => $image_url,
        ]);
        return $product_id;
    }

    public function update(int $product_id, string $product_name, string $description, float $base_price, string $image_url, bool $is_available) {
        $sql = "UPDATE products
                SET product_name = :product_name, description = :description, base_price = :base_price,
                    image_url = :image_url, is_available = :is_available
                WHERE product_id = :product_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "product_name" => $product_name,
            "description"  => $description,
            "base_price"   => $base_price,
            "image_url"    => $image_url,
            // pdo_pgsql turns PHP false into '' which Postgres rejects
            // for boolean columns — bind as 'true'/'false' strings.
            "is_available" => $is_available ? 'true' : 'false',
            "product_id"   => $product_id,
        ]);
    }

    // Soft delete: flag the row and hide it from the menu. A hard DELETE
    // would also violate FKs for any product that was ever ordered
    // (order_items references it) — history must survive the menu.
    public function delete(int $product_id) {
        $sql = "UPDATE products SET is_deleted = TRUE, is_available = FALSE WHERE product_id = :product_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["product_id" => $product_id]);
    }
}
