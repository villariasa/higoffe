<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/PayMongo.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('customer');

$order_id = (int) ($_GET['id'] ?? 0);
$pdo = (new Database())->connect();
$orderModel = new Order($pdo);
$order = $orderModel->getById($order_id);

if (!$order || (int) $order['customer_user_id'] !== (int) $_SESSION['user_id']) {
    header("Location: /orders/view.php?id={$order_id}");
    exit;
}

if ($order['payment_status'] !== 'confirmed' && PayMongo::isConfigured()) {
    $sessionId = (string) ($_SESSION['paymongo_session_' . $order_id] ?? '');
    if ($sessionId !== '') {
        $res = PayMongo::getCheckoutSession($sessionId);
        if ($res['ok']) {
            $attrs = $res['data']['attributes'] ?? [];
            $payments = $attrs['payments'] ?? [];
            $isPaid = ($attrs['payment_intent']['attributes']['status'] ?? '') === 'succeeded';
            if (!$isPaid && is_array($payments)) {
                foreach ($payments as $p) {
                    if (($p['attributes']['status'] ?? '') === 'paid') {
                        $isPaid = true;
                        break;
                    }
                }
            }
            if ($isPaid && $orderModel->confirmOnlinePayment($order_id)) {
                $messageModel = new Message($pdo);
                $messageModel->createSystem((int) $order['conversation_id'], (int) $_SESSION['user_id'], 'Online payment confirmed via PayMongo.');
            }
        }
    }
}

header("Location: /orders/view.php?id={$order_id}");
exit;
