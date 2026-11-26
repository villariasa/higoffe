<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Uploads.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/image_upload_multi_field.php';
require_once __DIR__ . '/../includes/components.php';

// Anyone can post — a seller can share as their café (cafe_id set); a
// customer post is personal. A post can also check in at a café
// ("is at X"), tag a product ("Share this item" from a product page),
// and be public or café-page-only.
$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);
$own_cafe = null;
if ($_SESSION['role'] === 'seller') {
    $own_cafe = $cafeModel->getByOwner((int) $_SESSION['user_id']);
}
$all_cafes = $cafeModel->getAll();

// Optional product tag, arriving from a product page's "Share" button.
$productModel = new Product($pdo);
$tag_product = null;
$tag_product_id = (int) ($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
if ($tag_product_id > 0) {
    $tag_product = $productModel->getById($tag_product_id);
    if (!$tag_product) {
        $tag_product_id = 0;
    }
}

$error = '';
$post_as_cafe = $own_cafe !== null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = (string) trim($_POST['caption'] ?? '');
    $image_urls_raw = json_decode((string) ($_POST['image_urls'] ?? '[]'), true);
    $image_urls = [];
    if (is_array($image_urls_raw)) {
        foreach (array_slice($image_urls_raw, 0, 10) as $url) {
            $url = (string) $url;
            if ($url !== '' && Uploads::isAllowedMediaUrl($url)) {
                $image_urls[] = $url;
            }
        }
    }
    $post_as_cafe = $own_cafe !== null && isset($_POST['post_as_cafe']);
    $visibility = ($_POST['visibility'] ?? 'public') === 'cafe_only' ? 'cafe_only' : 'public';

    $tagged_cafe_id = (int) ($_POST['tagged_cafe_id'] ?? 0);
    if ($tagged_cafe_id > 0 && !$cafeModel->getById($tagged_cafe_id)) {
        $tagged_cafe_id = 0;
    }
    // A product tag implies the check-in café if none was picked.
    if ($tagged_cafe_id === 0 && $tag_product) {
        $tagged_cafe_id = (int) $tag_product['cafe_id'];
    }

    if ($caption === '' && empty($image_urls)) {
        $error = 'Write something or add a photo.';
    } elseif ($visibility === 'cafe_only' && $tagged_cafe_id === 0 && !$post_as_cafe) {
        $error = 'A café-only post needs a café — tag one, or post as your café.';
    } else {
        $postModel = new Post($pdo);
        $postModel->create(
            (int) $_SESSION['user_id'],
            $post_as_cafe ? (int) $own_cafe['cafe_id'] : null,
            $caption,
            $image_urls,
            $tag_product_id > 0 ? $tag_product_id : null,
            $tagged_cafe_id > 0 ? $tagged_cafe_id : null,
            $visibility
        );
        header("Location: /feed/index.php");
        exit;
    }
}

$upload_auth = Uploads::issueToken((int) $_SESSION['user_id'], 'post');

$page_title = 'New post';
$back_url = '/feed/index.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="px-4 py-4 lg:mx-auto lg:max-w-xl lg:px-0">
    <?php if ($error !== ''): ?>
        <script>document.addEventListener('DOMContentLoaded', function () { window.higoffeToast(<?= json_encode($error, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, 'error'); });</script>
    <?php endif; ?>

    <form method="post" class="space-y-4">
        <textarea name="caption" rows="4" placeholder="Share a drink, a café moment, a recommendation..."
                  class="w-full rounded-2xl border border-stone-200 bg-white p-3.5 text-sm focus:border-coffee focus:outline-none"></textarea>

        <?php if ($tag_product): ?>
            <input type="hidden" name="product_id" value="<?= (int) $tag_product['product_id'] ?>">
            <div class="flex items-center gap-2.5 rounded-2xl border border-accent/30 bg-white p-3">
                <div class="h-11 w-11 shrink-0 overflow-hidden rounded-lg bg-stone-200">
                    <?php if (!empty($tag_product['image_url'])): ?>
                        <img src="<?= htmlspecialchars($tag_product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                    <?php else: ?>
                        <div class="flex h-full w-full items-center justify-center text-stone-400"><?php icon('beaker', 'w-5 h-5'); ?></div>
                    <?php endif; ?>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-accent">Sharing this item</p>
                    <p class="truncate text-[13px] font-bold text-stone-900"><?= htmlspecialchars($tag_product['product_name'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="rounded-2xl bg-white p-3.5">
            <label class="mb-1 block text-xs font-semibold text-stone-500">Photos (optional)</label>
            <?php render_image_upload_multi_field($upload_auth, 'image_urls'); ?>
        </div>

        <div class="space-y-2.5 rounded-2xl bg-white p-3.5">
            <div>
                <label class="mb-1 flex items-center gap-1 text-xs font-semibold text-stone-500">
                    <?php icon('map-pin', 'w-3.5 h-3.5'); ?> Check in at a café (optional)
                </label>
                <select name="tagged_cafe_id" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
                    <option value="">-- No check-in --</option>
                    <?php foreach ($all_cafes as $c): ?>
                        <option value="<?= (int) $c['cafe_id'] ?>" <?= $tag_product && (int) $tag_product['cafe_id'] === (int) $c['cafe_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['cafe_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <p class="mb-1 text-xs font-semibold text-stone-500">Who can see this?</p>
                <div class="flex gap-2">
                    <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-xl border border-stone-200 bg-cream p-2.5 text-[13px] text-stone-700">
                        <input type="radio" name="visibility" value="public" checked class="accent-accent"> Public feed
                    </label>
                    <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-xl border border-stone-200 bg-cream p-2.5 text-[13px] text-stone-700">
                        <input type="radio" name="visibility" value="cafe_only" class="accent-accent"> Café page only
                    </label>
                </div>
            </div>
            <?php if ($own_cafe !== null): ?>
                <label class="flex items-center gap-2.5 text-[13px] text-stone-700">
                    <input type="checkbox" name="post_as_cafe" <?= $post_as_cafe ? 'checked' : '' ?> class="accent-accent">
                    Post as <span class="font-bold"><?= htmlspecialchars($own_cafe['cafe_name'], ENT_QUOTES, 'UTF-8') ?></span> (promo/announcement)
                </label>
            <?php endif; ?>
        </div>

        <button type="submit" class="w-full rounded-xl bg-accent py-3 text-sm font-bold text-white active:opacity-80">Post</button>
    </form>
</div>
<script src="/includes/image_upload_multi.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
