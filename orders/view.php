<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../includes/auth_check.php';

$order_id = (int) ($_GET['id'] ?? 0);

$pdo = (new Database())->connect();
$orderModel = new Order($pdo);
$order = $orderModel->getById($order_id);

if (!$order) {
    header("Location: /index.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

$is_owner_seller = false;
if ($role === 'seller') {
    $cafeModel = new Cafe($pdo);
    $cafe = $cafeModel->getByOwner($user_id);
    $is_owner_seller = $cafe && (int) $order['cafe_id'] === (int) $cafe['cafe_id'];
}

$is_authorized = ($role === 'customer' && (int) $order['customer_user_id'] === $user_id) || $is_owner_seller;

if (!$is_authorized) {
    header("Location: /index.php");
    exit;
}

// Customers confirming a Pending order do that on confirm.php, not here.
if ($role === 'customer' && $order['status_label'] === 'Pending') {
    header("Location: /orders/confirm.php?id={$order_id}");
    exit;
}

$next_statuses = $is_owner_seller ? $orderModel->getAllowedNextStatuses($order['status_label']) : [];

$orderItemModel = new OrderItem($pdo);
$items = $orderItemModel->getForOrder($order_id);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-xl mx-auto bg-white border border-stone-200 rounded-lg p-6">
    <h1 class="text-xl font-bold mb-2">Order #<?= (int) $order_id ?></h1>
    <p class="text-sm text-stone-500 mb-4">Status: <span class="font-semibold"><?= htmlspecialchars($order['status_label'], ENT_QUOTES, 'UTF-8') ?></span></p>

    <ul class="space-y-2 mb-4 border-b border-stone-200 pb-4">
        <?php foreach ($items as $item): ?>
            <li class="flex justify-between text-sm">
                <span><?= (int) $item['quantity'] ?>&times; <?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?><?php if ($item['size_label']): ?> (<?= htmlspecialchars($item['size_label'], ENT_QUOTES, 'UTF-8') ?>)<?php endif; ?></span>
                <span>&#8369;<?= number_format((float) $item['line_total'], 2) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>

    <p class="font-semibold mb-4">Total: &#8369;<?= number_format((float) $order['total_amount'], 2) ?></p>

    <div class="border-t border-stone-200 pt-4 mb-4">
        <p class="text-sm font-semibold mb-2">Payment</p>
        <?php if ($order['payment_status'] === 'unpaid'): ?>
            <?php if ($role === 'customer'): ?>
                <a href="/orders/submit_payment.php?id=<?= (int) $order_id ?>" class="inline-block bg-stone-800 text-white rounded px-4 py-2 text-sm font-semibold">Submit payment proof</a>
            <?php else: ?>
                <p class="text-sm text-stone-500">Waiting for the customer to submit payment proof.</p>
            <?php endif; ?>
        <?php elseif ($order['payment_status'] === 'proof_submitted'): ?>
            <?php if (!empty($order['payment_proof_url'])): ?>
                <a href="<?= htmlspecialchars($order['payment_proof_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                    <img src="<?= htmlspecialchars($order['payment_proof_url'], ENT_QUOTES, 'UTF-8') ?>" alt="Payment proof" class="w-32 h-32 object-cover rounded-lg border border-stone-200 mb-2">
                </a>
            <?php endif; ?>
            <?php if ($is_owner_seller): ?>
                <form method="post" action="/orders/confirm_payment.php">
                    <input type="hidden" name="order_id" value="<?= (int) $order_id ?>">
                    <button type="submit" class="bg-amber-700 text-white rounded px-4 py-2 text-sm font-semibold">Confirm payment</button>
                </form>
            <?php else: ?>
                <p class="text-sm text-stone-500">Proof submitted — waiting for the café to confirm.</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-sm text-green-700 font-semibold">&check; Payment confirmed</p>
        <?php endif; ?>
    </div>

    <?php if ($is_owner_seller && !empty($next_statuses)): ?>
        <div class="flex gap-2 flex-wrap">
            <?php foreach ($next_statuses as $status): ?>
                <form method="post" action="/orders/update_status.php">
                    <input type="hidden" name="order_id" value="<?= (int) $order_id ?>">
                    <input type="hidden" name="to_label" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="bg-amber-700 text-white rounded px-4 py-2 text-sm font-semibold">Mark: <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></button>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
