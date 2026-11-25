<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$pdo = (new Database())->connect();

$user_count = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$cafe_count = (int) $pdo->query("SELECT COUNT(*) FROM cafes")->fetchColumn();

$orderModel = new Order($pdo);
$order_count = $orderModel->countAll();
$weekly_revenue = $orderModel->sumRevenueThisWeek();

require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-bold mb-4">Admin dashboard</h1>

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-stone-200 rounded-lg p-4">
        <p class="text-xs text-stone-500 uppercase">Users</p>
        <p class="text-2xl font-bold"><?= $user_count ?></p>
    </div>
    <div class="bg-white border border-stone-200 rounded-lg p-4">
        <p class="text-xs text-stone-500 uppercase">Cafés</p>
        <p class="text-2xl font-bold"><?= $cafe_count ?></p>
    </div>
    <div class="bg-white border border-stone-200 rounded-lg p-4">
        <p class="text-xs text-stone-500 uppercase">Orders</p>
        <p class="text-2xl font-bold"><?= $order_count ?></p>
    </div>
    <div class="bg-white border border-stone-200 rounded-lg p-4">
        <p class="text-xs text-stone-500 uppercase">Revenue (7d)</p>
        <p class="text-2xl font-bold">&#8369;<?= number_format($weekly_revenue, 2) ?></p>
    </div>
</div>

<div class="flex gap-4 text-sm">
    <a href="/admin/users.php" class="text-amber-700 underline">Manage users</a>
    <a href="/admin/cafes.php" class="text-amber-700 underline">Verify cafés</a>
    <a href="/admin/posts.php" class="text-amber-700 underline">Moderate posts</a>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
