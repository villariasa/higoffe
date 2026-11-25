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
