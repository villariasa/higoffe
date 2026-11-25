<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Uploads.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';
require_once __DIR__ . '/../includes/image_upload_field.php';

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

$next_statuses = $is_owner_seller ? $orderModel->getAllowedNextStatuses($order['status_label']) : [];

$orderItemModel = new OrderItem($pdo);
$items = $orderItemModel->getForOrder($order_id);

// Grab-style progress timeline
$timeline = $order['fulfillment_type'] === 'delivery'
    ? ['Pending', 'Confirmed', 'Preparing', 'Out for Delivery', 'Completed']
    : ['Pending', 'Confirmed', 'Preparing', 'Ready for Pickup', 'Completed'];
$short_labels = $order['fulfillment_type'] === 'delivery'
    ? ['Pending', 'Confirmed', 'Preparing', 'Delivery', 'Done']
    : ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Done'];
$current_step = array_search($order['status_label'], $timeline, true);
$is_cancelled = $order['status_label'] === 'Cancelled';

$upload_auth = ($role === 'customer' && $order['payment_status'] === 'unpaid')
    ? Uploads::issueToken($user_id, 'payment_proof')
    : null;

$page_title = 'Order #' . $order_id;
$back_url = $role === 'customer' ? '/orders/customer_history.php' : '/orders/seller_dashboard.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-3 px-4 py-4 lg:mx-auto lg:max-w-xl lg:px-0">

    <!-- Quick header + Chat link -->
    <div class="flex items-center justify-between rounded-2xl bg-white p-3.5">
        <div class="min-w-0">
            <p class="text-xs font-bold text-stone-900">
                <?= $is_owner_seller ? htmlspecialchars($order['customer_name'] ?? 'Customer', ENT_QUOTES, 'UTF-8') : htmlspecialchars($order['cafe_name'] ?? 'Café', ENT_QUOTES, 'UTF-8') ?>
            </p>
            <p class="text-[11px] text-stone-400">
                Payment:
                <?php if ($order['payment_status'] === 'confirmed'): ?>
                    <span class="font-bold text-green-600">Confirmed</span>
                <?php elseif ($order['payment_status'] === 'proof_submitted'): ?>
                    <span class="font-bold text-amber-600">Proof submitted</span>
                <?php else: ?>
                    <span class="font-bold text-red-500">Unpaid</span>
                <?php endif; ?>
            </p>
        </div>
        <a href="/chat/conversation.php?id=<?= (int) $order['conversation_id'] ?>" class="inline-flex items-center gap-1.5 rounded-full border border-stone-200 px-3.5 py-1.5 text-xs font-bold text-stone-700 hover:border-accent hover:text-accent">
            <?php icon('chat-bubble', 'w-3.5 h-3.5'); ?> Open chat
        </a>
    </div>

    <!-- Status timeline -->
    <div class="rounded-2xl bg-white p-4">
        <?php if ($is_cancelled): ?>
            <p class="text-center text-sm font-bold text-red-500">This order was cancelled.</p>
        <?php else: ?>
            <div class="flex items-start">
                <?php foreach ($timeline as $i => $step): ?>
                    <?php $done = $current_step !== false && $i <= $current_step; ?>
                    <div class="flex flex-col items-center <?= $i > 0 ? 'flex-1' : '' ?>">
                        <div class="flex w-full items-center">
                            <?php if ($i > 0): ?>
                                <div class="h-0.5 flex-1 <?= $done ? 'bg-accent' : 'bg-stone-200' ?>"></div>
                            <?php endif; ?>
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold <?= $done ? 'bg-accent text-white' : 'bg-stone-200 text-stone-400' ?>">
                                <?php if ($done): ?><?php icon('check', 'w-3 h-3'); ?><?php else: ?><?= $i + 1 ?><?php endif; ?>
                            </span>
                        </div>
                        <span class="mt-1 text-[9px] font-semibold <?= $done ? 'text-stone-800' : 'text-stone-400' ?>"><?= htmlspecialchars($short_labels[$i], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="mt-2.5 text-center text-sm font-bold text-stone-900"><?= htmlspecialchars($order['status_label'], ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-center text-[11px] capitalize text-stone-400"><?= htmlspecialchars($order['fulfillment_type'], ENT_QUOTES, 'UTF-8') ?><?php if (!empty($order['delivery_address'])): ?> · <?= htmlspecialchars($order['delivery_address'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?></p>
        <?php endif; ?>
    </div>

    <!-- Items -->
    <div class="rounded-2xl bg-white p-4">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">Order summary</p>
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
        </div>
    </div>

    <!-- Payment -->
    <div class="rounded-2xl bg-white p-4">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">Payment</p>
        <?php if ($order['payment_status'] === 'unpaid'): ?>
            <?php if ($role === 'customer' && $upload_auth): ?>
                <form method="post" action="/orders/submit_payment.php" class="space-y-3">
                    <input type="hidden" name="order_id" value="<?= (int) $order_id ?>">
                    <?php render_image_upload_field($upload_auth, 'proof_url', 'Upload payment screenshot (GCash / bank transfer)', ''); ?>
                    <button type="submit" class="w-full rounded-xl bg-accent py-2.5 text-sm font-bold text-white active:opacity-80 disabled:opacity-50">Submit payment proof</button>
                </form>
                <script src="/includes/image_upload.js"></script>
            <?php else: ?>
                <p class="text-sm text-stone-500">Waiting for the customer to submit payment proof.</p>
            <?php endif; ?>
        <?php elseif ($order['payment_status'] === 'proof_submitted'): ?>
            <?php if (!empty($order['payment_proof_url'])): ?>
                <a href="<?= htmlspecialchars($order['payment_proof_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                    <img src="<?= htmlspecialchars($order['payment_proof_url'], ENT_QUOTES, 'UTF-8') ?>" alt="Payment proof" loading="lazy" decoding="async" class="mb-2.5 h-32 w-32 rounded-xl border border-stone-200 object-cover">
                </a>
            <?php endif; ?>
            <?php if ($is_owner_seller): ?>
                <form method="post" action="/orders/confirm_payment.php">
                    <input type="hidden" name="order_id" value="<?= (int) $order_id ?>">
                    <button type="submit" class="w-full rounded-xl bg-accent py-2.5 text-sm font-bold text-white active:opacity-80">Confirm payment</button>
                </form>
            <?php else: ?>
                <p class="text-sm text-stone-500">Proof submitted — waiting for the café to confirm.</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="flex items-center gap-1.5 text-sm font-bold text-green-600"><?php icon('check-circle', 'w-4 h-4'); ?> Payment confirmed</p>
        <?php endif; ?>
    </div>

    <!-- Seller status actions -->
    <?php if ($is_owner_seller && !empty($next_statuses)): ?>
        <div class="rounded-2xl bg-white p-4">
            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">Update status</p>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($next_statuses as $status): ?>
                    <form method="post" action="/orders/update_status.php">
                        <input type="hidden" name="order_id" value="<?= (int) $order_id ?>">
                        <input type="hidden" name="to_label" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="rounded-full <?= $status === 'Cancelled' ? 'border border-red-300 text-red-500' : 'bg-coffee text-white' ?> px-4 py-2 text-xs font-bold active:opacity-80">
                            <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    <?php elseif ($role === 'customer' && $order['status_label'] === 'Pending'): ?>
        <form method="post" action="/orders/confirm.php" data-confirm="Cancel this order?">
            <input type="hidden" name="order_id" value="<?= (int) $order_id ?>">
            <input type="hidden" name="action" value="cancel">
            <button type="submit" class="w-full rounded-xl border border-red-300 bg-white py-2.5 text-sm font-bold text-red-500 active:bg-red-50">Cancel order</button>
        </form>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
