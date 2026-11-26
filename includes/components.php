<?php
// Reusable UI components for the mobile+desktop layout. Heroicons only
// (via includes/icons.php) — no emoji. Pure presentation.
require_once __DIR__ . '/icons.php';

// Relative post age: "Just now", "5 mins ago", "3 hrs ago", "2 days
// ago", then the actual date past a week.
function time_ago(string $timestamp): string {
    $seconds = time() - strtotime($timestamp);
    if ($seconds < 60) return 'Just now';
    if ($seconds < 3600) { $m = intdiv($seconds, 60); return $m . ($m === 1 ? ' min ago' : ' mins ago'); }
    if ($seconds < 86400) { $h = intdiv($seconds, 3600); return $h . ($h === 1 ? ' hr ago' : ' hrs ago'); }
    if ($seconds < 604800) { $d = intdiv($seconds, 86400); return $d . ($d === 1 ? ' day ago' : ' days ago'); }
    return date('M j, Y', strtotime($timestamp));
}

// ★★★★☆-style rating row. $avg may be null (no reviews yet).
function render_stars(?float $avg, int $count = 0, string $size = 'w-3.5 h-3.5'): void {
    if ($avg === null || $count === 0) {
        echo '<span class="text-[11px] text-stone-400">No reviews yet</span>';
        return;
    }
    echo '<span class="inline-flex items-center gap-0.5">';
    $rounded = (int) round($avg);
    for ($i = 1; $i <= 5; $i++) {
        icon($i <= $rounded ? 'star-solid' : 'star', $size . ' ' . ($i <= $rounded ? 'text-amber-400' : 'text-stone-300'));
    }
    echo '<span class="ml-1 text-[11px] font-bold text-stone-700">' . number_format($avg, 1) . '</span>';
    echo '<span class="text-[11px] text-stone-400">(' . $count . ')</span>';
    echo '</span>';
}

