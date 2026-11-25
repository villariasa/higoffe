<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('seller');

$order_id = (int) ($_POST['order_id'] ?? 0);
$to_label = (string) ($_POST['to_label'] ?? '');

$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);
$cafe = $cafeModel->getByOwner((int) $_SESSION['user_id']);

$orderModel = new Order($pdo);
$order = $orderModel->getById($order_id);

// Ownership check — a seller can only transition their own café's orders.
if ($order && $cafe && (int) $order['cafe_id'] === (int) $cafe['cafe_id']) {
    $orderModel->transition($order_id, $order['status_label'], $to_label);
}

header("Location: /orders/view.php?id={$order_id}");
exit;
