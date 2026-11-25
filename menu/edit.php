<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Uploads.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ProductSize.php';
require_once __DIR__ . '/../models/ProductAddon.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/image_upload_field.php';
require_role('seller');

$product_id = (int) ($_GET['id'] ?? 0);

$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);
$cafe = $cafeModel->getByOwner((int) $_SESSION['user_id']);

$productModel = new Product($pdo);
$product = $productModel->getById($product_id);

// Ownership check — a seller can only edit their own café's products.
if (!$cafe || !$product || (int) $product['cafe_id'] !== (int) $cafe['cafe_id']) {
    header("Location: /menu/index.php");
    exit;
}

$sizeModel = new ProductSize($pdo);
$addonModel = new ProductAddon($pdo);
$error = '';
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_product';

    if ($action === 'update_product') {
        $product_name = (string) trim($_POST['product_name'] ?? '');
        $description  = (string) trim($_POST['description'] ?? '');
        $base_price   = (float) ($_POST['base_price'] ?? 0);
        $image_url    = (string) trim($_POST['image_url'] ?? '');
        $is_available = isset($_POST['is_available']);

        if ($product_name === '' || $base_price <= 0) {
            $error = 'Product name and a price greater than 0 are required.';
        } elseif (!Uploads::isAllowedMediaUrl($image_url)) {
            $error = 'Invalid image URL.';
        } else {
            $productModel->update($product_id, $product_name, $description, $base_price, $image_url, $is_available);
            $saved = true;
        }
    } elseif ($action === 'add_size') {
        $size_label  = (string) trim($_POST['size_label'] ?? '');
        $price_addon = (float) ($_POST['price_addon'] ?? 0);
        if ($size_label !== '') {
            $sizeModel->create($product_id, $size_label, $price_addon);
        }
    } elseif ($action === 'delete_size') {
        $sizeModel->delete((int) ($_POST['size_id'] ?? 0));
    } elseif ($action === 'add_addon') {
        $addon_label = (string) trim($_POST['addon_label'] ?? '');
        $addon_price = (float) ($_POST['addon_price'] ?? 0);
        if ($addon_label !== '') {
            $addonModel->create($product_id, $addon_label, $addon_price);
        }
    } elseif ($action === 'delete_addon') {
        $addonModel->delete((int) ($_POST['addon_id'] ?? 0));
    }

    $product = $productModel->getById($product_id);
}

$sizes = $sizeModel->getByProduct($product_id);
$addons = $addonModel->getByProduct($product_id);
$upload_auth = Uploads::issueToken((int) $_SESSION['user_id'], 'product');

$page_title = 'Edit product';
$back_url = '/menu/index.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-3 px-4 py-4 lg:mx-auto lg:max-w-xl lg:px-0">
    <?php if ($saved): ?>
        <p class="rounded-xl bg-green-50 p-3 text-sm text-green-700">Saved.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="rounded-xl bg-red-50 p-3 text-sm text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <!-- Product details -->
    <form method="post" class="space-y-3 rounded-2xl bg-white p-4">
        <input type="hidden" name="action" value="update_product">
        <div>
            <label class="mb-1 block text-xs font-semibold text-stone-500">Product name</label>
            <input type="text" name="product_name" required value="<?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold text-stone-500">Description</label>
            <textarea name="description" rows="3" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none"><?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold text-stone-500">Base price (&#8369;)</label>
            <input type="number" step="0.01" min="0" name="base_price" required value="<?= htmlspecialchars((string) $product['base_price'], ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
        </div>
        <?php render_image_upload_field($upload_auth, 'image_url', 'Product photo', $product['image_url'] ?? '', 3); ?>
        <label class="flex items-center gap-2 text-sm text-stone-700">
            <input type="checkbox" name="is_available" <?= $product['is_available'] ? 'checked' : '' ?> class="accent-accent">
            Available on menu
        </label>
        <button type="submit" class="w-full rounded-xl bg-accent py-2.5 text-sm font-bold text-white active:opacity-80">Save</button>
    </form>

    <!-- Sizes -->
    <div class="rounded-2xl bg-white p-4">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">Sizes</p>
        <ul class="mb-3 space-y-1.5">
            <?php foreach ($sizes as $size): ?>
                <li class="flex items-center justify-between text-sm text-stone-700">
                    <span><?= htmlspecialchars($size['size_label'], ENT_QUOTES, 'UTF-8') ?> <span class="text-stone-400">(+&#8369;<?= number_format((float) $size['price_addon'], 2) ?>)</span></span>
                    <form method="post">
                        <input type="hidden" name="action" value="delete_size">
                        <input type="hidden" name="size_id" value="<?= (int) $size['size_id'] ?>">
                        <button type="submit" class="text-xs font-semibold text-red-500">Remove</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
        <form method="post" class="flex gap-2">
            <input type="hidden" name="action" value="add_size">
            <input type="text" name="size_label" placeholder="e.g. Large" required class="min-w-0 flex-1 rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
            <input type="number" step="0.01" name="price_addon" placeholder="+&#8369;" class="w-20 rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
            <button type="submit" class="shrink-0 rounded-xl bg-coffee px-4 text-xs font-bold text-white">Add</button>
        </form>
    </div>

    <!-- Add-ons -->
    <div class="rounded-2xl bg-white p-4">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">Add-ons</p>
        <ul class="mb-3 space-y-1.5">
            <?php foreach ($addons as $addon): ?>
                <li class="flex items-center justify-between text-sm text-stone-700">
                    <span><?= htmlspecialchars($addon['addon_label'], ENT_QUOTES, 'UTF-8') ?> <span class="text-stone-400">(+&#8369;<?= number_format((float) $addon['addon_price'], 2) ?>)</span></span>
                    <form method="post">
                        <input type="hidden" name="action" value="delete_addon">
                        <input type="hidden" name="addon_id" value="<?= (int) $addon['addon_id'] ?>">
                        <button type="submit" class="text-xs font-semibold text-red-500">Remove</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
        <form method="post" class="flex gap-2">
            <input type="hidden" name="action" value="add_addon">
            <input type="text" name="addon_label" placeholder="e.g. Extra Shot" required class="min-w-0 flex-1 rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
            <input type="number" step="0.01" name="addon_price" placeholder="+&#8369;" class="w-20 rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
            <button type="submit" class="shrink-0 rounded-xl bg-coffee px-4 text-xs font-bold text-white">Add</button>
        </form>
    </div>
</div>
<script src="/includes/image_upload.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
