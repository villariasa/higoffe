<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('customer');

$pdo = (new Database())->connect();
$orderModel = new Order($pdo);
$orders = $orderModel->getForCustomer((int) $_SESSION['user_id']);

require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-bold mb-4">My orders</h1>

<div class="bg-white border border-stone-200 rounded-lg divide-y divide-stone-200 max-w-xl">
    <?php foreach ($orders as $order): ?>
        <a href="/orders/view.php?id=<?= (int) $order['order_id'] ?>" class="flex items-center justify-between p-4 hover:bg-stone-50">
            <div>
                <p class="font-semibold"><?= htmlspecialchars($order['cafe_name'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-xs text-stone-400"><?= htmlspecialchars($order['status_label'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <span class="font-semibold">&#8369;<?= number_format((float) $order['total_amount'], 2) ?></span>
        </a>
    <?php endforeach; ?>
    <?php if (empty($orders)): ?>
        <p class="p-4 text-stone-500 text-sm">No orders yet.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
