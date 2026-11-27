<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/PayMongo.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('customer');

header('Content-Type: application/json');

$order_id = (int) ($_POST['order_id'] ?? $_GET['id'] ?? 0);
$pdo = (new Database())->connect();
$orderModel = new Order($pdo);
$order = $orderModel->getById($order_id);

if (!$order || (int) $order['customer_user_id'] !== (int) $_SESSION['user_id'] || $order['payment_status'] !== 'unpaid') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Order is not eligible for payment.']);
    exit;
}

$res = PayMongo::createQrPhIntent($order);
if (!$res['ok']) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $res['error'] ?? 'Could not generate QR Ph code.']);
    exit;
}

$_SESSION['paymongo_intent_' . $order_id] = $res['intent_id'];

echo json_encode([
    'ok' => true,
    'qr_image' => $res['qr_image'],
    'amount' => number_format((float) $order['total_amount'], 2),
]);
