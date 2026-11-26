<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/ProductLike.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../includes/auth_check.php';

$product_id = (int) ($_POST['product_id'] ?? 0);
$user_id = (int) $_SESSION['user_id'];

$pdo = (new Database())->connect();
$productLikeModel = new ProductLike($pdo);
$liked = $productLikeModel->toggle($product_id, $user_id);

if ($liked) {
    $product = (new Product($pdo))->getById($product_id);
    $cafe = $product ? (new Cafe($pdo))->getById((int) $product['cafe_id']) : null;
    if ($cafe && (int) $cafe['owner_user_id'] !== $user_id) {
        (new Notification($pdo))->create((int) $cafe['owner_user_id'], $_SESSION['full_name'] . ' liked your menu item "' . $product['product_name'] . '".', '/menu/view.php?id=' . $product_id);
    }
}

header("Content-Type: application/json");
echo json_encode(["liked" => $liked, "like_count" => $productLikeModel->count($product_id)]);
