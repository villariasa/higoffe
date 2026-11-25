<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('seller');

$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);
$cafe = $cafeModel->getByOwner((int) $_SESSION['user_id']);

if (!$cafe) {
    header("Location: /cafes/profile.php");
    exit;
}

$orderModel = new Order($pdo);
$statuses = ['Pending', 'Confirmed', 'Preparing', 'Ready for Pickup', 'Out for Delivery'];

require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-bold mb-4">Orders</h1>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($statuses as $status): ?>
        <?php $orders = $orderModel->getForSellerByStatus((int) $cafe['cafe_id'], $status); ?>
        <div>
            <h2 class="font-semibold mb-2"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?> (<?= count($orders) ?>)</h2>
            <div class="space-y-2">
                <?php foreach ($orders as $order): ?>
                    <a href="/orders/view.php?id=<?= (int) $order['order_id'] ?>" class="block bg-white border border-stone-200 rounded-lg p-3 hover:border-amber-400">
                        <p class="font-semibold text-sm"><?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs text-stone-400">&#8369;<?= number_format((float) $order['total_amount'], 2) ?></p>
                    </a>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                    <p class="text-stone-500 text-sm">None.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
