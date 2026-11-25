<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Uploads.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/image_upload_field.php';
require_role('seller');

$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);
$cafe = $cafeModel->getByOwner((int) $_SESSION['user_id']);

if (!$cafe) {
    header("Location: /cafes/profile.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = (string) trim($_POST['product_name'] ?? '');
    $description  = (string) trim($_POST['description'] ?? '');
    $base_price   = (float) ($_POST['base_price'] ?? 0);
    $image_url    = (string) trim($_POST['image_url'] ?? '');

    if ($product_name === '' || $base_price <= 0) {
        $error = 'Product name and a price greater than 0 are required.';
    } elseif (!Uploads::isAllowedMediaUrl($image_url)) {
        $error = 'Invalid image URL.';
    } else {
        $productModel = new Product($pdo);
        $product_id = $productModel->create((int) $cafe['cafe_id'], $product_name, $description, $base_price, $image_url);
        header("Location: /menu/edit.php?id={$product_id}");
        exit;
    }
}

$upload_auth = Uploads::issueToken((int) $_SESSION['user_id'], 'product');

$page_title = 'Add product';
$back_url = '/menu/index.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="px-4 py-4">
    <?php if ($error !== ''): ?>
        <p class="mb-3 rounded-xl bg-red-50 p-3 text-sm text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-3">
        <div class="space-y-3 rounded-2xl bg-white p-4">
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Product name</label>
                <input type="text" name="product_name" required class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Description</label>
                <textarea name="description" rows="3" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none"></textarea>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Base price (&#8369;)</label>
                <input type="number" step="0.01" min="0" name="base_price" required class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
            </div>
            <?php render_image_upload_field($upload_auth, 'image_url', 'Product photo', ''); ?>
        </div>
        <button type="submit" class="w-full rounded-xl bg-accent py-3 text-sm font-bold text-white active:opacity-80">Save and continue</button>
    </form>
</div>
<script src="/includes/image_upload.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
