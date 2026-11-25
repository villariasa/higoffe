<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/icons.php';

$current_role = $_SESSION['role'] ?? null;

// Pages can set these BEFORE including header.php:
//   $page_title — renders a sub-page top bar (back arrow + title) on
//                 mobile instead of the main logo bar. $back_url controls
//                 the arrow target. Desktop always shows the navbar.
//   $no_shell   — skip top bars entirely (auth/chat pages draw their own).
$page_title = $page_title ?? null;
$back_url = $back_url ?? '/index.php';
$no_shell = $no_shell ?? false;
$is_admin = $current_role === 'admin';
$is_app_user = $current_role === 'customer' || $current_role === 'seller';

// Desktop navbar destinations (same as bottom nav, role-aware)
if ($current_role === 'customer') {
    $desktop_nav = [
        ['label' => 'Home',    'href' => '/feed/index.php',              'match' => '/feed/index.php',       'icon' => 'home'],
        ['label' => 'Explore', 'href' => '/cafes/discover.php',          'match' => '/cafes/',               'icon' => 'magnifying-glass'],
        ['label' => 'Post',    'href' => '/feed/create_post.php',        'match' => '/feed/create_post.php', 'icon' => 'plus'],
        ['label' => 'Inbox',   'href' => '/chat/inbox.php',              'match' => '/chat/',                'icon' => 'inbox'],
        ['label' => 'Orders',  'href' => '/orders/customer_history.php', 'match' => '/orders/',              'icon' => 'receipt'],
        ['label' => 'Profile', 'href' => '/profile/view.php',            'match' => '/profile/',             'icon' => 'user-circle'],
    ];
} elseif ($current_role === 'seller') {
    $desktop_nav = [
        ['label' => 'Home',    'href' => '/queue/index.php',             'match' => '/queue/',               'icon' => 'home'],
        ['label' => 'Feed',    'href' => '/feed/index.php',              'match' => '/feed/index.php',       'icon' => 'storefront'],
        ['label' => 'Post',    'href' => '/feed/create_post.php',        'match' => '/feed/create_post.php', 'icon' => 'plus'],
        ['label' => 'Menu',    'href' => '/menu/index.php',              'match' => '/menu/',                'icon' => 'queue-list'],
        ['label' => 'Orders',  'href' => '/orders/seller_dashboard.php', 'match' => '/orders/',              'icon' => 'receipt'],
        ['label' => 'Profile', 'href' => '/profile/view.php',            'match' => '/profile/',             'icon' => 'user-circle'],
    ];
} else {
    $desktop_nav = [];
}
$current_path = $_SERVER['SCRIPT_NAME'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Higoffe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    cream:  '#faf7f2',
                    coffee: { light: '#8a6a4f', DEFAULT: '#5c4330', dark: '#3e2d1f' },
                    accent: '#e0701a'
                }
            }
        }
    };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
        #nprogress .bar { background: #e0701a; height: 3px; }
        #nprogress .peg { box-shadow: 0 0 10px #e0701a, 0 0 5px #e0701a; }
        #nprogress .spinner { display: none; }
    </style>
</head>
<body class="bg-stone-200 text-stone-800"<?= !empty($wants_location) ? ' data-wants-location="1"' : '' ?>>
<script>
NProgress.configure({ showSpinner: false, trickleSpeed: 120 });
window.addEventListener('beforeunload', function () { NProgress.start(); });
window.addEventListener('pageshow', function () { NProgress.done(); });
document.addEventListener('DOMContentLoaded', function () { NProgress.done(); });
</script>

<div class="relative mx-auto min-h-screen w-full bg-cream shadow-xl <?= $is_admin ? 'max-w-5xl' : ($is_app_user ? 'max-w-md lg:max-w-6xl' : 'max-w-md') ?> <?= $is_app_user ? 'pb-20 lg:pb-10' : '' ?>">

