<?php
// Shortcut: given cafe_id (and optionally product_id), start-or-reuse a
// conversation and redirect to create_order_card.php with that conversation.
// Called from cafes/view.php and menu/view.php "Order Now" buttons.
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('customer');

$cafe_id    = (int) ($_GET['cafe_id'] ?? 0);
$product_id = (int) ($_GET['product_id'] ?? 0);

if (!$cafe_id) {
    header('Location: /cafes/discover.php');
    exit;
}

$pdo  = (new Database())->connect();
$conv = new Conversation($pdo);
$conversation_id = $conv->startOrReuse((int) $_SESSION['user_id'], $cafe_id);

$redirect = '/orders/create_order_card.php?conversation_id=' . $conversation_id;
if ($product_id) {
    $redirect .= '&prefill_product_id=' . $product_id;
}
header('Location: ' . $redirect);
exit;
