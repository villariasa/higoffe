<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/PayMongo.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('customer');

header('Content-Type: application/json');

$order_id = (int) ($_GET['id'] ?? 0);
$pdo = (new Database())->connect();
$orderModel = new Order($pdo);
$order = $orderModel->getById($order_id);

if (!$order || (int) $order['customer_user_id'] !== (int) $_SESSION['user_id']) {
    echo json_encode(['ok' => false, 'paid' => false]);
    exit;
}

if ($order['payment_status'] === 'confirmed') {
    echo json_encode(['ok' => true, 'paid' => true]);
    exit;
}

$intentId = (string) ($_SESSION['paymongo_intent_' . $order_id] ?? '');
if ($intentId !== '' && PayMongo::isConfigured()) {
    $res = PayMongo::getPaymentIntent($intentId);
    if ($res['ok']) {
        $status = (string) ($res['data']['attributes']['status'] ?? '');
        if ($status === 'succeeded') {
            unset($_SESSION['paymongo_qr_' . $order_id], $_SESSION['paymongo_intent_' . $order_id]);
            if ($orderModel->confirmOnlinePayment($order_id)) {
                $messageModel = new Message($pdo);
                $messageModel->createSystem((int) $order['conversation_id'], (int) $_SESSION['user_id'], 'Online payment confirmed via QR Ph.');
            }
            echo json_encode(['ok' => true, 'paid' => true]);
            exit;
        }
    }
}

echo json_encode(['ok' => true, 'paid' => false]);