<?php if ($is_app_user): ?>
    <!-- Desktop navbar (hidden on mobile) -->
    <header class="sticky top-0 z-40 hidden border-b border-stone-200 bg-cream/95 backdrop-blur lg:block">
        <div class="flex items-center justify-between px-6 py-3">
            <a href="/index.php" class="text-xl font-black tracking-tight text-coffee">Higoffe<span class="text-accent">.</span></a>
            <nav class="flex items-center gap-1">
                <?php foreach ($desktop_nav as $item): ?>
                    <?php $active = strpos($current_path, $item['match']) === 0; ?>
                    <a href="<?= $item['href'] ?>"
                       class="flex items-center gap-1.5 rounded-full px-3.5 py-2 text-[13px] font-semibold <?= $active ? 'bg-coffee text-white' : 'text-stone-600 hover:bg-stone-100' ?>">
                        <?php icon($item['icon'], 'w-4 h-4'); ?>
                        <?= $item['label'] ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="flex items-center gap-2">
                <button type="button" class="location-chip flex items-center gap-1 rounded-full border border-stone-200 bg-white px-3 py-1.5 text-xs font-semibold text-stone-600 transition-colors hover:border-coffee active:opacity-80" title="Click to use current location">
                    <?php icon('map-pin-solid', 'w-3.5 h-3.5 text-accent'); ?>
                    <span class="location-chip-label">Set location</span>
                </button>
                <a href="/auth/logout.php" aria-label="Log out" class="flex h-9 w-9 items-center justify-center rounded-full text-stone-500 hover:bg-stone-100">
                    <?php icon('logout', 'w-5 h-5'); ?>
                </a>
            </div>
        </div>
    </header>
<?php endif; ?>

<?php if (!$no_shell): ?>
    <?php if ($page_title !== null): ?>
        <!-- Mobile sub-page top bar: back + title -->
        <header class="sticky top-0 z-40 flex items-center gap-2 border-b border-stone-200 bg-cream/95 px-2 py-2.5 backdrop-blur lg:hidden">
            <a href="<?= htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8') ?>" aria-label="Back" class="flex h-9 w-9 items-center justify-center rounded-full active:bg-stone-200">
                <?php icon('arrow-left', 'w-5 h-5 text-stone-700'); ?>
            </a>
            <h1 class="truncate text-[15px] font-bold text-stone-900"><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></h1>
        </header>
        <!-- Desktop gets a slim title strip instead -->
        <div class="hidden items-center gap-2 px-6 pt-5 lg:flex">
            <a href="<?= htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8') ?>" class="flex h-8 w-8 items-center justify-center rounded-full text-stone-500 hover:bg-stone-100">
                <?php icon('arrow-left', 'w-4 h-4'); ?>
            </a>
            <h1 class="text-lg font-black text-stone-900"><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></h1>
        </div>
    <?php else: ?>
        <!-- Mobile main top bar -->
        <header class="sticky top-0 z-40 border-b border-stone-200 bg-cream/95 px-4 py-2.5 backdrop-blur lg:hidden">
            <div class="flex items-center justify-between">
                <span class="text-lg font-black tracking-tight text-coffee">Higoffe<span class="text-accent">.</span></span>
                <div class="flex items-center gap-1">
                    <?php if ($is_app_user): ?>
                        <button type="button" class="location-chip flex items-center gap-1 rounded-full border border-stone-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-stone-600 active:opacity-80" title="Click to use current location">
                            <?php icon('map-pin-solid', 'w-3 h-3 text-accent'); ?>
                            <span class="location-chip-label">Set location</span>
                        </button>
                    <?php endif; ?>
                    <?php if ($current_role): ?>
                        <a href="/auth/logout.php" aria-label="Log out" class="flex h-9 w-9 items-center justify-center rounded-full text-stone-500 active:bg-stone-200">
                            <?php icon('logout', 'w-5 h-5'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>
    <?php endif; ?>
<?php endif; ?>

<main>
