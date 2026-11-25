<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ProductSize.php';
require_once __DIR__ . '/../models/ProductAddon.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('seller');

$conversation_id = (int) ($_GET['conversation_id'] ?? $_POST['conversation_id'] ?? 0);

$pdo = (new Database())->connect();
$conversationModel = new Conversation($pdo);
$conversation = $conversationModel->getById($conversation_id);

$cafeModel = new Cafe($pdo);
$cafe = $cafeModel->getByOwner((int) $_SESSION['user_id']);

if (!$conversation || !$cafe
    || (int) $conversation['cafe_id'] !== (int) $cafe['cafe_id']
    || (int) $conversation['assigned_seller_user_id'] !== (int) $_SESSION['user_id']) {
    header("Location: /queue/index.php");
    exit;
}

if (!isset($_SESSION['order_draft'][$conversation_id])) {
    $_SESSION['order_draft'][$conversation_id] = [];
}

$productModel = new Product($pdo);
$sizeModel = new ProductSize($pdo);
$addonModel = new ProductAddon($pdo);
$products = $productModel->getAvailableByCafe((int) $cafe['cafe_id']);

$error = '';
$selected_product_id = (int) ($_POST['product_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_item') {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $size_id = isset($_POST['size_id']) && $_POST['size_id'] !== '' ? (int) $_POST['size_id'] : null;
        $addon_ids = array_map('intval', $_POST['addon_ids'] ?? []);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

        $product = $productModel->getById($product_id);
        if ($product && (int) $product['cafe_id'] === (int) $cafe['cafe_id']) {
            $unit_price = (float) $product['base_price'];
            $size_label = null;
            if ($size_id) {
                $size = $sizeModel->getById($size_id);
                if ($size) {
                    $unit_price += (float) $size['price_addon'];
                    $size_label = $size['size_label'];
                }
            }
            $addon_lines = [];
            foreach ($addon_ids as $addon_id) {
                $addon = $addonModel->getById($addon_id);
                if ($addon) {
                    $unit_price += (float) $addon['addon_price'];
                    $addon_lines[] = ["addon_id" => $addon_id, "addon_label" => $addon['addon_label'], "addon_price" => (float) $addon['addon_price']];
                }
            }
            $_SESSION['order_draft'][$conversation_id][] = [
                "product_id"   => $product_id,
                "product_name" => $product['product_name'],
                "size_id"      => $size_id,
                "size_label"   => $size_label,
                "addons"       => $addon_lines,
                "quantity"     => $quantity,
                "unit_price"   => $unit_price,
                "line_total"   => $unit_price * $quantity,
            ];
        }
    } elseif ($action === 'remove_item') {
        $index = (int) ($_POST['index'] ?? -1);
        unset($_SESSION['order_draft'][$conversation_id][$index]);
        $_SESSION['order_draft'][$conversation_id] = array_values($_SESSION['order_draft'][$conversation_id]);
    } elseif ($action === 'finalize') {
        $fulfillment_type = ($_POST['fulfillment_type'] ?? '') === 'delivery' ? 'delivery' : 'pickup';
        $delivery_address = (string) trim($_POST['delivery_address'] ?? '');
        $additional_charges = (float) ($_POST['additional_charges'] ?? 0);
        $cart = $_SESSION['order_draft'][$conversation_id];

        if (empty($cart)) {
            $error = 'Add at least one item before sending.';
        } else {
            $subtotal = array_sum(array_column($cart, 'line_total'));
            $orderModel = new Order($pdo);
            $order_id = $orderModel->create(
                $conversation_id,
                (int) $conversation['customer_user_id'],
                (int) $cafe['cafe_id'],
                $fulfillment_type,
                $delivery_address,
                $subtotal,
                $additional_charges
            );

            $orderItemModel = new OrderItem($pdo);
            foreach ($cart as $item) {
                $order_item_id = $orderItemModel->create($order_id, $item['product_id'], $item['size_id'], $item['quantity'], $item['unit_price'], $item['line_total']);
                foreach ($item['addons'] as $addon) {
                    $orderItemModel->addAddon($order_item_id, $addon['addon_id'], $addon['addon_price']);
                }
            }

            $messageModel = new Message($pdo);
            $messageModel->create($conversation_id, (int) $_SESSION['user_id'], 'order_card', 'Order card', $order_id);
            $conversationModel->touchLastMessageAt($conversation_id);

            unset($_SESSION['order_draft'][$conversation_id]);
            header("Location: /chat/conversation.php?id={$conversation_id}");
            exit;
        }
    }
}

$cart = $_SESSION['order_draft'][$conversation_id];
$cart_subtotal = array_sum(array_column($cart, 'line_total'));

