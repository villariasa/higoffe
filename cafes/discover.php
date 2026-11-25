<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';

$keyword = (string) trim($_GET['q'] ?? '');
$filter  = (string) trim($_GET['filter'] ?? '');
$lat     = isset($_GET['lat']) && is_numeric($_GET['lat']) ? (float) $_GET['lat'] : null;
$lng     = isset($_GET['lng']) && is_numeric($_GET['lng']) ? (float) $_GET['lng'] : null;

$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);

if ($filter === 'nearby' && $lat !== null && $lng !== null) {
    $cafes = $cafeModel->getNearby($lat, $lng, 50);
} elseif ($keyword !== '') {
    $cafes = $cafeModel->search($keyword, $lat, $lng);
} else {
    $cafes = $cafeModel->getAll($lat, $lng);
}

$productModel = new Product($pdo);
$trending = ($keyword === '' && $filter !== 'nearby') ? $productModel->getTrending(10) : [];

$chips = ['Coffee', 'Milk Tea', 'Matcha', 'Frappe', 'Pastries', 'Study spot'];
$coords_query = ($lat !== null && $lng !== null) ? '&lat=' . urlencode((string) $lat) . '&lng=' . urlencode((string) $lng) : '';

$wants_location = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="px-4 pt-3 lg:px-6 lg:pt-6 lg:max-w-2xl">
    <?php render_search_bar('/cafes/discover.php', 'Search cafés...', $keyword); ?>
</div>

<!-- Filter chips -->
<div class="no-scrollbar mt-3 flex gap-2 overflow-x-auto px-4 lg:px-6">
    <a href="/cafes/discover.php?filter=nearby<?= $coords_query ?>"
       class="location-chip shrink-0 flex items-center gap-1 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors <?= $filter === 'nearby' ? 'bg-accent text-white' : 'bg-white text-stone-600 border border-stone-200 hover:border-coffee' ?>">
        <?php icon('map-pin-solid', 'w-3 h-3 ' . ($filter === 'nearby' ? 'text-white' : 'text-accent')); ?>
        Nearby
    </a>
    <?php foreach ($chips as $chip): ?>
        <?php $active = strcasecmp($chip, $keyword) === 0; ?>
        <a href="/cafes/discover.php?q=<?= urlencode($chip) ?><?= $coords_query ?>"
           class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold <?= $active ? 'bg-coffee text-white' : 'bg-white text-stone-600 border border-stone-200' ?>">
            <?= htmlspecialchars($chip, ENT_QUOTES, 'UTF-8') ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if (!empty($trending)): ?>
    <section class="mt-5 lg:mt-8">
        <?php render_section_header('Trending drinks'); ?>
        <div class="no-scrollbar flex gap-3 overflow-x-auto px-4 lg:grid lg:grid-cols-5 lg:gap-4 lg:overflow-visible lg:px-6">
            <?php foreach ($trending as $product): ?>
                <?php render_drink_card($product); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="mt-5 lg:mt-8">
    <?php 
    if ($filter === 'nearby') {
        $section_title = 'Cafés near you';
    } elseif ($keyword !== '') {
        $section_title = 'Results for "' . $keyword . '"';
    } else {
        $section_title = 'All cafés';
    }
    render_section_header($section_title);
    ?>

    <?php if (empty($cafes)): ?>
        <?php render_empty_state('magnifying-glass', $filter === 'nearby' ? 'No cafés nearby' : 'No cafés found', $filter === 'nearby' ? 'No cafés with location data found near you yet.' : 'Try a different search or browse the categories above.'); ?>
    <?php else: ?>
        <div class="divide-y divide-stone-100 border-y border-stone-100 lg:grid lg:grid-cols-2 lg:gap-4 lg:divide-y-0 lg:border-0 lg:px-6">
            <?php foreach ($cafes as $cafe): ?>
                <?php render_cafe_card_list($cafe); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<script src="/includes/location.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
