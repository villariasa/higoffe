<?php
// Shared order-detail markup — expects $order, $items, $role, $order_id,
// $is_owner_seller, $next_statuses, $upload_auth, $timeline, $short_labels,
// $current_step, $is_cancelled already computed by the including page.
// Used by both the full page (orders/view.php, for deep-links from chat/
// notifications) and the AJAX fragment (orders/detail_fragment.php, for
// the inline expand-in-place panel on the order list pages) — one copy of
// the actual order logic instead of three.
require_once __DIR__ . '/../includes/icons.php';
require_once __DIR__ . '/../config/PayMongo.php';
?>
<!-- Quick header + Chat link -->
<div class="flex items-center justify-between rounded-2xl bg-white p-3.5">
    <div class="min-w-0">
        <p class="text-xs font-bold text-stone-900">
            <?= $is_owner_seller ? htmlspecialchars($order['customer_name'] ?? 'Customer', ENT_QUOTES, 'UTF-8') : htmlspecialchars($order['cafe_name'] ?? 'Café', ENT_QUOTES, 'UTF-8') ?>
        </p>
        <p class="text-[11px] text-stone-400">
            Payment:
            <?php if ($order['payment_status'] === 'confirmed'): ?>
                <span class="font-bold text-green-600">Confirmed</span>
            <?php elseif ($order['payment_status'] === 'proof_submitted'): ?>
                <span class="font-bold text-amber-600">Proof submitted</span>
            <?php else: ?>
                <span class="font-bold text-red-500">Unpaid</span>
            <?php endif; ?>
        </p>
    </div>
    <a href="/chat/conversation.php?id=<?= (int) $order['conversation_id'] ?>" class="inline-flex items-center gap-1.5 rounded-full border border-stone-200 px-3.5 py-1.5 text-xs font-bold text-stone-700 hover:border-accent hover:text-accent">
        <?php icon('chat-bubble', 'w-3.5 h-3.5'); ?> Open chat
    </a>
</div>

