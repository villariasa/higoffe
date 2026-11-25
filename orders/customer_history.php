<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';
require_role('customer');

$pdo = (new Database())->connect();
$orderModel = new Order($pdo);
$orders = $orderModel->getForCustomer((int) $_SESSION['user_id']);

$page_title = 'My orders';
$back_url = '/feed/index.php';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="px-4 py-4 lg:mx-auto lg:max-w-2xl">
    <?php if (empty($orders)): ?>
        <?php render_empty_state('receipt', 'No orders yet', 'Browse a café menu and tap "Order Now" to place your first order!'); ?>
    <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($orders as $order): ?>
                <div class="rounded-2xl bg-white p-3.5">
                    <a href="/orders/view.php?id=<?= (int) $order['order_id'] ?>" class="flex items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-stone-900"><?= htmlspecialchars($order['cafe_name'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-[11px] text-stone-400">Order #<?= (int) $order['order_id'] ?> · <?= htmlspecialchars(date('M j, g:i A', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-black text-coffee">&#8369;<?= number_format((float) $order['total_amount'], 2) ?></p>
                            <?php
                            $pill = [
                                'Pending' => 'bg-amber-100 text-amber-700', 'Confirmed' => 'bg-blue-100 text-blue-700',
                                'Preparing' => 'bg-indigo-100 text-indigo-700', 'Ready for Pickup' => 'bg-green-100 text-green-700',
                                'Out for Delivery' => 'bg-green-100 text-green-700', 'Completed' => 'bg-stone-100 text-stone-500',
                                'Cancelled' => 'bg-red-100 text-red-600',
                            ][$order['status_label']] ?? 'bg-stone-100 text-stone-500';
                            ?>
                            <span class="rounded-full px-2 py-0.5 text-[9px] font-bold uppercase <?= $pill ?>"><?= htmlspecialchars($order['status_label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (($order['payment_status'] ?? 'unpaid') === 'confirmed'): ?>
                                <p class="mt-0.5 text-[10px] font-bold text-green-600">PAID</p>
                            <?php elseif (($order['payment_status'] ?? '') === 'proof_submitted'): ?>
                                <p class="mt-0.5 text-[10px] font-bold text-amber-600">PROOF SENT</p>
                            <?php elseif ($order['status_label'] !== 'Cancelled'): ?>
                                <p class="mt-0.5 text-[10px] font-bold text-red-500">UNPAID</p>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php if (in_array($order['status_label'], ['Completed', 'Cancelled'])): ?>
                        <div class="mt-2 border-t border-stone-100 pt-2">
                            <a href="/orders/reorder.php?order_id=<?= (int) $order['order_id'] ?>" class="text-xs font-bold text-coffee">↺ Reorder</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
