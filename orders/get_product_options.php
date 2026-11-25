<?php
// AJAX endpoint: returns sizes + addons for a given product as JSON.
// Called by create_order_card.php JS when the user picks a product,
// so the page never reloads just to show size/addon options.
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ProductSize.php';
require_once __DIR__ . '/../models/ProductAddon.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('customer');

header('Content-Type: application/json');

$product_id = (int) ($_GET['product_id'] ?? 0);
if (!$product_id) {
    echo json_encode(['sizes' => [], 'addons' => [], 'base_price' => 0, 'image_url' => '']);
    exit;
}

$pdo  = (new Database())->connect();
$prod = (new Product($pdo))->getById($product_id);
if (!$prod) {
    echo json_encode(['sizes' => [], 'addons' => [], 'base_price' => 0, 'image_url' => '']);
    exit;
}

$sizes  = (new ProductSize($pdo))->getByProduct($product_id);
$addons = (new ProductAddon($pdo))->getByProduct($product_id);

echo json_encode([
    'base_price' => (float) $prod['base_price'],
    'image_url'  => $prod['image_url'] ?? '',
    'sizes'      => array_map(fn($s) => [
        'size_id'     => (int)   $s['size_id'],
        'size_label'  => (string)$s['size_label'],
        'price_addon' => (float) $s['price_addon'],
    ], $sizes),
    'addons'     => array_map(fn($a) => [
        'addon_id'    => (int)   $a['addon_id'],
        'addon_label' => (string)$a['addon_label'],
        'addon_price' => (float) $a['addon_price'],
    ], $addons),
]);
