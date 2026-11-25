<?php
// Reusable UI components for the mobile+desktop layout. Heroicons only
// (via includes/icons.php) — no emoji. Pure presentation.
require_once __DIR__ . '/icons.php';

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
                <img src="<?= htmlspecialchars($cafe['cover_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
            <?php else: ?>
                <div class="flex h-full w-full items-center justify-center text-stone-400"><?php icon('storefront', 'w-8 h-8'); ?></div>
            <?php endif; ?>
        </div>
        <p class="mt-1.5 flex items-center gap-1 truncate text-[13px] font-semibold text-stone-900">
            <span class="truncate"><?= htmlspecialchars($cafe['cafe_name'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php if (!empty($cafe['is_verified'])): ?><?php icon('check-badge-solid', 'w-3.5 h-3.5 shrink-0 text-accent'); ?><?php endif; ?>
        </p>
        <p class="truncate text-[11px] text-stone-500"><?= htmlspecialchars($cafe['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
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
                <img src="<?= htmlspecialchars($cafe['logo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
            <?php elseif (!empty($cafe['cover_url'])): ?>
                <img src="<?= htmlspecialchars($cafe['cover_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
            <?php else: ?>
                <div class="flex h-full w-full items-center justify-center text-stone-400"><?php icon('storefront', 'w-6 h-6'); ?></div>
            <?php endif; ?>
        </div>
        <div class="min-w-0 flex-1">
            <p class="flex items-center gap-1 truncate text-sm font-semibold text-stone-900">
                <span class="truncate"><?= htmlspecialchars($cafe['cafe_name'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($cafe['is_verified'])): ?><?php icon('check-badge-solid', 'w-4 h-4 shrink-0 text-accent'); ?><?php endif; ?>
            </p>
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
                <img src="<?= htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
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

// Avatar circle with image-or-initial fallback.
function render_avatar(?string $url, string $name, string $size_classes = 'w-9 h-9'): void {
    if (!empty($url)) {
        ?><img src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" alt="" class="<?= $size_classes ?> shrink-0 rounded-full object-cover"><?php
    } else {
        $initial = mb_strtoupper(mb_substr(trim($name) !== '' ? $name : '?', 0, 1));
        ?><div class="<?= $size_classes ?> shrink-0 rounded-full bg-coffee/10 flex items-center justify-center text-sm font-bold text-coffee"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div><?php
    }
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
