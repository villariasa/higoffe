<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('customer');

$order_id = (int) ($_GET['id'] ?? $_POST['order_id'] ?? 0);

$pdo = (new Database())->connect();
$orderModel = new Order($pdo);
$order = $orderModel->getById($order_id);

if (!$order || (int) $order['customer_user_id'] !== (int) $_SESSION['user_id']) {
    header("Location: /orders/customer_history.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $to_label = $action === 'confirm' ? 'Confirmed' : ($action === 'cancel' ? 'Cancelled' : '');
    if ($to_label !== '') {
        $success = $orderModel->transition($order_id, $order['status_label'], $to_label);
        if (!$success) {
            $error = 'This order already changed status — please refresh.';
        }
        $order = $orderModel->getById($order_id);
    }
}

$orderItemModel = new OrderItem($pdo);
$items = $orderItemModel->getForOrder($order_id);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-xl mx-auto bg-white border border-stone-200 rounded-lg p-6">
    <h1 class="text-xl font-bold mb-2">Order #<?= (int) $order_id ?></h1>
    <p class="text-sm text-stone-500 mb-4">Status: <?= htmlspecialchars($order['status_label'], ENT_QUOTES, 'UTF-8') ?></p>

    <?php if ($error !== ''): ?>
        <p class="bg-red-50 text-red-700 text-sm rounded p-3 mb-4"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <ul class="space-y-2 mb-4 border-b border-stone-200 pb-4">
        <?php foreach ($items as $item): ?>
            <li class="flex justify-between text-sm">
                <span><?= (int) $item['quantity'] ?>&times; <?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?><?php if ($item['size_label']): ?> (<?= htmlspecialchars($item['size_label'], ENT_QUOTES, 'UTF-8') ?>)<?php endif; ?></span>
                <span>&#8369;<?= number_format((float) $item['line_total'], 2) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="text-sm space-y-1 mb-4">
        <p>Subtotal: &#8369;<?= number_format((float) $order['subtotal'], 2) ?></p>
        <p>Additional charges: &#8369;<?= number_format((float) $order['additional_charges'], 2) ?></p>
        <p class="font-semibold">Total: &#8369;<?= number_format((float) $order['total_amount'], 2) ?></p>
        <p><?= htmlspecialchars(ucfirst($order['fulfillment_type']), ENT_QUOTES, 'UTF-8') ?><?php if ($order['delivery_address']): ?> — <?= htmlspecialchars($order['delivery_address'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?></p>
    </div>

    <?php if ($order['status_label'] === 'Pending'): ?>
        <div class="flex gap-3">
            <form method="post"><input type="hidden" name="action" value="confirm"><button type="submit" class="bg-amber-700 text-white rounded px-4 py-2 text-sm font-semibold">Confirm order</button></form>
            <form method="post" onsubmit="return confirm('Cancel this order?');"><input type="hidden" name="action" value="cancel"><button type="submit" class="border border-red-500 text-red-600 rounded px-4 py-2 text-sm font-semibold">Cancel</button></form>
        </div>
    <?php else: ?>
        <p class="text-sm"><a href="/orders/view.php?id=<?= $order_id ?>" class="text-amber-700 underline">Track this order</a></p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
