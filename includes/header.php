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

// Admin panel navigation — rendered as a persistent sidebar (desktop)
// and a horizontal scroll bar (mobile) on every admin page.
$admin_nav = [
    ['label' => 'Dashboard', 'href' => '/admin/index.php',        'match' => '/admin/index.php',        'icon' => 'home'],
    ['label' => 'Users',     'href' => '/admin/users.php',        'match' => '/admin/users.php',        'icon' => 'user-circle'],
    ['label' => 'Cafés',     'href' => '/admin/cafes.php',        'match' => '/admin/cafes.php',        'icon' => 'storefront'],
    ['label' => 'Posts',     'href' => '/admin/posts.php',        'match' => '/admin/posts.php',        'icon' => 'photo'],
    ['label' => 'Reports',   'href' => '/admin/reports.php',      'match' => '/admin/reports.php',      'icon' => 'flag'],
    ['label' => 'Sales',     'href' => '/admin/sales_report.php', 'match' => '/admin/sales_report.php', 'icon' => 'receipt'],
    ['label' => 'SMTP',      'href' => '/admin/settings.php',     'match' => '/admin/settings.php',     'icon' => 'cog'],
];
$current_path = $_SERVER['SCRIPT_NAME'] ?? '';

// Unread-notification badge. Reuses the page's existing $pdo (every app
// page connects before including the header) — no extra connection; if a
// page didn't connect, the badge just shows 0.
$unread_notifications = 0;
if ($is_app_user && isset($pdo) && $pdo instanceof PDO) {
    require_once __DIR__ . '/../models/Notification.php';
    $unread_notifications = (new Notification($pdo))->countUnread((int) $_SESSION['user_id']);
}
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
    <!-- Turbo Drive: intercepts link clicks, fetches the next page over
         AJAX, swaps the <body> and merges the <head>, and updates the URL
         via the History API — SPA-feel navigation, PHP pages unchanged. -->
    <script type="module">
        import * as Turbo from "https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.12/dist/turbo.es2017-esm.min.js";
        // Forms submit natively (full reload): our PHP re-renders error
        // states with HTTP 200 on POST, which Turbo Drive refuses to
        // render ("form responses must redirect"). Links stay smooth.
        Turbo.setFormMode("off");
    </script>
    <!-- Don't flash stale cached previews of dynamic pages (feed counts,
         chat messages) between navigations. -->
    <meta name="turbo-cache-control" content="no-preview">
    <script>
    // Head scripts run once — Turbo merges the head and skips scripts it
    // has already seen, so these listeners never double-register.
    NProgress.configure({ showSpinner: false, trickleSpeed: 120 });
    document.addEventListener('turbo:visit', function () { NProgress.start(); });
    document.addEventListener('turbo:load', function () { NProgress.done(); });
    document.addEventListener('DOMContentLoaded', function () { NProgress.done(); });
    // Fallback for native navigations (form submits, auth pages).
    window.addEventListener('beforeunload', function () { NProgress.start(); });
    window.addEventListener('pageshow', function () { NProgress.done(); });
    </script>
    <script src="/includes/ui.js" defer></script>
    <script src="/includes/location.js" defer></script>
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
        #nprogress .bar { background: #e0701a; height: 3px; }
        #nprogress .peg { box-shadow: 0 0 10px #e0701a, 0 0 5px #e0701a; }
        #nprogress .spinner { display: none; }
        .turbo-progress-bar { display: none; } /* NProgress is the one loading bar */
    </style>
</head>
<body class="bg-stone-200 text-stone-800"<?= !empty($wants_location) ? ' data-wants-location="1"' : '' ?>>

<div class="relative mx-auto min-h-screen w-full bg-cream <?= $is_admin ? 'max-w-none lg:grid lg:grid-cols-[240px_minmax(0,1fr)]' : ($is_app_user ? 'max-w-md shadow-xl lg:max-w-none lg:shadow-none lg:grid lg:grid-cols-[260px_minmax(0,1fr)]' : 'max-w-md shadow-xl') ?> <?= $is_app_user ? 'pb-20 lg:pb-0' : '' ?>">

