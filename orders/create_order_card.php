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
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('customer');

// ── CSRF ──────────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// ── Conversation auth ─────────────────────────────────────────────────────
$conversation_id = (int) ($_GET['conversation_id'] ?? $_POST['conversation_id'] ?? 0);

$pdo = (new Database())->connect();
$conversationModel = new Conversation($pdo);
$conversation = $conversationModel->getById($conversation_id);

$cafeModel = new Cafe($pdo);
$cafe = $conversation ? $cafeModel->getById((int) $conversation['cafe_id']) : null;

if (!$conversation || !$cafe
    || (int) $conversation['customer_user_id'] !== (int) $_SESSION['user_id']) {
    header('Location: /chat/inbox.php');
    exit;
}

$user_id  = (int) $_SESSION['user_id'];
$cafe_id  = (int) $cafe['cafe_id'];

// ── DB draft helpers ──────────────────────────────────────────────────────
function getDraft(PDO $pdo, int $conv_id, int $uid): array {
    $st = $pdo->prepare('SELECT items FROM order_drafts WHERE conversation_id=:c AND customer_user_id=:u');
    $st->execute([':c' => $conv_id, ':u' => $uid]);
    $row = $st->fetch();
    return $row ? (json_decode($row['items'], true) ?: []) : [];
}
function saveDraft(PDO $pdo, int $conv_id, int $uid, array $items): void {
    $st = $pdo->prepare(
        'INSERT INTO order_drafts (conversation_id, customer_user_id, items, updated_at)
         VALUES (:c, :u, :i, NOW())
         ON CONFLICT (conversation_id, customer_user_id)
         DO UPDATE SET items = EXCLUDED.items, updated_at = NOW()'
    );
    $st->execute([':c' => $conv_id, ':u' => $uid, ':i' => json_encode($items)]);
}
function clearDraft(PDO $pdo, int $conv_id, int $uid): void {
    $st = $pdo->prepare('DELETE FROM order_drafts WHERE conversation_id=:c AND customer_user_id=:u');
    $st->execute([':c' => $conv_id, ':u' => $uid]);
}

$productModel = new Product($pdo);
$sizeModel    = new ProductSize($pdo);
$addonModel   = new ProductAddon($pdo);
$products     = $productModel->getAvailableByCafe($cafe_id);

$error = '';

