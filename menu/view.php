<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ProductReview.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';

$product_id = (int) ($_GET['id'] ?? 0);
$user_id = (int) $_SESSION['user_id'];
$is_customer = $_SESSION['role'] === 'customer';

$pdo = (new Database())->connect();
$productModel = new Product($pdo);
$product = $productModel->getByIdWithMeta($product_id, $user_id);

if (!$product || !$product['is_available']) {
    header("Location: /cafes/discover.php");
    exit;
}

$reviewModel = new ProductReview($pdo);
$can_review = $is_customer && $reviewModel->canReview($user_id, $product_id);
$review_error = '';

if ($is_customer && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_review') {
    $rating = (int) ($_POST['rating'] ?? 0);
    $review_text = (string) trim($_POST['review_text'] ?? '');
    if (!$can_review) {
        // Server-side verified-buyer gate — a crafted POST must fail too.
        $review_error = 'You can only review items from your completed orders.';
    } elseif ($rating < 1 || $rating > 5) {
        $review_error = 'Pick a rating from 1 to 5.';
    } else {
        $reviewModel->upsert($product_id, $user_id, $rating, $review_text);
        $cafe = (new Cafe($pdo))->getById((int) $product['cafe_id']);
        if ($cafe && (int) $cafe['owner_user_id'] !== $user_id) {
            (new Notification($pdo))->create((int) $cafe['owner_user_id'], $_SESSION['full_name'] . ' left a ' . $rating . '-star review on "' . $product['product_name'] . '".', '/menu/view.php?id=' . $product_id);
        }
        header("Location: /menu/view.php?id={$product_id}");
        exit;
    }
}

$reviews = $reviewModel->getForProduct($product_id);
$own_review = $is_customer ? $reviewModel->getOwn($product_id, $user_id) : null;