function render_search_bar(string $action, string $placeholder, string $value = ''): void {
    $lat = isset($_GET['lat']) && is_numeric($_GET['lat']) ? (float) $_GET['lat'] : null;
    $lng = isset($_GET['lng']) && is_numeric($_GET['lng']) ? (float) $_GET['lng'] : null;
    ?>
    <form method="get" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-stone-400"><?php icon('magnifying-glass', 'w-4 h-4'); ?></span>
        <input type="text" name="q" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"
               placeholder="<?= htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') ?>"
               class="w-full rounded-xl border border-stone-200 bg-white py-2.5 pl-9 pr-3 text-sm text-stone-800 placeholder:text-stone-400 focus:border-coffee focus:outline-none">
        <?php if ($lat !== null && $lng !== null): ?>
            <input type="hidden" name="lat" value="<?= htmlspecialchars((string) $lat, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="lng" value="<?= htmlspecialchars((string) $lng, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
    </form>
    <?php
}

function render_section_header(string $title, string $see_all_url = ''): void {
    ?>
    <div class="mb-2.5 flex items-center justify-between px-4 lg:px-6">
        <h2 class="text-[15px] font-bold text-stone-900 lg:text-base"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if ($see_all_url !== ''): ?>
            <a href="<?= htmlspecialchars($see_all_url, ENT_QUOTES, 'UTF-8') ?>" class="text-xs font-semibold text-accent hover:underline">See all</a>
        <?php endif; ?>
    </div>
    <?php
}

// Compact café card for horizontal rows (mobile) / grids (desktop).
function render_cafe_card_h(array $cafe): void {
    ?>
    <a href="/cafes/view.php?id=<?= (int) $cafe['cafe_id'] ?>" class="w-36 shrink-0 lg:w-auto lg:rounded-2xl lg:bg-white lg:p-2.5 lg:transition-shadow lg:hover:shadow-md">
        <div class="h-24 w-36 overflow-hidden rounded-xl bg-stone-200 lg:h-28 lg:w-full">
            <?php if (!empty($cafe['cover_url'])): ?>
                <img src="<?= htmlspecialchars($cafe['cover_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover">
            <?php else: ?>
                <div class="flex h-full w-full items-center justify-center text-stone-400"><?php icon('storefront', 'w-8 h-8'); ?></div>
            <?php endif; ?>
        </div>
        <p class="mt-1.5 flex items-center gap-1 truncate text-[13px] font-semibold text-stone-900">
            <span class="truncate"><?= htmlspecialchars($cafe['cafe_name'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php if (!empty($cafe['is_verified'])): ?><?php icon('check-badge-solid', 'w-3.5 h-3.5 shrink-0 text-accent'); ?><?php endif; ?>
        </p>
        <p class="truncate text-[11px] text-stone-500"><?= htmlspecialchars($cafe['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        <?php if (!empty($cafe['rating_count'])): ?>
            <p class="flex items-center gap-0.5 text-[11px] font-bold text-stone-700">
                <?php icon('star-solid', 'w-3 h-3 text-amber-400'); ?>
                <?= number_format((float) $cafe['rating_avg'], 1) ?>
                <span class="font-normal text-stone-400">(<?= (int) $cafe['rating_count'] ?>)</span>
            </p>
        <?php endif; ?>
        <?php if (isset($cafe['distance_km'])): ?>
            <p class="text-[11px] font-semibold text-accent"><?= number_format((float) $cafe['distance_km'], 1) ?> km</p>
        <?php endif; ?>
    </a>
    <?php
}

// Full-width list/grid café card (Explore results).
function render_cafe_card_list(array $cafe): void {
    ?>
    <a href="/cafes/view.php?id=<?= (int) $cafe['cafe_id'] ?>" class="flex gap-3 bg-white px-4 py-3 active:bg-stone-50 lg:rounded-2xl lg:border lg:border-stone-100 lg:transition-shadow lg:hover:shadow-md">
        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-stone-200">
            <?php if (!empty($cafe['logo_url'])): ?>
                <img src="<?= htmlspecialchars($cafe['logo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover">
            <?php elseif (!empty($cafe['cover_url'])): ?>
                <img src="<?= htmlspecialchars($cafe['cover_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover">
            <?php else: ?>
                <div class="flex h-full w-full items-center justify-center text-stone-400"><?php icon('storefront', 'w-6 h-6'); ?></div>
            <?php endif; ?>
        </div>
        <div class="min-w-0 flex-1">
            <p class="flex items-center gap-1 truncate text-sm font-semibold text-stone-900">
                <span class="truncate"><?= htmlspecialchars($cafe['cafe_name'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($cafe['is_verified'])): ?><?php icon('check-badge-solid', 'w-4 h-4 shrink-0 text-accent'); ?><?php endif; ?>
            </p>
            <?php if (!empty($cafe['rating_count'])): ?>
                <p class="mt-0.5 flex items-center gap-0.5 text-[11px] font-bold text-stone-700">
                    <?php icon('star-solid', 'w-3 h-3 text-amber-400'); ?>
                    <?= number_format((float) $cafe['rating_avg'], 1) ?>
                    <span class="font-normal text-stone-400">(<?= (int) $cafe['rating_count'] ?> reviews)</span>
                </p>
            <?php endif; ?>
            <p class="mt-0.5 line-clamp-1 text-xs text-stone-500"><?= htmlspecialchars($cafe['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p class="mt-0.5 flex items-center gap-1 truncate text-[11px] text-stone-400">
                <?php if (!empty($cafe['address'])): ?><?php icon('map-pin', 'w-3 h-3 shrink-0'); ?><?= htmlspecialchars($cafe['address'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
            </p>
            <?php if (isset($cafe['distance_km'])): ?>
                <p class="text-[11px] font-semibold text-accent"><?= number_format((float) $cafe['distance_km'], 1) ?> km away</p>
            <?php endif; ?>
        </div>
    </a>
    <?php
}

// Compact drink/product card for horizontal rows (mobile) / grids (desktop).
function render_drink_card(array $product): void {
    ?>
    <a href="/cafes/view.php?id=<?= (int) $product['cafe_id'] ?>" class="w-32 shrink-0 lg:w-auto lg:rounded-2xl lg:bg-white lg:p-2.5 lg:transition-shadow lg:hover:shadow-md">
        <div class="h-28 w-32 overflow-hidden rounded-xl bg-stone-200 lg:h-32 lg:w-full">
            <?php if (!empty($product['image_url'])): ?>
                <img src="<?= htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover">
            <?php else: ?>
                <div class="flex h-full w-full items-center justify-center text-stone-400"><?php icon('beaker', 'w-8 h-8'); ?></div>
            <?php endif; ?>
        </div>
        <p class="mt-1.5 truncate text-[13px] font-semibold text-stone-900"><?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="truncate text-[11px] text-stone-500"><?= htmlspecialchars($product['cafe_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        <p class="text-xs font-bold text-coffee">&#8369;<?= number_format((float) $product['base_price'], 2) ?></p>
    </a>
    <?php
}

// Avatar circle with image-or-initial fallback. $last_active_at (a
// TIMESTAMPTZ string from the users table) adds a Messenger/Instagram-style
// green "active now" dot when set and recent — distinct from a
// conversation's waiting/active/resolved status pill.
function render_avatar(?string $url, string $name, string $size_classes = 'w-9 h-9', ?string $last_active_at = null): void {
    $is_online = $last_active_at !== null && (time() - strtotime($last_active_at)) < 300; // 5 min
    ?><span class="relative inline-block shrink-0 <?= $size_classes ?>"><?php
    if (!empty($url)) {
        ?><img src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" decoding="async" class="h-full w-full rounded-full object-cover"><?php
    } else {
        $initial = mb_strtoupper(mb_substr(trim($name) !== '' ? $name : '?', 0, 1));
        ?><div class="flex h-full w-full items-center justify-center rounded-full bg-coffee/10 text-sm font-bold text-coffee"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div><?php
    }
    if ($is_online) {
        ?><span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white bg-green-500" title="Active now"></span><?php
    }
    ?></span><?php
}

// Tagged-product chip rendered inside a post card. Expects a post row
// from Post::getFeed()/getByIdWithMeta() with tagged_product_* columns.
function render_product_chip(array $post): void {
    if (empty($post['product_id']) || empty($post['tagged_product_name'])) {
        return;
    }
    ?>
    <a href="/menu/view.php?id=<?= (int) $post['product_id'] ?>" class="mt-2 flex items-center gap-2.5 rounded-xl border border-stone-200 bg-cream p-2.5 transition-colors hover:border-accent">
        <div class="h-11 w-11 shrink-0 overflow-hidden rounded-lg bg-stone-200">
            <?php if (!empty($post['tagged_product_image'])): ?>
                <img src="<?= htmlspecialchars($post['tagged_product_image'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover">
            <?php else: ?>
                <div class="flex h-full w-full items-center justify-center text-stone-400"><?php icon('beaker', 'w-5 h-5'); ?></div>
            <?php endif; ?>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[13px] font-bold text-stone-900"><?= htmlspecialchars($post['tagged_product_name'], ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-[11px] font-black text-coffee">&#8369;<?= number_format((float) $post['tagged_product_price'], 2) ?></p>
        </div>
        <span class="shrink-0 text-[11px] font-bold text-accent">View item &rarr;</span>
    </a>
    <?php
}

// Empty state — $icon_name is a Heroicon name from includes/icons.php.
function render_empty_state(string $icon_name, string $title, string $hint = ''): void {
    ?>
    <div class="flex flex-col items-center px-6 py-10 text-center">
        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-stone-100 text-stone-400"><?php icon($icon_name, 'w-7 h-7'); ?></span>
        <p class="mt-3 text-sm font-semibold text-stone-700"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></p>
        <?php if ($hint !== ''): ?>
            <p class="mt-1 max-w-xs text-xs text-stone-400"><?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>
    <?php
}
