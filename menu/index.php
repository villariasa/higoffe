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

$productModel = new Product($pdo);
$products = $productModel->getByCafe((int) $cafe['cafe_id']);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold">Menu — <?= htmlspecialchars($cafe['cafe_name'], ENT_QUOTES, 'UTF-8') ?></h1>
    <a href="/menu/create.php" class="bg-amber-700 text-white rounded px-4 py-2 text-sm font-semibold">+ Add product</a>
</div>

<div class="bg-white border border-stone-200 rounded-lg divide-y divide-stone-200">
    <?php foreach ($products as $product): ?>
        <div class="flex items-center justify-between p-4">
            <div>
                <p class="font-semibold"><?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-sm text-stone-500">
                    &#8369;<?= number_format((float) $product['base_price'], 2) ?>
                    &middot; <?= $product['is_available'] ? 'Available' : 'Unavailable' ?>
                </p>
            </div>
            <div class="flex gap-3 text-sm">
                <a href="/menu/edit.php?id=<?= (int) $product['product_id'] ?>" class="text-amber-700 underline">Edit</a>
                <form method="post" action="/menu/delete.php" onsubmit="return confirm('Delete this product?');">
                    <input type="hidden" name="product_id" value="<?= (int) $product['product_id'] ?>">
                    <button type="submit" class="text-red-600 underline">Delete</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($products)): ?>
        <p class="p-4 text-stone-500 text-sm">No products yet.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
