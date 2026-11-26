<?php
// Fixed 5-tab bottom navigation — mobile only (desktop uses the top
// navbar rendered by header.php). Heroicons via includes/icons.php.
require_once __DIR__ . '/icons.php';

$nav_role = $_SESSION['role'] ?? null;
if ($nav_role !== 'customer' && $nav_role !== 'seller') {
    return;
}

$path = $_SERVER['SCRIPT_NAME'] ?? '';

if ($nav_role === 'customer') {
    $tabs = [
        ['label' => 'Home',    'href' => '/feed/index.php',           'match' => '/feed/index.php',          'icon' => 'home',             'tour' => 'home'],
        ['label' => 'Explore', 'href' => '/cafes/discover.php',       'match' => '/cafes/',                  'icon' => 'magnifying-glass', 'tour' => 'explore'],
        ['label' => 'Post',    'href' => '/feed/create_post.php',     'match' => '/feed/create_post.php',    'icon' => 'plus',             'tour' => 'post'],
        ['label' => 'Inbox',   'href' => '/chat/inbox.php',           'match' => '/chat/',                   'icon' => 'inbox',            'tour' => 'inbox'],
        ['label' => 'Profile', 'href' => '/profile/view.php',         'match' => '/profile/',                'icon' => 'user-circle',      'tour' => 'profile'],
    ];
} else {
    $tabs = [
        ['label' => 'Home',    'href' => '/queue/index.php',             'match' => '/queue/',               'icon' => 'home',             'tour' => 'home'],
        ['label' => 'Feed',    'href' => '/feed/index.php',              'match' => '/feed/index.php',       'icon' => 'storefront',       'tour' => 'feed'],
        ['label' => 'Post',    'href' => '/feed/create_post.php',        'match' => '/feed/create_post.php', 'icon' => 'plus',             'tour' => 'post'],
        ['label' => 'Orders',  'href' => '/orders/seller_dashboard.php', 'match' => '/orders/',              'icon' => 'receipt',          'tour' => 'orders'],
        ['label' => 'Profile', 'href' => '/profile/view.php',            'match' => '/profile/',             'icon' => 'user-circle',      'tour' => 'profile'],
    ];
}
?>
<nav class="fixed bottom-0 inset-x-0 z-50 mx-auto w-full max-w-md border-t border-stone-200 bg-white lg:hidden">
    <div class="grid grid-cols-5">
        <?php foreach ($tabs as $tab): ?>
            <?php $is_tab_active = strpos($path, $tab['match']) === 0; ?>
            <?php if ($tab['icon'] === 'plus'): ?>
                <a href="<?= $tab['href'] ?>" data-tour="nav-<?= $tab['tour'] ?>" class="flex flex-col items-center justify-center py-1.5" aria-label="Create post">
                    <span class="-mt-4 flex h-11 w-11 items-center justify-center rounded-full bg-accent text-white shadow-md">
                        <?php icon('plus', 'w-6 h-6'); ?>
                    </span>
                    <span class="mt-0.5 text-[10px] font-semibold <?= $is_tab_active ? 'text-accent' : 'text-stone-500' ?>"><?= $tab['label'] ?></span>
                </a>
            <?php else: ?>
                <a href="<?= $tab['href'] ?>" data-tour="nav-<?= $tab['tour'] ?>" class="flex flex-col items-center justify-center gap-0.5 py-2">
                    <?php icon($tab['icon'], 'w-5 h-5 ' . ($is_tab_active ? 'text-accent' : 'text-stone-400')); ?>
                    <span class="text-[10px] font-semibold <?= $is_tab_active ? 'text-accent' : 'text-stone-500' ?>"><?= $tab['label'] ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</nav>
