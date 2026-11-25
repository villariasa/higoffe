<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Notification.php';
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
    $success = $orderModel->transition($order_id, $order['status_label'], $to_label);
    // Notify the customer about the status change.
    if ($success) {
        $statusMessages = [
            'Confirmed'        => 'Your order has been confirmed! 🎉',
            'Preparing'        => 'Your order is being prepared. ☕',
            'Ready for Pickup' => 'Your order is ready for pickup!',
            'Out for Delivery' => 'Your order is out for delivery! 🛵',
            'Completed'        => 'Your order is complete. Thank you!',
            'Cancelled'        => 'Your order was cancelled. Please contact the café for details.',
        ];
        $msg = $statusMessages[$to_label] ?? "Order status updated to {$to_label}.";
        (new Notification($pdo))->create(
            (int) $order['customer_user_id'],
            $msg,
            '/orders/view.php?id=' . $order_id
        );
    }
}

header("Location: /orders/view.php?id={$order_id}");
exit;
