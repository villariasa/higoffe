<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../includes/auth_check.php';
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
    } else {
        $productModel = new Product($pdo);
        $product_id = $productModel->create((int) $cafe['cafe_id'], $product_name, $description, $base_price, $image_url);
        header("Location: /menu/edit.php?id={$product_id}");
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-lg mx-auto bg-white border border-stone-200 rounded-lg p-6">
    <h1 class="text-xl font-bold mb-4">Add a product</h1>

    <?php if ($error !== ''): ?>
        <p class="bg-red-50 text-red-700 text-sm rounded p-3 mb-4"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-3">
        <div>
            <label class="block text-sm text-stone-600 mb-1">Product name</label>
            <input type="text" name="product_name" required class="w-full border border-stone-300 rounded p-2">
        </div>
        <div>
            <label class="block text-sm text-stone-600 mb-1">Description</label>
            <textarea name="description" rows="3" class="w-full border border-stone-300 rounded p-2"></textarea>
        </div>
        <div>
            <label class="block text-sm text-stone-600 mb-1">Base price (&#8369;)</label>
            <input type="number" step="0.01" min="0" name="base_price" required class="w-full border border-stone-300 rounded p-2">
        </div>
        <div>
            <label class="block text-sm text-stone-600 mb-1">Image URL</label>
            <input type="text" name="image_url" class="w-full border border-stone-300 rounded p-2">
        </div>
        <button type="submit" class="w-full bg-amber-700 text-white rounded p-2 font-semibold">Save and continue</button>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
