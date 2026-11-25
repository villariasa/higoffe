<?php
// Reorder: copies items from a completed/cancelled order into a new DB draft
// for the same café, then redirects to create_order_card.php.
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('customer');

$order_id = (int) ($_GET['order_id'] ?? 0);
$user_id  = (int) $_SESSION['user_id'];

$pdo        = (new Database())->connect();
$orderModel = new Order($pdo);
$order      = $orderModel->getById($order_id);

// Ownership + terminal-state check
if (!$order || (int) $order['customer_user_id'] !== $user_id
    || !in_array($order['status_label'], ['Completed', 'Cancelled'])) {
    header('Location: /orders/customer_history.php');
    exit;
}

// Load original items (with snapshot prices from the DB)
$itemModel = new OrderItem($pdo);
$items     = $itemModel->getForOrder($order_id);  // must exist — returns rows with product_name, size_id, size_label, unit_price, quantity, line_total + addons

// Build a draft array matching the create_order_card format
$draft = [];
foreach ($items as $item) {
    $addons = [];
    if (method_exists($itemModel, 'getAddonsForItem')) {
        foreach ($itemModel->getAddonsForItem((int) $item['order_item_id']) as $a) {
            $addons[] = ['addon_id' => (int) $a['addon_id'], 'addon_label' => $a['addon_label'] ?? '', 'addon_price' => (float) $a['addon_price']];
        }
    }
    $draft[] = [
        'product_id'   => (int)   $item['product_id'],
        'product_name' => (string) $item['product_name'],
        'size_id'      => isset($item['size_id']) && $item['size_id'] !== '' ? (int) $item['size_id'] : null,
        'size_label'   => $item['size_label'] ?? null,
        'addons'       => $addons,
        'quantity'     => (int)   $item['quantity'],
        'unit_price'   => (float) $item['unit_price'],
        'line_total'   => (float) $item['line_total'],
    ];
}

// Start or reuse a conversation with the same café
$convModel       = new Conversation($pdo);
$conversation_id = $convModel->startOrReuse($user_id, (int) $order['cafe_id']);

// Save draft to DB (upsert)
$st = $pdo->prepare(
    'INSERT INTO order_drafts (conversation_id, customer_user_id, items, updated_at)
     VALUES (:c, :u, :i, NOW())
     ON CONFLICT (conversation_id, customer_user_id)
     DO UPDATE SET items = EXCLUDED.items, updated_at = NOW()'
);
$st->execute([':c' => $conversation_id, ':u' => $user_id, ':i' => json_encode($draft)]);

header('Location: /orders/create_order_card.php?conversation_id=' . $conversation_id);
exit;
