<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';
require_role('admin');

$pdo = (new Database())->connect();

$user_count = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$cafe_count = (int) $pdo->query("SELECT COUNT(*) FROM cafes")->fetchColumn();
$post_count = (int) $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();

$orderModel = new Order($pdo);
$order_count = $orderModel->countAll();
$weekly_revenue = $orderModel->sumRevenueThisWeek();

$reportModel = new Report($pdo);
$pending_reports = count($reportModel->getPending());

require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-black text-stone-900">Dashboard</h1>
<p class="mt-0.5 text-sm text-stone-500">Platform overview at a glance.</p>

<div class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-5">
    <?php
    $stats = [
        ['label' => 'Users',        'value' => number_format($user_count),            'icon' => 'user-circle'],
        ['label' => 'Cafés',        'value' => number_format($cafe_count),            'icon' => 'storefront'],
        ['label' => 'Posts',        'value' => number_format($post_count),            'icon' => 'photo'],
        ['label' => 'Orders',       'value' => number_format($order_count),           'icon' => 'receipt'],
        ['label' => 'Revenue (7d)', 'value' => '₱' . number_format($weekly_revenue, 2), 'icon' => 'star'],
    ];
    ?>
    <?php foreach ($stats as $stat): ?>
        <div class="rounded-2xl border border-stone-200 bg-white p-4">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-cream text-coffee"><?php icon($stat['icon'], 'w-5 h-5'); ?></span>
            <p class="mt-2.5 text-xl font-black text-stone-900"><?= htmlspecialchars($stat['value'], ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-[11px] font-semibold uppercase tracking-wide text-stone-400"><?= $stat['label'] ?></p>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($pending_reports > 0): ?>
    <a href="/admin/reports.php" class="mt-4 flex items-center gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 transition-colors hover:border-red-300">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-red-500"><?php icon('flag', 'w-5 h-5'); ?></span>
        <div class="flex-1">
            <p class="text-sm font-bold text-red-700"><?= $pending_reports ?> pending report<?= $pending_reports === 1 ? '' : 's' ?></p>
            <p class="text-xs text-red-500">Content flagged by users awaiting review.</p>
        </div>
        <span class="text-sm font-bold text-red-500">Review &rarr;</span>
    </a>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
