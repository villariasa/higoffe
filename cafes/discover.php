<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';

$keyword = (string) trim($_GET['q'] ?? '');

$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);
$cafes = $keyword !== '' ? $cafeModel->search($keyword) : $cafeModel->getAll();

$productModel = new Product($pdo);
$trending = $keyword === '' ? $productModel->getTrending(10) : [];

$chips = ['Coffee', 'Milk Tea', 'Matcha', 'Frappe', 'Pastries', 'Study spot'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="px-4 pt-3">
    <?php render_search_bar('/cafes/discover.php', 'Search cafés...', $keyword); ?>
</div>

<!-- Filter chips -->
<div class="no-scrollbar mt-3 flex gap-2 overflow-x-auto px-4">
    <?php foreach ($chips as $chip): ?>
        <?php $active = strcasecmp($chip, $keyword) === 0; ?>
        <a href="/cafes/discover.php?q=<?= urlencode($chip) ?>"
           class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold <?= $active ? 'bg-coffee text-white' : 'bg-white text-stone-600 border border-stone-200' ?>">
            <?= htmlspecialchars($chip, ENT_QUOTES, 'UTF-8') ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if (!empty($trending)): ?>
    <section class="mt-5">
        <?php render_section_header('Trending drinks'); ?>
        <div class="no-scrollbar flex gap-3 overflow-x-auto px-4">
            <?php foreach ($trending as $product): ?>
                <?php render_drink_card($product); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="mt-5">
    <?php render_section_header($keyword !== '' ? 'Results for "' . $keyword . '"' : 'All cafés'); ?>

    <?php if (empty($cafes)): ?>
        <?php render_empty_state('🔎', 'No cafés found', 'Try a different search or browse the categories above.'); ?>
    <?php else: ?>
        <div class="divide-y divide-stone-100 border-y border-stone-100">
            <?php foreach ($cafes as $cafe): ?>
                <?php render_cafe_card_list($cafe); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
