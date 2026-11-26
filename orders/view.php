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

<div id="order-detail-root" class="space-y-3 px-4 py-4 lg:mx-auto lg:max-w-xl lg:px-0">
    <?php require __DIR__ . '/_order_detail.php'; ?>
</div>
<script>
// Live status — the seller confirming/advancing an order (or the customer
// submitting payment proof) should show up here without a manual reload.
(function () {
    const root = document.getElementById('order-detail-root');
    const orderId = <?= (int) $order_id ?>;
    async function refresh() {
        if (!document.body.contains(root) || document.hidden) return;
        try {
            const res = await fetch('/orders/detail_fragment.php?id=' + orderId);
            if (res.ok) root.innerHTML = await res.text();
        } catch (err) { /* next tick retries */ }
    }
    const timer = setInterval(function () {
        if (!document.body.contains(root)) { clearInterval(timer); return; }
        refresh();
    }, 4000);
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
