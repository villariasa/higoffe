<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Follow.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';

$cafe_id = (int) ($_GET['id'] ?? 0);

$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);
$cafe = $cafeModel->getById($cafe_id);

if (!$cafe) {
    header("Location: /cafes/discover.php");
    exit;
}

$is_customer = $_SESSION['role'] === 'customer';

if ($is_customer && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_follow') {
    $followModel = new Follow($pdo);
    $followModel->toggle((int) $_SESSION['user_id'], $cafe_id);
    header("Location: /cafes/view.php?id={$cafe_id}");
    exit;
}

$productModel = new Product($pdo);
$products = $productModel->getAvailableByCafe($cafe_id);

$postModel = new Post($pdo);
$posts = $postModel->getFeed((int) $_SESSION['user_id'], $cafe_id);

$is_following = false;
if ($is_customer) {
    $followModel = new Follow($pdo);
    $is_following = $followModel->isFollowing((int) $_SESSION['user_id'], $cafe_id);
}

$lat = isset($_GET['lat']) && is_numeric($_GET['lat']) ? (float) $_GET['lat'] : null;
$lng = isset($_GET['lng']) && is_numeric($_GET['lng']) ? (float) $_GET['lng'] : null;

$distance_km = null;
if ($lat !== null && $lng !== null && !empty($cafe['latitude']) && !empty($cafe['longitude'])) {
    $c_lat = (float) $cafe['latitude'];
    $c_lng = (float) $cafe['longitude'];
    $theta = $lng - $c_lng;
    $dist = sin(deg2rad($lat)) * sin(deg2rad($c_lat)) + cos(deg2rad($lat)) * cos(deg2rad($c_lat)) * cos(deg2rad($theta));
    $dist = acos(min(1.0, max(-1.0, $dist)));
    $distance_km = rad2deg($dist) * 60 * 1.853159616; // km
}

$wants_location = true;
$no_shell = true; // custom hero header with a floating back button
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Cover hero -->
<div class="relative h-44 w-full bg-stone-300 lg:h-64">
    <?php if (!empty($cafe['cover_url'])): ?>
        <img src="<?= htmlspecialchars($cafe['cover_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
    <?php else: ?>
        <div class="flex h-full w-full items-center justify-center bg-coffee/20 text-coffee"><?php icon('storefront', 'w-12 h-12'); ?></div>
    <?php endif; ?>
    <a href="/cafes/discover.php" aria-label="Back" class="absolute left-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 shadow lg:hidden">
        <?php icon('arrow-left', 'w-5 h-5 text-stone-700'); ?>
    </a>
</div>

