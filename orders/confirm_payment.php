<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('seller');

$order_id = (int) ($_POST['order_id'] ?? 0);

$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);
$cafe = $cafeModel->getByOwner((int) $_SESSION['user_id']);

$orderModel = new Order($pdo);
$order = $orderModel->getById($order_id);

if ($order && $cafe && (int) $order['cafe_id'] === (int) $cafe['cafe_id']) {
    $success = $orderModel->confirmPayment($order_id);
    if ($success) {
        $messageModel = new Message($pdo);
        $messageModel->createSystem((int) $order['conversation_id'], (int) $_SESSION['user_id'], 'Payment confirmed. Thank you!');
    }
}

header("Location: /orders/view.php?id={$order_id}");
exit;
