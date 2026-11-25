<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/ProductLike.php';
require_once __DIR__ . '/../includes/auth_check.php';

$product_id = (int) ($_POST['product_id'] ?? 0);

$pdo = (new Database())->connect();
$productLikeModel = new ProductLike($pdo);
$liked = $productLikeModel->toggle($product_id, (int) $_SESSION['user_id']);

header("Content-Type: application/json");
echo json_encode(["liked" => $liked, "like_count" => $productLikeModel->count($product_id)]);