<!-- Identity card -->
<div class="relative mx-4 -mt-8 rounded-2xl bg-white p-4 shadow-sm lg:mx-6 lg:-mt-12 lg:max-w-3xl lg:p-6">
    <div class="flex items-start gap-3">
        <div class="-mt-10 h-16 w-16 shrink-0 overflow-hidden rounded-2xl border-4 border-white bg-stone-200 shadow lg:h-20 lg:w-20">
            <?php if (!empty($cafe['logo_url'])): ?>
                <img src="<?= htmlspecialchars($cafe['logo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
            <?php else: ?>
                <div class="flex h-full w-full items-center justify-center text-stone-400"><?php icon('storefront', 'w-7 h-7'); ?></div>
            <?php endif; ?>
        </div>
        <div class="min-w-0 flex-1">
            <h1 class="flex items-center gap-1.5 text-base font-black text-stone-900 lg:text-lg">
                <?= htmlspecialchars($cafe['cafe_name'], ENT_QUOTES, 'UTF-8') ?>
                <?php if ($cafe['is_verified']): ?><?php icon('check-badge-solid', 'w-4 h-4 text-accent'); ?><?php endif; ?>
            </h1>
            <div class="mt-0.5 flex flex-wrap items-center gap-2">
                <?php if (!empty($cafe['address'])): ?>
                    <p class="flex items-center gap-1 text-[11px] text-stone-500">
                        <?php icon('map-pin-solid', 'w-3 h-3 text-accent'); ?>
                        <?= htmlspecialchars($cafe['address'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>
                <?php if ($distance_km !== null): ?>
                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-accent">
                        <?= number_format($distance_km, 1) ?> km away
                    </span>
                <?php endif; ?>
                <?php if (!empty($cafe['latitude']) && !empty($cafe['longitude'])): ?>
                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($cafe['latitude'] . ',' . $cafe['longitude']) ?>"
                       target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1 text-[11px] font-bold text-accent hover:underline">
                        <?php icon('arrow-top-right', 'w-3 h-3'); ?> Directions
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($cafe['description'])): ?>
        <p class="mt-2 text-[13px] leading-snug text-stone-600"><?= nl2br(htmlspecialchars($cafe['description'], ENT_QUOTES, 'UTF-8')) ?></p>
    <?php endif; ?>

    <?php if ($is_customer): ?>
        <div class="mt-3 flex gap-2">
            <form method="post" class="flex-1">
                <input type="hidden" name="action" value="toggle_follow">
                <button type="submit" class="w-full rounded-xl py-2 text-sm font-bold <?= $is_following ? 'border border-stone-300 text-stone-600' : 'bg-coffee text-white' ?>">
                    <?= $is_following ? 'Following' : 'Follow' ?>
                </button>
            </form>
            <form method="post" action="/chat/start.php" class="flex-1">
                <input type="hidden" name="cafe_id" value="<?= $cafe_id ?>">
                <button type="submit" class="w-full rounded-xl bg-accent py-2 text-sm font-bold text-white">Chat &amp; Order</button>
            </form>
        </div>
    <?php endif; ?>

    <form method="post" action="/reports/submit.php" class="report-form mt-2 text-right">
        <input type="hidden" name="target_type" value="cafe">
        <input type="hidden" name="target_id" value="<?= $cafe_id ?>">
        <input type="hidden" name="return_to" value="/cafes/view.php?id=<?= $cafe_id ?>">
        <input type="hidden" name="reason" class="reason-input">
        <button type="submit" class="text-[11px] text-stone-400 active:text-red-500">Report</button>
    </form>
</div>

<!-- Menu -->
<section class="mt-5 lg:mt-8">
    <?php render_section_header('Menu'); ?>
    <?php if (empty($products)): ?>
        <?php render_empty_state('beaker', 'No menu items yet'); ?>
    <?php else: ?>
        <div class="space-y-2 px-4 lg:grid lg:grid-cols-2 lg:gap-3 lg:space-y-0 lg:px-6">
            <?php foreach ($products as $product): ?>
                <div class="flex gap-3 rounded-2xl bg-white p-3 lg:border lg:border-stone-100">
                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-stone-200">
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="<?= htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                        <?php else: ?>
                            <div class="flex h-full w-full items-center justify-center text-stone-400"><?php icon('beaker', 'w-7 h-7'); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-stone-900"><?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="line-clamp-1 text-xs text-stone-500"><?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="mt-1.5 flex items-center justify-between">
                            <p class="text-sm font-black text-coffee">&#8369;<?= number_format((float) $product['base_price'], 2) ?></p>
                            <?php if ($is_customer): ?>
                                <form method="post" action="/chat/start.php">
                                    <input type="hidden" name="cafe_id" value="<?= $cafe_id ?>">
                                    <button type="submit" class="rounded-full bg-cream border border-coffee/30 px-3 py-1 text-[11px] font-bold text-coffee">Ask about this</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Posts -->
<section class="mt-5 lg:mt-8 lg:max-w-3xl">
    <?php render_section_header('Posts'); ?>
    <?php if (empty($posts)): ?>
        <?php render_empty_state('photo', 'No posts yet'); ?>
    <?php else: ?>
        <div class="space-y-2.5 lg:px-6">
            <?php foreach ($posts as $post): ?>
                <article class="bg-white px-4 py-3 lg:rounded-2xl lg:border lg:border-stone-100">
                    <?php if (!empty($post['caption'])): ?>
                        <p class="whitespace-pre-wrap text-sm text-stone-800"><?= htmlspecialchars($post['caption'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <?php if (!empty($post['image_url'])): ?>
                        <img src="<?= htmlspecialchars($post['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="mt-2 max-h-72 w-full rounded-xl object-cover">
                    <?php endif; ?>
                    <p class="mt-2 flex items-center gap-3 text-[11px] text-stone-400">
                        <span class="flex items-center gap-1"><?php icon('heart', 'w-3.5 h-3.5'); ?><?= (int) $post['like_count'] ?></span>
                        <span class="flex items-center gap-1"><?php icon('chat-bubble', 'w-3.5 h-3.5'); ?><?= (int) $post['comment_count'] ?></span>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script>
document.querySelectorAll('.report-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        const reason = window.prompt('Why are you reporting this café?');
        if (reason === null) { e.preventDefault(); return; }
        form.querySelector('.reason-input').value = reason;
    });
});
</script>
<script src="/includes/location.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
