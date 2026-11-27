<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/PayMongo.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('customer');

$order_id = (int) ($_POST['order_id'] ?? $_GET['id'] ?? 0);
$pdo = (new Database())->connect();
$orderModel = new Order($pdo);
$order = $orderModel->getById($order_id);

if (!$order || (int) $order['customer_user_id'] !== (int) $_SESSION['user_id'] || $order['payment_status'] !== 'unpaid') {
    header("Location: /orders/view.php?id={$order_id}");
    exit;
}

if (!PayMongo::isConfigured()) {
    header("Location: /orders/view.php?id={$order_id}&pay_error=" . urlencode('Online payment is not configured yet.'));
    exit;
}

$items = (new OrderItem($pdo))->getForOrder($order_id);
$baseUrl = PayMongo::baseUrl();
$returnUrl = "{$baseUrl}/orders/paymongo_return.php?id={$order_id}";
$cancelUrl = "{$baseUrl}/orders/view.php?id={$order_id}";

$res = PayMongo::createCheckoutSession($order, $items, $returnUrl, $cancelUrl);
if (!$res['ok']) {
    $err = $res['error'] ?? 'Could not start PayMongo checkout.';
    header("Location: /orders/view.php?id={$order_id}&pay_error=" . urlencode($err));
    exit;
}

$sessionId = (string) ($res['data']['id'] ?? '');
$checkoutUrl = (string) ($res['data']['attributes']['checkout_url'] ?? '');

if ($sessionId !== '') {
    $_SESSION['paymongo_session_' . $order_id] = $sessionId;
}

if ($checkoutUrl === '') {
    header("Location: /orders/view.php?id={$order_id}&pay_error=" . urlencode('Missing PayMongo checkout URL.'));
    exit;
}

header("Location: {$checkoutUrl}");
exit;