<?php if ($is_admin): ?>
    <!-- Admin sidebar (desktop) -->
    <aside class="sticky top-0 hidden h-screen flex-col border-r border-stone-200 bg-coffee-dark px-3 py-5 lg:flex">
        <a href="/admin/index.php" class="px-3 text-xl font-black tracking-tight text-white">Higoffe<span class="text-accent">.</span> <span class="text-[10px] font-bold uppercase tracking-widest text-white/50">Admin</span></a>
        <nav class="mt-6 flex flex-1 flex-col gap-1">
            <?php foreach ($admin_nav as $item): ?>
                <?php $active = strpos($current_path, $item['match']) === 0; ?>
                <a href="<?= $item['href'] ?>"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold transition-colors <?= $active ? 'bg-accent text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' ?>">
                    <?php icon($item['icon'], 'w-5 h-5'); ?>
                    <?= $item['label'] ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <a href="/auth/logout.php" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-white/70 hover:bg-white/10 hover:text-white">
            <?php icon('logout', 'w-5 h-5'); ?> Log out
        </a>
    </aside>

    <div class="min-w-0">
        <!-- Admin mobile top bar + horizontal nav -->
        <header class="sticky top-0 z-40 border-b border-stone-200 bg-coffee-dark lg:hidden">
            <div class="flex items-center justify-between px-4 py-2.5">
                <span class="text-base font-black tracking-tight text-white">Higoffe<span class="text-accent">.</span> <span class="text-[9px] font-bold uppercase tracking-widest text-white/50">Admin</span></span>
                <a href="/auth/logout.php" aria-label="Log out" class="text-white/70"><?php icon('logout', 'w-5 h-5'); ?></a>
            </div>
            <nav class="no-scrollbar flex gap-1 overflow-x-auto px-3 pb-2">
                <?php foreach ($admin_nav as $item): ?>
                    <?php $active = strpos($current_path, $item['match']) === 0; ?>
                    <a href="<?= $item['href'] ?>" class="shrink-0 rounded-full px-3 py-1.5 text-[11px] font-bold <?= $active ? 'bg-accent text-white' : 'text-white/70' ?>"><?= $item['label'] ?></a>
                <?php endforeach; ?>
            </nav>
        </header>
    <main class="px-4 py-6 lg:px-8">
<?php else: ?>

<?php if ($is_app_user): ?>
    <!-- Desktop left sidebar (hidden on mobile) -->
    <aside class="sticky top-0 hidden h-screen flex-col border-r border-stone-200 bg-white px-4 py-5 lg:flex">
        <a href="/index.php" class="px-3 text-2xl font-black tracking-tight text-coffee">Higoffe<span class="text-accent">.</span></a>

        <button type="button" class="location-chip mt-4 flex items-center gap-1.5 rounded-xl border border-stone-200 bg-cream px-3 py-2 text-xs font-semibold text-stone-600 transition-colors hover:border-coffee" title="Click to use current location">
            <?php icon('map-pin-solid', 'w-3.5 h-3.5 text-accent'); ?>
            <span class="location-chip-label">Set location</span>
        </button>

        <nav class="mt-5 flex flex-1 flex-col gap-1">
            <?php foreach ($desktop_nav as $item): ?>
                <?php $active = strpos($current_path, $item['match']) === 0; ?>
                <a href="<?= $item['href'] ?>"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[14px] font-semibold transition-colors <?= $active ? 'bg-coffee text-white' : 'text-stone-600 hover:bg-cream' ?>">
                    <?php icon($item['icon'], 'w-5 h-5'); ?>
                    <?= $item['label'] ?>
                </a>
            <?php endforeach; ?>
            <a href="/notifications.php"
               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[14px] font-semibold transition-colors <?= strpos($current_path, '/notifications.php') === 0 ? 'bg-coffee text-white' : 'text-stone-600 hover:bg-cream' ?>">
                <span class="relative">
                    <?php icon('bell', 'w-5 h-5'); ?>
                    <?php if ($unread_notifications > 0): ?>
                        <span class="absolute -right-1.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-bold text-white"><?= $unread_notifications > 9 ? '9+' : $unread_notifications ?></span>
                    <?php endif; ?>
                </span>
                Notifications
            </a>
        </nav>

        <!-- User mini-card -->
        <div class="mt-4 flex items-center gap-2.5 rounded-xl border border-stone-200 bg-cream p-2.5">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-coffee/10 text-sm font-bold text-coffee">
                <?= htmlspecialchars(mb_strtoupper(mb_substr((string) ($_SESSION['full_name'] ?? '?'), 0, 1)), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-[13px] font-bold text-stone-900"><?= htmlspecialchars((string) ($_SESSION['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-[10px] capitalize text-stone-400"><?= htmlspecialchars((string) $current_role, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <a href="/auth/logout.php" aria-label="Log out" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-stone-400 hover:bg-stone-200 hover:text-stone-600">
                <?php icon('logout', 'w-4 h-4'); ?>
            </a>
        </div>
    </aside>
<?php endif; ?>

<div class="min-w-0 <?= $is_app_user ? 'lg:pb-10' : '' ?>">
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
                        <a href="/notifications.php" aria-label="Notifications" class="relative flex h-9 w-9 items-center justify-center rounded-full text-stone-500 active:bg-stone-200">
                            <?php icon('bell', 'w-5 h-5'); ?>
                            <?php if ($unread_notifications > 0): ?>
                                <span class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-bold text-white"><?= $unread_notifications > 9 ? '9+' : $unread_notifications ?></span>
                            <?php endif; ?>
                        </a>
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
<?php endif; // admin vs app shell ?>