$page_title = $product['product_name'];
$back_url = '/cafes/view.php?id=' . (int) $product['cafe_id'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="px-4 py-4 lg:mx-auto lg:max-w-2xl lg:px-0">
    <!-- Product card -->
    <div class="overflow-hidden rounded-2xl bg-white lg:border lg:border-stone-100">
        <?php if (!empty($product['image_url'])): ?>
            <img src="<?= htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" decoding="async" class="lightbox-img h-56 w-full cursor-pointer object-cover lg:h-72">
        <?php endif; ?>
        <div class="p-4 lg:p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-lg font-black text-stone-900"><?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <a href="/cafes/view.php?id=<?= (int) $product['cafe_id'] ?>" class="mt-0.5 flex items-center gap-1.5 text-[12px] font-semibold text-stone-500 hover:text-accent">
                        <?php render_avatar($product['cafe_logo_url'] ?? null, $product['cafe_name'], 'w-5 h-5'); ?>
                        <?= htmlspecialchars($product['cafe_name'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <div class="mt-1.5"><?php render_stars(isset($product['rating_avg']) ? (float) $product['rating_avg'] : null, (int) ($product['rating_count'] ?? 0)); ?></div>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-xl font-black text-coffee">&#8369;<?= number_format((float) $product['base_price'], 2) ?></p>
                    <button type="button" id="product-like-btn"
                            class="mt-1 flex items-center gap-1 text-[13px] font-semibold <?= $product['liked_by_viewer'] ? 'text-accent' : 'text-stone-500' ?>"
                            data-product-id="<?= $product_id ?>" aria-label="Like this item">
                        <span class="heart-outline <?= $product['liked_by_viewer'] ? 'hidden' : '' ?>"><?php icon('heart', 'w-[18px] h-[18px]'); ?></span>
                        <span class="heart-solid <?= $product['liked_by_viewer'] ? '' : 'hidden' ?>"><?php icon('heart-solid', 'w-[18px] h-[18px]'); ?></span>
                        <span class="like-count"><?= (int) $product['like_count'] ?></span>
                    </button>
                </div>
            </div>

            <?php if (!empty($product['description'])): ?>
                <p class="mt-2 text-sm leading-snug text-stone-600"><?= nl2br(htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8')) ?></p>
            <?php endif; ?>

            <div class="mt-3 flex gap-2">
                <?php if ($is_customer): ?>
                    <a href="/orders/quick_order.php?cafe_id=<?= (int) $product['cafe_id'] ?>&product_id=<?= $product_id ?>" class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-accent py-2.5 text-sm font-bold text-white hover:opacity-90">
                        <?php icon('shopping-bag', 'w-4 h-4'); ?> Order Now
                    </a>
                    <form method="post" action="/chat/start.php" class="flex-1">
                        <input type="hidden" name="cafe_id" value="<?= (int) $product['cafe_id'] ?>">
                        <button type="submit" class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-accent/40 bg-cream py-2.5 text-sm font-bold text-accent hover:border-accent">
                            <?php icon('chat-bubble', 'w-4 h-4'); ?> Ask about this
                        </button>
                    </form>
                <?php endif; ?>
                <a href="/feed/create_post.php?product_id=<?= $product_id ?>" class="flex <?= $is_customer ? '' : 'flex-1' ?> items-center justify-center gap-1.5 rounded-xl border border-coffee/30 bg-cream py-2.5 text-sm font-bold text-coffee hover:border-coffee px-3">
                    <?php icon('photo', 'w-4 h-4'); ?> Share a post
                </a>
            </div>
        </div>
    </div>

    <!-- Reviews -->
    <section class="mt-5">
        <h2 class="mb-2.5 text-[15px] font-bold text-stone-900">Reviews</h2>
        <div class="space-y-2.5">
            <?php if ($review_error !== ''): ?>
                <script>document.addEventListener('DOMContentLoaded', function () { window.higoffeToast(<?= json_encode($review_error, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, 'error'); });</script>
            <?php endif; ?>

            <?php if ($is_customer): ?>
                <?php if ($can_review): ?>
                    <form method="post" class="rounded-2xl bg-white p-4 lg:border lg:border-stone-100">
                        <input type="hidden" name="action" value="submit_review">
                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400"><?= $own_review ? 'Update your review' : 'Write a review' ?></p>
                        <div class="flex items-center gap-3">
                            <select name="rating" required class="rounded-xl border border-stone-200 bg-cream p-2 text-sm focus:border-coffee focus:outline-none">
                                <option value="">Rating</option>
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <option value="<?= $i ?>" <?= $own_review && (int) $own_review['rating'] === $i ? 'selected' : '' ?>><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
                                <?php endfor; ?>
                            </select>
                            <span class="text-[11px] text-stone-400">Verified — you've ordered this</span>
                        </div>
                        <textarea name="review_text" rows="2" maxlength="1000" placeholder="How was it?" class="mt-2 w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none"><?= htmlspecialchars($own_review['review_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <button type="submit" class="mt-2 rounded-full bg-accent px-4 py-2 text-xs font-bold text-white hover:opacity-90"><?= $own_review ? 'Update review' : 'Post review' ?></button>
                    </form>
                <?php else: ?>
                    <p class="rounded-2xl bg-white p-3.5 text-[13px] text-stone-500 lg:border lg:border-stone-100">Reviews are from verified buyers — order this item to leave one.</p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (empty($reviews)): ?>
                <?php render_empty_state('star', 'No reviews yet'); ?>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="rounded-2xl bg-white p-4 lg:border lg:border-stone-100">
                        <div class="flex items-center gap-2.5">
                            <?php render_avatar($review['reviewer_avatar_url'] ?? null, $review['reviewer_name'], 'w-8 h-8'); ?>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13px] font-bold text-stone-900"><?= htmlspecialchars($review['reviewer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-[10px] text-stone-400"><?= htmlspecialchars(time_ago($review['created_at']), ENT_QUOTES, 'UTF-8') ?> · Verified order</p>
                            </div>
                            <span class="inline-flex shrink-0 items-center gap-0.5">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php icon($i <= (int) $review['rating'] ? 'star-solid' : 'star', 'w-3.5 h-3.5 ' . ($i <= (int) $review['rating'] ? 'text-amber-400' : 'text-stone-300')); ?>
                                <?php endfor; ?>
                            </span>
                        </div>
                        <?php if (!empty($review['review_text'])): ?>
                            <p class="mt-2 whitespace-pre-wrap text-sm text-stone-700"><?= htmlspecialchars($review['review_text'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
document.getElementById('product-like-btn').addEventListener('click', async function () {
    const btn = this;
    const res = await fetch('/menu/like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + encodeURIComponent(btn.dataset.productId)
    });
    const data = await res.json();
    btn.querySelector('.like-count').textContent = data.like_count;
    btn.classList.toggle('text-accent', data.liked);
    btn.classList.toggle('text-stone-500', !data.liked);
    btn.querySelector('.heart-outline').classList.toggle('hidden', data.liked);
    btn.querySelector('.heart-solid').classList.toggle('hidden', !data.liked);
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