// ── Handle AJAX requests ──────────────────────────────────────────────────
$is_ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check on all POST
    if (!hash_equals($csrf, (string)($_POST['csrf_token'] ?? ''))) {
        if ($is_ajax) { header('Content-Type: application/json'); echo json_encode(['ok' => false, 'error' => 'Invalid request.']); exit; }
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_item') {
            $product_id  = (int) ($_POST['product_id'] ?? 0);
            $size_id     = isset($_POST['size_id']) && $_POST['size_id'] !== '' ? (int) $_POST['size_id'] : null;
            $addon_ids   = array_map('intval', $_POST['addon_ids'] ?? []);
            $quantity    = max(1, (int) ($_POST['quantity'] ?? 1));

            $product = $productModel->getById($product_id);
            if ($product && (int) $product['cafe_id'] === $cafe_id) {
                $unit_price = (float) $product['base_price'];
                $size_label = null;
                if ($size_id) {
                    $size = $sizeModel->getById($size_id);
                    if ($size) { $unit_price += (float) $size['price_addon']; $size_label = $size['size_label']; }
                }
                $addon_lines = [];
                foreach ($addon_ids as $addon_id) {
                    $addon = $addonModel->getById($addon_id);
                    if ($addon) {
                        $unit_price  += (float) $addon['addon_price'];
                        $addon_lines[] = ['addon_id' => $addon_id, 'addon_label' => $addon['addon_label'], 'addon_price' => (float) $addon['addon_price']];
                    }
                }
                $cart = getDraft($pdo, $conversation_id, $user_id);
                $cart[] = [
                    'product_id'   => $product_id,
                    'product_name' => $product['product_name'],
                    'size_id'      => $size_id,
                    'size_label'   => $size_label,
                    'addons'       => $addon_lines,
                    'quantity'     => $quantity,
                    'unit_price'   => $unit_price,
                    'line_total'   => $unit_price * $quantity,
                ];
                saveDraft($pdo, $conversation_id, $user_id, $cart);
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => true, 'cart' => $cart, 'subtotal' => array_sum(array_column($cart, 'line_total'))]);
                    exit;
                }
            }

        } elseif ($action === 'remove_item') {
            $index = (int) ($_POST['index'] ?? -1);
            $cart  = getDraft($pdo, $conversation_id, $user_id);
            if (isset($cart[$index])) {
                unset($cart[$index]);
                $cart = array_values($cart);
                saveDraft($pdo, $conversation_id, $user_id, $cart);
            }
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => true, 'cart' => $cart, 'subtotal' => array_sum(array_column($cart, 'line_total'))]);
                exit;
            }

        } elseif ($action === 'finalize') {
            $fulfillment_type  = ($_POST['fulfillment_type'] ?? '') === 'delivery' ? 'delivery' : 'pickup';
            $delivery_address  = (string) trim($_POST['delivery_address'] ?? '');
            $additional_charges = (float) ($_POST['additional_charges'] ?? 0);
            $cart = getDraft($pdo, $conversation_id, $user_id);

            if (empty($cart)) {
                $error = 'Add at least one item before placing your order.';
            } else {
                $subtotal    = array_sum(array_column($cart, 'line_total'));
                $orderModel  = new Order($pdo);
                $order_id    = $orderModel->create(
                    $conversation_id,
                    (int) $conversation['customer_user_id'],
                    $cafe_id,
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

                // Post order-card message in the chat.
                $messageModel = new Message($pdo);
                $messageModel->create($conversation_id, $user_id, 'order_card', 'Order card', $order_id);
                $conversationModel->touchLastMessageAt($conversation_id);

                // Notify the seller.
                $total_fmt = '₱' . number_format($subtotal + $additional_charges, 2);
                (new Notification($pdo))->create(
                    (int) $cafe['owner_user_id'],
                    "New order from {$_SESSION['full_name']} — {$total_fmt}",
                    '/orders/view.php?id=' . $order_id
                );

                clearDraft($pdo, $conversation_id, $user_id);
                header("Location: /orders/view.php?id={$order_id}");
                exit;
            }
        }
    }
}

// ── Prefill product from quick_order.php redirect ─────────────────────────
$prefill_product_id = (int) ($_GET['prefill_product_id'] ?? 0);

$cart          = getDraft($pdo, $conversation_id, $user_id);
$cart_subtotal = array_sum(array_column($cart, 'line_total'));

