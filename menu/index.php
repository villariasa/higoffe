<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';
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

$page_title = 'Menu';
$back_url = '/queue/index.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="px-4 py-4 lg:mx-auto lg:max-w-2xl lg:px-0">
    <a href="/menu/create.php" class="mb-3 block w-full rounded-xl bg-accent py-3 text-center text-sm font-bold text-white active:opacity-80">+ Add product</a>

    <?php if (empty($products)): ?>
        <?php render_empty_state('beaker', 'No products yet', 'Add your first drink or pastry so customers can see your menu.'); ?>
    <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($products as $product): ?>
                <div class="flex items-center gap-3 rounded-2xl bg-white p-3">
                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-stone-200">
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="<?= htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover">
                        <?php else: ?>
                            <div class="flex h-full w-full items-center justify-center text-stone-400"><?php icon('beaker', 'w-6 h-6'); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-stone-900"><?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs text-stone-500">
                            &#8369;<?= number_format((float) $product['base_price'], 2) ?>
                            &middot; <span class="<?= $product['is_available'] ? 'text-green-600' : 'text-red-500' ?> font-semibold"><?= $product['is_available'] ? 'Available' : 'Unavailable' ?></span>
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="/menu/edit.php?id=<?= (int) $product['product_id'] ?>" class="rounded-full border border-stone-200 px-3 py-1.5 text-[11px] font-bold text-stone-600">Edit</a>
                        <form method="post" action="/menu/delete.php" data-confirm="Delete this product?">
                            <input type="hidden" name="product_id" value="<?= (int) $product['product_id'] ?>">
                            <button type="submit" class="text-[11px] font-bold text-red-500">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
