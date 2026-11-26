<?php
// AJAX fragment: the same order detail as orders/view.php, minus the page
// chrome (no header/footer) — fetched by seller_dashboard.php and
// customer_history.php to expand an order inline instead of navigating
// away. orders/view.php itself still exists for deep-links (notification
// clicks, chat "View / respond" links).
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Uploads.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';
require_once __DIR__ . '/../includes/image_upload_field.php';

$order_id = (int) ($_GET['id'] ?? 0);

$pdo = (new Database())->connect();
$orderModel = new Order($pdo);
$order = $orderModel->getById($order_id);

if (!$order) {
    http_response_code(404);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

$is_owner_seller = false;
if ($role === 'seller') {
    $cafeModel = new Cafe($pdo);
    $cafe = $cafeModel->getByOwner($user_id);
    $is_owner_seller = $cafe && (int) $order['cafe_id'] === (int) $cafe['cafe_id'];
}

$is_authorized = ($role === 'customer' && (int) $order['customer_user_id'] === $user_id) || $is_owner_seller;

if (!$is_authorized) {
    http_response_code(403);
    exit;
}

$next_statuses = $is_owner_seller ? $orderModel->getAllowedNextStatuses($order['status_label']) : [];

$orderItemModel = new OrderItem($pdo);
$items = $orderItemModel->getForOrder($order_id);

$timeline = $order['fulfillment_type'] === 'delivery'
    ? ['Pending', 'Confirmed', 'Preparing', 'Out for Delivery', 'Completed']
    : ['Pending', 'Confirmed', 'Preparing', 'Ready for Pickup', 'Completed'];
$short_labels = $order['fulfillment_type'] === 'delivery'
    ? ['Pending', 'Confirmed', 'Preparing', 'Delivery', 'Done']
    : ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Done'];
$current_step = array_search($order['status_label'], $timeline, true);
$is_cancelled = $order['status_label'] === 'Cancelled';

$upload_auth = ($role === 'customer' && $order['payment_status'] === 'unpaid')
    ? Uploads::issueToken($user_id, 'payment_proof')
    : null;

require __DIR__ . '/_order_detail.php';
