<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Uploads.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/image_upload_field.php';
require_role('customer');

$order_id = (int) ($_GET['id'] ?? $_POST['order_id'] ?? 0);

$pdo = (new Database())->connect();
$orderModel = new Order($pdo);
$order = $orderModel->getById($order_id);

if (!$order || (int) $order['customer_user_id'] !== (int) $_SESSION['user_id'] || $order['payment_status'] !== 'unpaid') {
    header("Location: /orders/view.php?id={$order_id}");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proof_url = (string) trim($_POST['proof_url'] ?? '');
    if ($proof_url === '') {
        $error = 'Please upload a screenshot of your payment first.';
    } elseif (!Uploads::isAllowedMediaUrl($proof_url)) {
        // Blocks a crafted request from storing a javascript: URL that
        // would execute when rendered inside an <a href>.
        $error = 'Invalid proof URL.';
    } else {
        $success = $orderModel->submitPaymentProof($order_id, $proof_url);
        if ($success) {
            $messageModel = new Message($pdo);
            $messageModel->create((int) $order['conversation_id'], (int) $_SESSION['user_id'], 'image', 'Payment proof', null, $proof_url);
            $messageModel->createSystem((int) $order['conversation_id'], (int) $_SESSION['user_id'], 'Payment proof submitted — waiting for the café to confirm.');
        }
        header("Location: /orders/view.php?id={$order_id}");
        exit;
    }
}

$upload_auth = Uploads::issueToken((int) $_SESSION['user_id'], 'payment_proof');

require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-md mx-auto bg-white border border-stone-200 rounded-xl p-6">
    <h1 class="text-xl font-bold mb-2">Submit payment proof</h1>
    <p class="text-sm text-stone-500 mb-4">Upload a screenshot of your GCash/bank transfer for order #<?= (int) $order_id ?> (&#8369;<?= number_format((float) $order['total_amount'], 2) ?>).</p>

    <?php if ($error !== ''): ?>
        <p class="bg-red-50 text-red-700 text-sm rounded p-3 mb-4"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-3">
        <?php render_image_upload_field($upload_auth, 'proof_url', 'Payment screenshot', ''); ?>
        <button type="submit" class="w-full bg-amber-700 text-white rounded p-2 font-semibold">Submit</button>
    </form>
</div>
<script src="/includes/image_upload.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
