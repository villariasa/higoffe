<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Follow.php';
require_once __DIR__ . '/../includes/auth_check.php';

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

require_once __DIR__ . '/../includes/header.php';
?>
<div class="bg-white border border-stone-200 rounded-lg overflow-hidden mb-6">
    <?php if (!empty($cafe['cover_url'])): ?>
        <img src="<?= htmlspecialchars($cafe['cover_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-full h-40 object-cover">
    <?php endif; ?>
    <div class="p-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold">
                    <?= htmlspecialchars($cafe['cafe_name'], ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($cafe['is_verified']): ?>
                        <span class="text-amber-600 text-sm align-middle">&check; Verified</span>
                    <?php endif; ?>
                </h1>
                <p class="text-stone-600 mt-1"><?= nl2br(htmlspecialchars($cafe['description'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>
                <p class="text-stone-500 text-sm mt-1"><?= htmlspecialchars($cafe['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php if ($is_customer): ?>
                <div class="flex gap-2">
                    <form method="post">
                        <input type="hidden" name="action" value="toggle_follow">
                        <button type="submit" class="border border-amber-700 text-amber-700 rounded px-4 py-2 text-sm font-semibold">
                            <?= $is_following ? 'Following' : 'Follow' ?>
                        </button>
                    </form>
                    <form method="post" action="/chat/start.php">
                        <input type="hidden" name="cafe_id" value="<?= $cafe_id ?>">
                        <button type="submit" class="bg-amber-700 text-white rounded px-4 py-2 text-sm font-semibold">Chat / Inquire</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<h2 class="text-lg font-bold mb-3">Menu</h2>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    <?php foreach ($products as $product): ?>
        <div class="bg-white border border-stone-200 rounded-lg p-4">
            <?php if (!empty($product['image_url'])): ?>
                <img src="<?= htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-full h-32 object-cover rounded mb-2">
            <?php endif; ?>
            <p class="font-semibold"><?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-sm text-stone-500"><?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-amber-700 font-semibold mt-1">&#8369;<?= number_format((float) $product['base_price'], 2) ?></p>
        </div>
    <?php endforeach; ?>
    <?php if (empty($products)): ?>
        <p class="text-stone-500 text-sm">No menu items yet.</p>
    <?php endif; ?>
</div>

<h2 class="text-lg font-bold mb-3">Posts</h2>
<div class="space-y-4">
    <?php foreach ($posts as $post): ?>
        <div class="bg-white border border-stone-200 rounded-lg p-4">
            <p class="whitespace-pre-wrap"><?= htmlspecialchars($post['caption'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($post['image_url'])): ?>
                <img src="<?= htmlspecialchars($post['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-full max-h-64 object-cover rounded mt-2">
            <?php endif; ?>
            <p class="text-xs text-stone-400 mt-2"><?= (int) $post['like_count'] ?> likes</p>
        </div>
    <?php endforeach; ?>
    <?php if (empty($posts)): ?>
        <p class="text-stone-500 text-sm">No posts yet.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
