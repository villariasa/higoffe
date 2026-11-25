<?php
// Fixed 5-tab bottom navigation (Grab/Foodpanda pattern). Role-aware:
//   customer: Home · Explore · Post · Inbox · Profile
//   seller:   Home(queue) · Feed · Post · Orders · Profile
// Active tab is detected from the current script path.
$nav_role = $_SESSION['role'] ?? null;
if ($nav_role !== 'customer' && $nav_role !== 'seller') {
    return;
}

$path = $_SERVER['SCRIPT_NAME'] ?? '';

if ($nav_role === 'customer') {
    $tabs = [
        ['label' => 'Home',    'href' => '/feed/index.php',           'match' => '/feed/index.php',          'icon' => 'home'],
        ['label' => 'Explore', 'href' => '/cafes/discover.php',       'match' => '/cafes/',                  'icon' => 'explore'],
        ['label' => 'Post',    'href' => '/feed/create_post.php',     'match' => '/feed/create_post.php',    'icon' => 'post'],
        ['label' => 'Inbox',   'href' => '/chat/inbox.php',           'match' => '/chat/',                   'icon' => 'inbox'],
        ['label' => 'Profile', 'href' => '/profile/view.php',         'match' => '/profile/',                'icon' => 'profile'],
    ];
} else {
    $tabs = [
        ['label' => 'Home',    'href' => '/queue/index.php',          'match' => '/queue/',                  'icon' => 'home'],
        ['label' => 'Feed',    'href' => '/feed/index.php',           'match' => '/feed/index.php',          'icon' => 'explore'],
        ['label' => 'Post',    'href' => '/feed/create_post.php',     'match' => '/feed/create_post.php',    'icon' => 'post'],
        ['label' => 'Orders',  'href' => '/orders/seller_dashboard.php', 'match' => '/orders/',              'icon' => 'inbox'],
        ['label' => 'Profile', 'href' => '/profile/view.php',         'match' => '/profile/',                'icon' => 'profile'],
    ];
}

function nav_icon_svg(string $icon, bool $active): string {
    $c = $active ? 'text-accent' : 'text-stone-400';
    switch ($icon) {
        case 'home':
            return "<svg class=\"h-5 w-5 {$c}\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"2\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"m2.25 12 8.95-8.95a1.13 1.13 0 0 1 1.6 0L21.75 12M4.5 9.75V19.9c0 .62.5 1.13 1.13 1.13H9.75V16a1.13 1.13 0 0 1 1.13-1.13h2.25A1.13 1.13 0 0 1 14.25 16v5.03h4.13c.62 0 1.12-.5 1.12-1.13V9.75\"/></svg>";
        case 'explore':
            return "<svg class=\"h-5 w-5 {$c}\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"2\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"m21 21-4.3-4.3M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z\"/></svg>";
        case 'post':
            // handled specially (raised accent button)
            return "<svg class=\"h-6 w-6 text-white\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"2\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M12 4.5v15m7.5-7.5h-15\"/></svg>";
        case 'inbox':
            return "<svg class=\"h-5 w-5 {$c}\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"2\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M2.25 13.5h3.86c.57 0 1.09.32 1.34.83l.37.73c.26.51.78.83 1.35.83h5.66c.57 0 1.1-.32 1.35-.83l.37-.73c.25-.51.77-.83 1.34-.83h3.86M2.25 13.5V6.75A2.25 2.25 0 0 1 4.5 4.5h15a2.25 2.25 0 0 1 2.25 2.25v6.75m-19.5 0v4.5a2.25 2.25 0 0 0 2.25 2.25h15a2.25 2.25 0 0 0 2.25-2.25v-4.5\"/></svg>";
        case 'profile':
            return "<svg class=\"h-5 w-5 {$c}\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"2\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M17.98 18.72a9.09 9.09 0 0 0-11.96 0M12 12.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z\"/><circle cx=\"12\" cy=\"12\" r=\"9.75\" stroke-width=\"1.5\"/></svg>";
    }
    return '';
}
?>
<nav class="fixed bottom-0 inset-x-0 z-50 mx-auto w-full max-w-md border-t border-stone-200 bg-white">
    <div class="grid grid-cols-5">
        <?php foreach ($tabs as $tab): ?>
            <?php
            $active = $tab['match'] !== '' && strpos($path, $tab['match']) === 0;
            ?>
            <?php if ($tab['icon'] === 'post'): ?>
                <a href="<?= $tab['href'] ?>" class="flex flex-col items-center justify-center py-1.5" aria-label="Create post">
                    <span class="-mt-4 flex h-11 w-11 items-center justify-center rounded-full bg-accent shadow-md">
                        <?= nav_icon_svg('post', $active) ?>
                    </span>
                    <span class="mt-0.5 text-[10px] font-semibold <?= $active ? 'text-accent' : 'text-stone-500' ?>"><?= $tab['label'] ?></span>
                </a>
            <?php else: ?>
                <a href="<?= $tab['href'] ?>" class="flex flex-col items-center justify-center gap-0.5 py-2">
                    <?= nav_icon_svg($tab['icon'], $active) ?>
                    <span class="text-[10px] font-semibold <?= $active ? 'text-accent' : 'text-stone-500' ?>"><?= $tab['label'] ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</nav>
