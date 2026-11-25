<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';
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
$active_status = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : 'Pending';
$orders = $orderModel->getForSellerByStatus((int) $cafe['cafe_id'], $active_status);

$page_title = 'Orders';
$back_url = '/queue/index.php';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Status tabs -->
<div class="no-scrollbar flex gap-2 overflow-x-auto px-4 pt-3 lg:px-6">
    <?php foreach ($statuses as $status): ?>
        <?php $active = $status === $active_status; ?>
        <a href="/orders/seller_dashboard.php?status=<?= urlencode($status) ?>"
           class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold <?= $active ? 'bg-coffee text-white' : 'bg-white text-stone-600 border border-stone-200' ?>">
            <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
        </a>
    <?php endforeach; ?>
</div>

<section class="mt-4 px-4 lg:px-6">
    <?php if (empty($orders)): ?>
        <?php render_empty_state('receipt', 'No "' . $active_status . '" orders'); ?>
    <?php else: ?>
        <div class="space-y-2 lg:grid lg:grid-cols-2 lg:gap-3 lg:space-y-0">
            <?php foreach ($orders as $order): ?>
                <a href="/orders/view.php?id=<?= (int) $order['order_id'] ?>" class="flex items-center gap-3 rounded-2xl bg-white p-3.5 active:bg-stone-50">
                    <?php render_avatar(null, $order['customer_name'], 'w-10 h-10'); ?>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-stone-900"><?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-[11px] text-stone-400">Order #<?= (int) $order['order_id'] ?> · <?= htmlspecialchars(date('M j, g:i A', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-sm font-black text-coffee">&#8369;<?= number_format((float) $order['total_amount'], 2) ?></p>
                        <?php if (($order['payment_status'] ?? 'unpaid') === 'confirmed'): ?>
                            <p class="text-[10px] font-bold text-green-600">PAID</p>
                        <?php elseif (($order['payment_status'] ?? '') === 'proof_submitted'): ?>
                            <p class="text-[10px] font-bold text-amber-600">CHECK PROOF</p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