<!-- Status timeline -->
<div class="rounded-2xl bg-white p-4">
    <?php if ($is_cancelled): ?>
        <p class="text-center text-sm font-bold text-red-500">This order was cancelled.</p>
    <?php else: ?>
        <div class="flex items-start">
            <?php foreach ($timeline as $i => $step): ?>
                <?php $done = $current_step !== false && $i <= $current_step; ?>
                <?php $is_current = $i === $current_step; ?>
                <div class="flex flex-col items-center <?= $i > 0 ? 'flex-1' : '' ?>">
                    <div class="flex w-full items-center">
                        <?php if ($i > 0): ?>
                            <div class="h-0.5 flex-1 transition-colors duration-700 <?= $done ? 'bg-accent' : 'bg-stone-200' ?>"></div>
                        <?php endif; ?>
                        <span class="relative flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold transition-colors duration-500 <?= $done ? 'bg-accent text-white' : 'bg-stone-200 text-stone-400' ?>">
                            <?php if ($is_current && !$is_cancelled): ?>
                                <span class="absolute inset-0 animate-ping rounded-full bg-accent opacity-60"></span>
                            <?php endif; ?>
                            <span class="relative">
                                <?php if ($done): ?><?php icon('check', 'w-3 h-3'); ?><?php else: ?><?= $i + 1 ?><?php endif; ?>
                            </span>
                        </span>
                    </div>
                    <span class="mt-1 text-[9px] font-semibold <?= $done ? 'text-stone-800' : 'text-stone-400' ?>"><?= htmlspecialchars($short_labels[$i], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="mt-2.5 text-center text-sm font-bold text-stone-900"><?= htmlspecialchars($order['status_label'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="text-center text-[11px] capitalize text-stone-400"><?= htmlspecialchars($order['fulfillment_type'], ENT_QUOTES, 'UTF-8') ?><?php if (!empty($order['delivery_address'])): ?> · <?= htmlspecialchars($order['delivery_address'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?></p>
    <?php endif; ?>
</div>

<!-- Items -->
<div class="rounded-2xl bg-white p-4">
    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">Order summary</p>
    <ul class="space-y-2">
        <?php foreach ($items as $item): ?>
            <li class="flex justify-between text-sm">
                <span class="text-stone-700"><?= (int) $item['quantity'] ?>&times; <?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?><?php if ($item['size_label']): ?> <span class="text-stone-400">(<?= htmlspecialchars($item['size_label'], ENT_QUOTES, 'UTF-8') ?>)</span><?php endif; ?></span>
                <span class="font-semibold">&#8369;<?= number_format((float) $item['line_total'], 2) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="mt-3 space-y-1 border-t border-stone-100 pt-2.5 text-sm">
        <p class="flex justify-between text-stone-500"><span>Subtotal</span><span>&#8369;<?= number_format((float) $order['subtotal'], 2) ?></span></p>
        <p class="flex justify-between text-stone-500"><span>Additional charges</span><span>&#8369;<?= number_format((float) $order['additional_charges'], 2) ?></span></p>
        <p class="flex justify-between text-base font-black text-stone-900"><span>Total</span><span>&#8369;<?= number_format((float) $order['total_amount'], 2) ?></span></p>
    </div>
</div>

<!-- Payment -->
<div class="rounded-2xl bg-white p-4">
    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">Payment</p>
    <?php if ($order['payment_status'] === 'unpaid'): ?>
        <?php if ($role === 'customer' && $upload_auth): ?>
            <?php if (!empty($_GET['pay_error'])): ?>
                <p class="mb-3 rounded-xl bg-red-50 px-3 py-2 text-xs font-medium text-red-600">
                    <?= htmlspecialchars((string) $_GET['pay_error'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>
            <?php if (PayMongo::isConfigured()): ?>
                <?php $saved_qr = (string) ($_SESSION['paymongo_qr_' . $order_id] ?? ''); ?>
                <div id="higoffe-qrph-box-<?= (int) $order_id ?>" class="mb-4 space-y-2.5">
                    <button
                        type="button"
                        data-qrph-order="<?= (int) $order_id ?>"
                        id="higoffe-qrph-btn-<?= (int) $order_id ?>"
                        class="<?= $saved_qr !== '' ? 'hidden ' : '' ?>w-full rounded-xl bg-stone-900 py-2.5 text-sm font-bold text-white transition hover:bg-stone-800 active:opacity-80 disabled:opacity-50"
                    >
                        Pay via QR Ph (GCash / Maya / Bank)
                    </button>
                    <div id="higoffe-qrph-panel-<?= (int) $order_id ?>" class="<?= $saved_qr !== '' ? '' : 'hidden ' ?>rounded-xl border border-stone-200 bg-stone-50 p-4 text-center">
                        <p class="text-xs font-bold uppercase tracking-wide text-stone-500">Scan to pay &#8369;<?= number_format((float) $order['total_amount'], 2) ?></p>
                        <img id="higoffe-qrph-img-<?= (int) $order_id ?>" src="<?= htmlspecialchars($saved_qr, ENT_QUOTES, 'UTF-8') ?>" alt="QR Ph Code" class="mx-auto mt-3 h-52 w-52 rounded-lg border border-stone-200 bg-white p-2 object-contain" />
                        <p class="mt-2.5 text-[11px] text-stone-500">Open GCash, Maya, or your banking app and scan this QR Ph code. Waiting for payment confirmation...</p>
                        <?php if (PayMongo::isTestMode()): ?>
                            <button
                                type="button"
                                data-qrph-sim="<?= (int) $order_id ?>"
                                class="mt-3 w-full rounded-lg border border-amber-300 bg-amber-50 py-2 text-xs font-bold text-amber-800 hover:bg-amber-100 active:opacity-80 disabled:opacity-50"
                            >
                                Test Mode: Simulate QR Payment
                            </button>
                        <?php endif; ?>
                    </div>
                    <p id="higoffe-qrph-err-<?= (int) $order_id ?>" class="hidden rounded-xl bg-red-50 px-3 py-2 text-xs font-medium text-red-600"></p>
                </div>
                <script>
                (function () {
                    if (!window.__higoffeQrPhPolls) window.__higoffeQrPhPolls = {};
                    function startPoll(oid) {
                        if (window.__higoffeQrPhPolls[oid]) return;
                        window.__higoffeQrPhPolls[oid] = setInterval(function () {
                            fetch('/orders/paymongo_poll.php?id=' + oid)
                                .then(function (r) { return r.json(); })
                                .then(function (p) {
                                    if (p.paid) {
                                        clearInterval(window.__higoffeQrPhPolls[oid]);
                                        delete window.__higoffeQrPhPolls[oid];
                                        window.location.href = '/orders/view.php?id=' + oid;
                                    }
                                })
                                .catch(function () {});
                        }, 3000);
                    }
                    <?php if ($saved_qr !== ''): ?>
                    startPoll(<?= (int) $order_id ?>);
                    <?php endif; ?>
                    if (window.__higoffeQrPhBound) return;
                    window.__higoffeQrPhBound = true;
                    document.addEventListener('click', function (ev) {
                        var simBtn = ev.target && ev.target.closest ? ev.target.closest('[data-qrph-sim]') : null;
                        if (simBtn) {
                            var soid = simBtn.getAttribute('data-qrph-sim');
                            simBtn.disabled = true;
                            simBtn.textContent = 'Confirming test payment...';
                            var sfd = new FormData();
                            sfd.append('order_id', String(soid));
                            fetch('/orders/paymongo_test_simulate.php', { method: 'POST', body: sfd })
                                .then(function (r) { return r.json(); })
                                .then(function (d) {
                                    if (d.paid) {
                                        if (window.__higoffeQrPhPolls[soid]) {
                                            clearInterval(window.__higoffeQrPhPolls[soid]);
                                            delete window.__higoffeQrPhPolls[soid];
                                        }
                                        window.location.href = '/orders/view.php?id=' + soid;
                                    }
                                })
                                .catch(function () {
                                    simBtn.disabled = false;
                                    simBtn.textContent = 'Test Mode: Simulate QR Payment';
                                });
                            return;
                        }
                        var btn = ev.target && ev.target.closest ? ev.target.closest('[data-qrph-order]') : null;
                        if (!btn) return;
                        var oid = btn.getAttribute('data-qrph-order');
                        var panel = document.getElementById('higoffe-qrph-panel-' + oid);
                        var img = document.getElementById('higoffe-qrph-img-' + oid);
                        var errBox = document.getElementById('higoffe-qrph-err-' + oid);
                        if (!panel || !img || !errBox) return;
                        errBox.classList.add('hidden');
                        btn.disabled = true;
                        btn.textContent = 'Generating QR Ph...';
                        var fd = new FormData();
                        fd.append('order_id', String(oid));
                        fetch('/orders/paymongo_qr.php', { method: 'POST', body: fd })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                if (!data.ok || !data.qr_image) {
                                    throw new Error(data.error || 'Could not generate QR Ph code.');
                                }
                                img.src = data.qr_image;
                                panel.classList.remove('hidden');
                                btn.classList.add('hidden');
                                startPoll(oid);
                            })
                            .catch(function (e) {
                                btn.disabled = false;
                                btn.textContent = 'Pay via QR Ph (GCash / Maya / Bank)';
                                errBox.textContent = e.message || 'Payment error.';
                                errBox.classList.remove('hidden');
                            });
                    });
                })();
                </script>
                <div class="relative mb-4 flex items-center">
                    <div class="flex-grow border-t border-stone-200"></div>
                    <span class="mx-3 shrink text-[11px] font-semibold uppercase tracking-wide text-stone-400">or upload proof</span>
                    <div class="flex-grow border-t border-stone-200"></div>
                </div>
            <?php endif; ?>
            <form method="post" action="/orders/submit_payment.php" class="space-y-3">
                <input type="hidden" name="order_id" value="<?= (int) $order_id ?>">
                <?php render_image_upload_field($upload_auth, 'proof_url', 'Upload payment screenshot (GCash / bank transfer)', '', 5, false); ?>
                <button type="submit" class="w-full rounded-xl bg-accent py-2.5 text-sm font-bold text-white active:opacity-80 disabled:opacity-50">Submit payment proof</button>
            </form>
            <script src="/includes/image_upload.js"></script>
        <?php else: ?>
            <p class="text-sm text-stone-500">Waiting for the customer to submit payment proof.</p>
        <?php endif; ?>
    <?php elseif ($order['payment_status'] === 'proof_submitted'): ?>
        <?php if (!empty($order['payment_proof_url'])): ?>
            <img src="<?= htmlspecialchars($order['payment_proof_url'], ENT_QUOTES, 'UTF-8') ?>" alt="Payment proof" loading="lazy" decoding="async" class="lightbox-img mb-2.5 h-32 w-32 cursor-pointer rounded-xl border border-stone-200 object-cover">
        <?php endif; ?>
        <?php if ($is_owner_seller): ?>
            <form method="post" action="/orders/confirm_payment.php">
                <input type="hidden" name="order_id" value="<?= (int) $order_id ?>">
                <button type="submit" class="w-full rounded-xl bg-accent py-2.5 text-sm font-bold text-white active:opacity-80">Confirm payment</button>
            </form>
        <?php else: ?>
            <p class="text-sm text-stone-500">Proof submitted — waiting for the café to confirm.</p>
        <?php endif; ?>
    <?php else: ?>
        <p class="flex items-center gap-1.5 text-sm font-bold text-green-600"><?php icon('check-circle', 'w-4 h-4'); ?> Payment confirmed</p>
    <?php endif; ?>
</div>

<!-- Seller status actions -->
<?php if ($is_owner_seller && !empty($next_statuses)): ?>
    <div class="rounded-2xl bg-white p-4">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">Update status</p>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($next_statuses as $status): ?>
                <form method="post" action="/orders/update_status.php">
                    <input type="hidden" name="order_id" value="<?= (int) $order_id ?>">
                    <input type="hidden" name="to_label" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="rounded-full <?= $status === 'Cancelled' ? 'border border-red-300 text-red-500' : 'bg-coffee text-white' ?> px-4 py-2 text-xs font-bold active:opacity-80">
                        <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
    </div>
<?php elseif ($role === 'customer' && $order['status_label'] === 'Pending'): ?>
    <form method="post" action="/orders/confirm.php" data-confirm="Cancel this order?">
        <input type="hidden" name="order_id" value="<?= (int) $order_id ?>">
        <input type="hidden" name="action" value="cancel">
        <button type="submit" class="w-full rounded-xl border border-red-300 bg-white py-2.5 text-sm font-bold text-red-500 active:bg-red-50">Cancel order</button>
    </form>
<?php endif; ?>