$page_title = 'Order from ' . $cafe['cafe_name'];
$back_url   = '/chat/conversation.php?id=' . $conversation_id;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="space-y-3 px-4 py-4 lg:mx-auto lg:max-w-xl lg:px-0">
    <?php if ($error !== ''): ?>
        <script>document.addEventListener('DOMContentLoaded',function(){window.higoffeToast(<?= json_encode($error, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>,'error');});</script>
    <?php endif; ?>

    <!-- Add item (AJAX, no reload) -->
    <div class="rounded-2xl bg-white p-4" id="product-selector-card">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">Choose item</p>

        <!-- Product image preview -->
        <div id="product-preview" class="mb-3 hidden">
            <img id="product-preview-img" src="" alt="" class="h-28 w-full rounded-xl object-cover">
        </div>

        <select id="product-select" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
            <option value="">-- Select product --</option>
            <?php foreach ($products as $p): ?>
                <option value="<?= (int)$p['product_id'] ?>"
                        data-base="<?= (float)$p['base_price'] ?>"
                        <?= $prefill_product_id === (int)$p['product_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8') ?> (&#8369;<?= number_format((float)$p['base_price'], 2) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <div id="options-area" class="mt-3 space-y-3"></div>

        <div id="add-item-row" class="mt-3 hidden flex items-center gap-3">
            <label class="text-xs font-semibold text-stone-500">Qty</label>
            <input type="number" id="qty-input" value="1" min="1" class="w-20 rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
            <button type="button" id="add-item-btn" class="ml-auto rounded-full bg-coffee px-4 py-2 text-xs font-bold text-white active:opacity-80">Add to order</button>
        </div>
    </div>

    <!-- Cart -->
    <div class="rounded-2xl bg-white p-4">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">Order so far</p>
        <ul id="cart-list" class="space-y-2">
            <?php if (empty($cart)): ?>
                <li id="cart-empty" class="text-sm text-stone-400">No items added yet.</li>
            <?php else: ?>
                <?php $__csrf = $csrf; foreach ($cart as $i => $item): ?>
                    <li class="flex items-center justify-between gap-2 border-b border-stone-100 pb-2 text-sm" data-index="<?= $i ?>">
                        <span class="min-w-0 text-stone-700">
                            <?= (int)$item['quantity'] ?>&times; <?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($item['size_label']): ?> <span class="text-stone-400">(<?= htmlspecialchars($item['size_label'], ENT_QUOTES, 'UTF-8') ?>)</span><?php endif; ?>
                            <?php foreach ($item['addons'] as $a): ?><span class="text-stone-400"> + <?= htmlspecialchars($a['addon_label'], ENT_QUOTES, 'UTF-8') ?></span><?php endforeach; ?>
                            — <span class="font-semibold">&#8369;<?= number_format($item['line_total'], 2) ?></span>
                        </span>
                        <button type="button" class="shrink-0 text-xs font-semibold text-red-500" onclick="removeItem(<?= $i ?>)">Remove</button>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        <p class="mt-2.5 flex justify-between text-sm font-black text-stone-900">
            <span>Subtotal</span>
            <span id="subtotal-display">&#8369;<?= number_format($cart_subtotal, 2) ?></span>
        </p>
    </div>

    <!-- Finalize -->
    <div class="rounded-2xl bg-white p-4">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">Finalize</p>
        <form method="post" class="space-y-3" id="finalize-form">
            <input type="hidden" name="action" value="finalize">
            <input type="hidden" name="conversation_id" value="<?= $conversation_id ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Fulfillment</label>
                <select name="fulfillment_type" id="fulfillment-select" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
                    <option value="pickup">Pickup</option>
                    <option value="delivery">Delivery</option>
                </select>
            </div>
            <div id="delivery-address-row" class="hidden">
                <label class="mb-1 block text-xs font-semibold text-stone-500">Delivery address</label>
                <input type="text" name="delivery_address" placeholder="Street, barangay, landmarks…" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
            </div>
            <input type="hidden" name="additional_charges" value="0">
            <button type="submit" class="w-full rounded-xl bg-accent py-3 text-sm font-bold text-white active:opacity-80">Place order</button>
        </form>
    </div>
</div>

<script>
(function () {
    const convId   = <?= $conversation_id ?>;
    const csrf     = <?= json_encode($csrf) ?>;
    const baseUrl  = '/orders';

    // Product selector AJAX
    const productSelect = document.getElementById('product-select');
    const optionsArea   = document.getElementById('options-area');
    const addItemRow    = document.getElementById('add-item-row');
    const addItemBtn    = document.getElementById('add-item-btn');
    const qtyInput      = document.getElementById('qty-input');
    const previewDiv    = document.getElementById('product-preview');
    const previewImg    = document.getElementById('product-preview-img');

    let currentOptions = { sizes: [], addons: [], base_price: 0 };

    function pesosFormat(n) {
        return '₱' + parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    productSelect.addEventListener('change', function () {
        const pid = this.value;
        optionsArea.innerHTML = '';
        addItemRow.classList.add('hidden');
        previewDiv.classList.add('hidden');
        if (!pid) return;

        fetch('/orders/get_product_options.php?product_id=' + pid, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            currentOptions = data;

            // Show image preview
            if (data.image_url) {
                previewImg.src = data.image_url;
                previewDiv.classList.remove('hidden');
            }

            // Sizes
            if (data.sizes.length) {
                const wrap = document.createElement('div');
                wrap.innerHTML = `<label class="mb-1 block text-xs font-semibold text-stone-500">Size</label>
                    <select id="size-select" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
                        <option value="">Default</option>
                        ${data.sizes.map(s => `<option value="${s.size_id}">${s.size_label} (+${pesosFormat(s.price_addon)})</option>`).join('')}
                    </select>`;
                optionsArea.appendChild(wrap);
            }

            // Add-ons
            if (data.addons.length) {
                const wrap = document.createElement('div');
                wrap.innerHTML = `<label class="mb-1 block text-xs font-semibold text-stone-500">Add-ons</label>
                    <div class="space-y-1.5">
                        ${data.addons.map(a => `<label class="flex items-center gap-2 text-sm text-stone-700">
                            <input type="checkbox" class="addon-check accent-accent" value="${a.addon_id}" data-price="${a.addon_price}">
                            ${a.addon_label} (+${pesosFormat(a.addon_price)})
                        </label>`).join('')}
                    </div>`;
                optionsArea.appendChild(wrap);
            }

            addItemRow.classList.remove('hidden');
        })
        .catch(() => addItemRow.classList.remove('hidden'));
    });

    // Trigger prefill if product pre-selected
    <?php if ($prefill_product_id): ?>
    productSelect.dispatchEvent(new Event('change'));
    <?php endif; ?>

    addItemBtn.addEventListener('click', function () {
        const pid = productSelect.value;
        if (!pid) { window.higoffeToast && window.higoffeToast('Select a product first.', 'error'); return; }

        const sizeSelect = document.getElementById('size-select');
        const size_id    = sizeSelect ? sizeSelect.value : '';
        const addonChecks = document.querySelectorAll('.addon-check:checked');
        const addon_ids  = [...addonChecks].map(c => c.value);
        const quantity   = parseInt(qtyInput.value, 10) || 1;

        const fd = new FormData();
        fd.append('action', 'add_item');
        fd.append('conversation_id', convId);
        fd.append('csrf_token', csrf);
        fd.append('product_id', pid);
        fd.append('size_id', size_id);
        addon_ids.forEach(id => fd.append('addon_ids[]', id));
        fd.append('quantity', quantity);

        addItemBtn.disabled = true;
        addItemBtn.textContent = 'Adding…';

        fetch(baseUrl + '/create_order_card.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                renderCart(data.cart, data.subtotal);
                // Reset selector
                productSelect.value = '';
                optionsArea.innerHTML = '';
                addItemRow.classList.add('hidden');
                previewDiv.classList.add('hidden');
                qtyInput.value = 1;
                window.higoffeToast && window.higoffeToast('Item added!', 'success');
            } else {
                window.higoffeToast && window.higoffeToast(data.error || 'Failed to add item.', 'error');
            }
        })
        .catch(() => window.higoffeToast && window.higoffeToast('Network error. Try again.', 'error'))
        .finally(() => { addItemBtn.disabled = false; addItemBtn.textContent = 'Add to order'; });
    });

    function renderCart(cart, subtotal) {
        const list = document.getElementById('cart-list');
        if (!cart.length) {
            list.innerHTML = '<li id="cart-empty" class="text-sm text-stone-400">No items added yet.</li>';
        } else {
            list.innerHTML = cart.map((item, i) => {
                const addonsHtml = item.addons.map(a => ` <span class="text-stone-400">+ ${a.addon_label}</span>`).join('');
                const sizeHtml   = item.size_label ? ` <span class="text-stone-400">(${item.size_label})</span>` : '';
                return `<li class="flex items-center justify-between gap-2 border-b border-stone-100 pb-2 text-sm" data-index="${i}">
                    <span class="min-w-0 text-stone-700">${item.quantity}&times; ${item.product_name}${sizeHtml}${addonsHtml} — <span class="font-semibold">₱${parseFloat(item.line_total).toFixed(2)}</span></span>
                    <button type="button" class="shrink-0 text-xs font-semibold text-red-500" onclick="removeItem(${i})">Remove</button>
                </li>`;
            }).join('');
        }
        document.getElementById('subtotal-display').textContent = '₱' + parseFloat(subtotal).toFixed(2);
    }

    window.removeItem = function (index) {
        const fd = new FormData();
        fd.append('action', 'remove_item');
        fd.append('conversation_id', convId);
        fd.append('csrf_token', csrf);
        fd.append('index', index);

        fetch(baseUrl + '/create_order_card.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
        .then(r => r.json())
        .then(data => { if (data.ok) renderCart(data.cart, data.subtotal); });
    };

    // Delivery address toggle
    document.getElementById('fulfillment-select').addEventListener('change', function () {
        document.getElementById('delivery-address-row').classList.toggle('hidden', this.value !== 'delivery');
    });
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
