<?php
// Reusable UI components for the Grab/Foodpanda-style mobile layout.
// Pure presentation — every function just prints markup from data the
// page already fetched through its Models.

function render_search_bar(string $action, string $placeholder, string $value = ''): void {
    ?>
    <form method="get" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
        </svg>
        <input type="text" name="q" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"
               placeholder="<?= htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') ?>"
               class="w-full rounded-xl border border-stone-200 bg-white py-2.5 pl-9 pr-3 text-sm text-stone-800 placeholder:text-stone-400 focus:border-coffee focus:outline-none">
    </form>
    <?php
}

function render_section_header(string $title, string $see_all_url = ''): void {
    ?>
    <div class="mb-2.5 flex items-center justify-between px-4">
        <h2 class="text-[15px] font-bold text-stone-900"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if ($see_all_url !== ''): ?>
            <a href="<?= htmlspecialchars($see_all_url, ENT_QUOTES, 'UTF-8') ?>" class="text-xs font-semibold text-accent">See all</a>
        <?php endif; ?>
    </div>
    <?php
}

// Compact horizontal café card (Popular cafés / Recommended rows).
function render_cafe_card_h(array $cafe): void {
    ?>
    <a href="/cafes/view.php?id=<?= (int) $cafe['cafe_id'] ?>" class="w-36 shrink-0">
        <div class="h-24 w-36 overflow-hidden rounded-xl bg-stone-200">
            <?php if (!empty($cafe['cover_url'])): ?>
                <img src="<?= htmlspecialchars($cafe['cover_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
            <?php else: ?>
                <div class="flex h-full w-full items-center justify-center text-2xl">&#9749;</div>
            <?php endif; ?>
        </div>
        <p class="mt-1.5 truncate text-[13px] font-semibold text-stone-900">
            <?= htmlspecialchars($cafe['cafe_name'], ENT_QUOTES, 'UTF-8') ?>
            <?php if (!empty($cafe['is_verified'])): ?><span class="text-accent">&check;</span><?php endif; ?>
        </p>
        <p class="truncate text-[11px] text-stone-500"><?= htmlspecialchars($cafe['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    </a>
    <?php
}

// Full-width list café card (Explore results).
function render_cafe_card_list(array $cafe): void {
    ?>
    <a href="/cafes/view.php?id=<?= (int) $cafe['cafe_id'] ?>" class="flex gap-3 bg-white px-4 py-3 active:bg-stone-50">
        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-stone-200">
            <?php if (!empty($cafe['logo_url'])): ?>
                <img src="<?= htmlspecialchars($cafe['logo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
            <?php elseif (!empty($cafe['cover_url'])): ?>
                <img src="<?= htmlspecialchars($cafe['cover_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
            <?php else: ?>
                <div class="flex h-full w-full items-center justify-center text-xl">&#9749;</div>
            <?php endif; ?>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-stone-900">
                <?= htmlspecialchars($cafe['cafe_name'], ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($cafe['is_verified'])): ?><span class="text-accent text-xs">&check; Verified</span><?php endif; ?>
            </p>
            <p class="mt-0.5 line-clamp-1 text-xs text-stone-500"><?= htmlspecialchars($cafe['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p class="mt-0.5 truncate text-[11px] text-stone-400"><?= htmlspecialchars($cafe['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </a>
    <?php
}

// Compact drink/product card for horizontal "Trending drinks" rows.
// Expects product row joined with cafe_name.
function render_drink_card(array $product): void {
    ?>
    <a href="/cafes/view.php?id=<?= (int) $product['cafe_id'] ?>" class="w-32 shrink-0">
        <div class="h-28 w-32 overflow-hidden rounded-xl bg-stone-200">
            <?php if (!empty($product['image_url'])): ?>
                <img src="<?= htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
            <?php else: ?>
                <div class="flex h-full w-full items-center justify-center text-2xl">&#129380;</div>
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

function render_empty_state(string $emoji, string $title, string $hint = ''): void {
    ?>
    <div class="flex flex-col items-center px-6 py-10 text-center">
        <span class="text-3xl"><?= $emoji ?></span>
        <p class="mt-2 text-sm font-semibold text-stone-700"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></p>
        <?php if ($hint !== ''): ?>
            <p class="mt-1 text-xs text-stone-400"><?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>
    <?php
}
