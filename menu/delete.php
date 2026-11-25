<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('seller');

$product_id = (int) ($_POST['product_id'] ?? 0);

$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);
$cafe = $cafeModel->getByOwner((int) $_SESSION['user_id']);

$productModel = new Product($pdo);
$product = $productModel->getById($product_id);

if ($cafe && $product && (int) $product['cafe_id'] === (int) $cafe['cafe_id']) {
    $productModel->delete($product_id);
}

header("Location: /menu/index.php");
exit;
