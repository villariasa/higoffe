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

$page_title = 'Review order #' . $order_id;
$back_url = '/orders/customer_history.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-3 px-4 py-4 lg:mx-auto lg:max-w-xl lg:px-0">
    <?php if ($error !== ''): ?>
        <p class="rounded-xl bg-red-50 p-3 text-sm text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <div class="rounded-2xl bg-white p-4">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">The café proposed this order</p>
        <ul class="space-y-2">
            <?php foreach ($items as $item): ?>
                <li class="flex justify-between text-sm">
                    <span class="text-stone-700"><?= (int) $item['quantity'] ?>&times; <?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?><?php if ($item['size_label']): ?> <span class="text-stone-400">(<?= htmlspecialchars($item['size_label'], ENT_QUOTES, 'UTF-8') ?>)</span><?php endif; ?></span>
                    <span class="font-semibold">&#8369;<?= number_format((float) $item['line_total'], 2) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="mt-3 space-y-1 border-t border-stone-100 pt-2.5 text-sm">
            <p class="flex justify-between text-stone-500"><span>Subtotal</span><span>&#8369;<?= number_format((float) $order['subtotal'], 2) ?></span></p>
            <p class="flex justify-between text-stone-500"><span>Additional charges</span><span>&#8369;<?= number_format((float) $order['additional_charges'], 2) ?></span></p>
            <p class="flex justify-between text-base font-black text-stone-900"><span>Total</span><span>&#8369;<?= number_format((float) $order['total_amount'], 2) ?></span></p>
            <p class="pt-1 text-[11px] capitalize text-stone-400"><?= htmlspecialchars($order['fulfillment_type'], ENT_QUOTES, 'UTF-8') ?><?php if (!empty($order['delivery_address'])): ?> · <?= htmlspecialchars($order['delivery_address'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?></p>
        </div>
    </div>

    <?php if ($order['status_label'] === 'Pending'): ?>
        <form method="post">
            <input type="hidden" name="action" value="confirm">
            <button type="submit" class="w-full rounded-xl bg-accent py-3 text-sm font-bold text-white active:opacity-80">Confirm order</button>
        </form>
        <form method="post" onsubmit="return confirm('Cancel this order?');">
            <input type="hidden" name="action" value="cancel">
            <button type="submit" class="w-full rounded-xl border border-red-300 py-3 text-sm font-bold text-red-500 active:bg-red-50">Cancel</button>
        </form>
        <p class="text-center text-[11px] text-stone-400">Want changes? Just reply in the chat — the café can send an updated order card.</p>
    <?php else: ?>
        <a href="/orders/view.php?id=<?= $order_id ?>" class="block w-full rounded-xl bg-coffee py-3 text-center text-sm font-bold text-white active:opacity-80">Track this order</a>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
