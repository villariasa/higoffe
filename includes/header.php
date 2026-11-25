<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_role = $_SESSION['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Higoffe</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-amber-50 text-stone-800">
<nav class="bg-white border-b border-stone-200 px-4 py-3 flex items-center justify-between">
    <a href="/index.php" class="font-bold text-lg text-amber-800">Higoffe</a>
    <div class="flex items-center gap-4 text-sm">
        <?php if ($current_role === 'customer'): ?>
            <a href="/feed/index.php" class="hover:text-amber-700">Feed</a>
            <a href="/profile/view.php" class="hover:text-amber-700">Profile</a>
            <a href="/cafes/discover.php" class="hover:text-amber-700">Discover</a>
            <a href="/chat/inbox.php" class="hover:text-amber-700">Messages</a>
            <a href="/orders/customer_history.php" class="hover:text-amber-700">Orders</a>
        <?php elseif ($current_role === 'seller'): ?>
            <a href="/feed/index.php" class="hover:text-amber-700">Feed</a>
            <a href="/profile/view.php" class="hover:text-amber-700">Profile</a>
            <a href="/queue/index.php" class="hover:text-amber-700">Inquiries</a>
            <a href="/menu/index.php" class="hover:text-amber-700">Menu</a>
            <a href="/orders/seller_dashboard.php" class="hover:text-amber-700">Orders</a>
        <?php elseif ($current_role === 'admin'): ?>
            <a href="/admin/index.php" class="hover:text-amber-700">Admin</a>
        <?php endif; ?>
        <?php if ($current_role): ?>
            <a href="/auth/logout.php" class="text-stone-500 hover:text-stone-700">Logout</a>
        <?php else: ?>
            <a href="/auth/login.php" class="text-stone-500 hover:text-stone-700">Login</a>
        <?php endif; ?>
    </div>
</nav>
<main class="max-w-5xl mx-auto px-4 py-6">
