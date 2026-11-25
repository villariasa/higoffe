<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';
require_role('admin');

$start_date = (string) ($_GET['start'] ?? date('Y-m-d', strtotime('-7 days')));
$end_date_input = (string) ($_GET['end'] ?? date('Y-m-d'));
// End date is inclusive: query up to the start of the next day.
$end_date = date('Y-m-d', strtotime($end_date_input . ' +1 day'));

$pdo = (new Database())->connect();
$orderModel = new Order($pdo);

$summary = $orderModel->getSalesSummary($start_date, $end_date);
$by_status = $orderModel->getOrderCountsByStatus($start_date, $end_date);
$top_products = $orderModel->getTopProducts($start_date, $end_date, 5);

require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-black text-stone-900">Sales report</h1>

<form method="get" class="mt-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-stone-400">From</label>
        <input type="date" name="start" value="<?= htmlspecialchars($start_date, ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-stone-200 bg-white p-2 text-sm focus:border-coffee focus:outline-none">
    </div>
    <div>
        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-stone-400">To</label>
        <input type="date" name="end" value="<?= htmlspecialchars($end_date_input, ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-stone-200 bg-white p-2 text-sm focus:border-coffee focus:outline-none">
    </div>
    <button type="submit" class="rounded-xl bg-accent px-4 py-2 text-sm font-bold text-white hover:opacity-90">Apply</button>
</form>

<div class="mt-5 grid grid-cols-2 gap-3 lg:max-w-xl">
    <div class="rounded-2xl border border-stone-200 bg-white p-4">
        <p class="text-[11px] font-bold uppercase tracking-wide text-stone-400">Completed orders</p>
        <p class="mt-1 text-2xl font-black text-stone-900"><?= (int) $summary['order_count'] ?></p>
    </div>
    <div class="rounded-2xl border border-stone-200 bg-white p-4">
        <p class="text-[11px] font-bold uppercase tracking-wide text-stone-400">Revenue</p>
        <p class="mt-1 text-2xl font-black text-coffee">&#8369;<?= number_format((float) $summary['total_revenue'], 2) ?></p>
    </div>
</div>

<div class="mt-5 grid gap-4 lg:grid-cols-2 lg:max-w-4xl">
    <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white">
        <p class="border-b border-stone-200 bg-cream px-4 py-2.5 text-[11px] font-bold uppercase tracking-wide text-stone-400">Orders by status</p>
        <div class="divide-y divide-stone-100">
            <?php foreach ($by_status as $row): ?>
                <div class="flex justify-between px-4 py-2.5 text-sm">
                    <span class="text-stone-600"><?= htmlspecialchars($row['status_label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="font-black text-stone-900"><?= (int) $row['order_count'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white">
        <p class="border-b border-stone-200 bg-cream px-4 py-2.5 text-[11px] font-bold uppercase tracking-wide text-stone-400">Top products</p>
        <div class="divide-y divide-stone-100">
            <?php foreach ($top_products as $row): ?>
                <div class="flex justify-between px-4 py-2.5 text-sm">
                    <span class="text-stone-600"><?= htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="font-black text-stone-900"><?= (int) $row['total_quantity'] ?> sold</span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($top_products)): ?>
                <p class="px-4 py-3 text-sm text-stone-400">No completed orders in range.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
