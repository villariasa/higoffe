<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/PayMongo.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Message.php';

http_response_code(200);
header('Content-Type: application/json');

$rawBody = (string) file_get_contents('php://input');
$sigHeader = (string) ($_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '');

if (PayMongo::webhookSecret() !== '' && !PayMongo::verifyWebhookSignature($rawBody, $sigHeader)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Invalid signature']);
    exit;
}

$event = json_decode($rawBody, true);
$eventType = (string) ($event['data']['attributes']['type'] ?? '');

if ($eventType === 'checkout_session.payment.paid') {
    $resource = $event['data']['attributes']['data']['attributes'] ?? [];
    $order_id = (int) ($resource['metadata']['order_id'] ?? 0);

    if ($order_id > 0) {
        $pdo = (new Database())->connect();
        $orderModel = new Order($pdo);
        $order = $orderModel->getById($order_id);
        if ($order && $orderModel->confirmOnlinePayment($order_id)) {
            $messageModel = new Message($pdo);
            $messageModel->createSystem(
                (int) $order['conversation_id'],
                (int) $order['customer_user_id'],
                'Online payment confirmed via PayMongo.'
            );
        }
    }
}

echo json_encode(['ok' => true]);