$page_title = 'Create order card';
$back_url = '/chat/conversation.php?id=' . $conversation_id;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-3 px-4 py-4">
    <?php if ($error !== ''): ?>
        <p class="rounded-xl bg-red-50 p-3 text-sm text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <!-- Add item -->
    <div class="rounded-2xl bg-white p-4">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">Add item</p>
        <form method="post" class="space-y-3">
            <input type="hidden" name="action" value="add_item">
            <input type="hidden" name="conversation_id" value="<?= $conversation_id ?>">
            <select name="product_id" required class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none" onchange="this.form.submit()">
                <option value="">-- Select product --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['product_id'] ?>" <?= $selected_product_id === (int) $p['product_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8') ?> (&#8369;<?= number_format((float) $p['base_price'], 2) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if ($selected_product_id):
                $sizes = $sizeModel->getByProduct($selected_product_id);
                $addons = $addonModel->getByProduct($selected_product_id);
            ?>
                <?php if (!empty($sizes)): ?>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-stone-500">Size</label>
                        <select name="size_id" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
                            <option value="">Default</option>
                            <?php foreach ($sizes as $s): ?>
                                <option value="<?= (int) $s['size_id'] ?>"><?= htmlspecialchars($s['size_label'], ENT_QUOTES, 'UTF-8') ?> (+&#8369;<?= number_format((float) $s['price_addon'], 2) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <?php if (!empty($addons)): ?>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-stone-500">Add-ons</label>
                        <div class="space-y-1.5">
                            <?php foreach ($addons as $a): ?>
                                <label class="flex items-center gap-2 text-sm text-stone-700">
                                    <input type="checkbox" name="addon_ids[]" value="<?= (int) $a['addon_id'] ?>" class="accent-accent">
                                    <?= htmlspecialchars($a['addon_label'], ENT_QUOTES, 'UTF-8') ?> (+&#8369;<?= number_format((float) $a['addon_price'], 2) ?>)
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <input type="hidden" name="product_id" value="<?= $selected_product_id ?>">
                <div class="flex items-center gap-3">
                    <label class="text-xs font-semibold text-stone-500">Qty</label>
                    <input type="number" name="quantity" value="1" min="1" class="w-20 rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
                    <button type="submit" class="ml-auto rounded-full bg-coffee px-4 py-2 text-xs font-bold text-white active:opacity-80">Add to order</button>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Cart -->
    <div class="rounded-2xl bg-white p-4">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">Order so far</p>
        <?php if (empty($cart)): ?>
            <p class="text-sm text-stone-400">No items added yet.</p>
        <?php else: ?>
            <ul class="space-y-2">
                <?php foreach ($cart as $i => $item): ?>
                    <li class="flex items-center justify-between gap-2 border-b border-stone-100 pb-2 text-sm">
                        <span class="min-w-0 text-stone-700">
                            <?= (int) $item['quantity'] ?>&times; <?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($item['size_label']): ?> <span class="text-stone-400">(<?= htmlspecialchars($item['size_label'], ENT_QUOTES, 'UTF-8') ?>)</span><?php endif; ?>
                            <?php foreach ($item['addons'] as $a): ?>
                                <span class="text-stone-400">+ <?= htmlspecialchars($a['addon_label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                            — <span class="font-semibold">&#8369;<?= number_format($item['line_total'], 2) ?></span>
                        </span>
                        <form method="post" class="shrink-0">
                            <input type="hidden" name="action" value="remove_item">
                            <input type="hidden" name="conversation_id" value="<?= $conversation_id ?>">
                            <input type="hidden" name="index" value="<?= $i ?>">
                            <button type="submit" class="text-xs font-semibold text-red-500">Remove</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <p class="mt-2.5 flex justify-between text-sm font-black text-stone-900"><span>Subtotal</span><span>&#8369;<?= number_format($cart_subtotal, 2) ?></span></p>
    </div>

    <!-- Finalize -->
    <div class="rounded-2xl bg-white p-4">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">Finalize</p>
        <form method="post" class="space-y-3">
            <input type="hidden" name="action" value="finalize">
            <input type="hidden" name="conversation_id" value="<?= $conversation_id ?>">
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Fulfillment</label>
                <select name="fulfillment_type" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
                    <option value="pickup">Pickup</option>
                    <option value="delivery">Delivery</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Delivery address (if delivery)</label>
                <input type="text" name="delivery_address" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Additional charges (&#8369;)</label>
                <input type="number" step="0.01" name="additional_charges" value="0" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
            </div>
            <button type="submit" class="w-full rounded-xl bg-accent py-3 text-sm font-bold text-white active:opacity-80">Send order card to customer</button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
