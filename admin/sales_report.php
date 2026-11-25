<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$start_date = (string) ($_GET['start'] ?? date('Y-m-d', strtotime('-7 days')));
$end_date_input = (string) ($_GET['end'] ?? date('Y-m-d'));
// Treat the end date as inclusive by querying up to the start of the next day.
$end_date = date('Y-m-d', strtotime($end_date_input . ' +1 day'));

$pdo = (new Database())->connect();
$orderModel = new Order($pdo);

$summary = $orderModel->getSalesSummary($start_date, $end_date);
$by_status = $orderModel->getOrderCountsByStatus($start_date, $end_date);
$top_products = $orderModel->getTopProducts($start_date, $end_date, 5);

require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-bold mb-4">Sales report</h1>

<form method="get" class="flex items-end gap-3 mb-6">
    <div>
        <label class="block text-xs text-stone-500 mb-1">From</label>
        <input type="date" name="start" value="<?= htmlspecialchars($start_date, ENT_QUOTES, 'UTF-8') ?>" class="border border-stone-300 rounded p-2 text-sm">
    </div>
    <div>
        <label class="block text-xs text-stone-500 mb-1">To</label>
        <input type="date" name="end" value="<?= htmlspecialchars($end_date_input, ENT_QUOTES, 'UTF-8') ?>" class="border border-stone-300 rounded p-2 text-sm">
    </div>
    <button type="submit" class="bg-amber-700 text-white rounded px-4 py-2 text-sm font-semibold">Apply</button>
</form>

<div class="grid sm:grid-cols-2 gap-4 mb-6">
    <div class="bg-white border border-stone-200 rounded-lg p-4">
        <p class="text-xs text-stone-500 uppercase">Completed orders</p>
        <p class="text-2xl font-bold"><?= (int) $summary['order_count'] ?></p>
    </div>
    <div class="bg-white border border-stone-200 rounded-lg p-4">
        <p class="text-xs text-stone-500 uppercase">Revenue</p>
        <p class="text-2xl font-bold">&#8369;<?= number_format((float) $summary['total_revenue'], 2) ?></p>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div>
        <h2 class="font-semibold mb-2">Orders by status</h2>
        <div class="bg-white border border-stone-200 rounded-lg divide-y divide-stone-200">
            <?php foreach ($by_status as $row): ?>
                <div class="flex justify-between p-3 text-sm">
                    <span><?= htmlspecialchars($row['status_label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="font-semibold"><?= (int) $row['order_count'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div>
        <h2 class="font-semibold mb-2">Top products</h2>
        <div class="bg-white border border-stone-200 rounded-lg divide-y divide-stone-200">
            <?php foreach ($top_products as $row): ?>
                <div class="flex justify-between p-3 text-sm">
                    <span><?= htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="font-semibold"><?= (int) $row['total_quantity'] ?> sold</span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($top_products)): ?>
                <p class="p-3 text-stone-500 text-sm">No completed orders in range.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
