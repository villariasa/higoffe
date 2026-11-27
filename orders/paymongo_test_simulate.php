<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/PayMongo.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('customer');

header('Content-Type: application/json');

if (!PayMongo::isTestMode()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Simulation is only available in PayMongo Test Mode.']);
    exit;
}

$order_id = (int) ($_POST['order_id'] ?? 0);
$pdo = (new Database())->connect();
$orderModel = new Order($pdo);
$order = $orderModel->getById($order_id);

if (!$order || (int) $order['customer_user_id'] !== (int) $_SESSION['user_id'] || $order['payment_status'] !== 'unpaid') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Order is not eligible for payment.']);
    exit;
}

unset($_SESSION['paymongo_qr_' . $order_id], $_SESSION['paymongo_intent_' . $order_id]);

if ($orderModel->confirmOnlinePayment($order_id)) {
    $messageModel = new Message($pdo);
    $messageModel->createSystem((int) $order['conversation_id'], (int) $_SESSION['user_id'], 'Online payment confirmed via QR Ph (test mode).');
}

echo json_encode(['ok' => true, 'paid' => true]);
