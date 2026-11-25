<?php
// Partial include — renders one message bubble. Expects $m, $user_id,
// and $orderModel to already be in scope from the including page.
require_once __DIR__ . '/../includes/icons.php';

if ($m['message_type'] === 'system') {
    ?>
    <p class="py-0.5 text-center text-[11px] italic text-stone-400"><?= htmlspecialchars($m['body'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    <?php
    return;
}

$is_mine = (int) $m['sender_user_id'] === $user_id;
?>
<div class="flex flex-col <?= $is_mine ? 'items-end' : 'items-start' ?>" data-message-id="<?= (int) $m['message_id'] ?>" data-mine="<?= $is_mine ? '1' : '0' ?>">
    <div class="flex items-end gap-2 <?= $is_mine ? 'justify-end' : 'justify-start' ?>">
        <div class="max-w-[75%] rounded-2xl px-3 py-2 text-sm <?= $is_mine ? 'rounded-br-md bg-coffee text-white' : 'rounded-bl-md border border-stone-200 bg-white text-stone-800' ?>">
            <?php if ($m['message_type'] === 'order_card' && $m['order_id']): ?>
                <?php $order = $orderModel->getById((int) $m['order_id']); ?>
                <?php if ($order): ?>
                    <p class="flex items-center gap-1.5 font-bold"><?php icon('shopping-bag', 'w-4 h-4'); ?> Order card — &#8369;<?= number_format((float) $order['total_amount'], 2) ?></p>
                    <p class="text-xs opacity-75">Status: <?= htmlspecialchars($order['status_label'], ENT_QUOTES, 'UTF-8') ?></p>
                    <a href="/orders/view.php?id=<?= (int) $order['order_id'] ?>" class="text-xs font-semibold underline">View / respond</a>
                <?php endif; ?>
            <?php elseif ($m['message_type'] === 'image' && !empty($m['image_url'])): ?>
                <a href="<?= htmlspecialchars($m['image_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                    <img src="<?= htmlspecialchars($m['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" decoding="async" class="max-h-64 max-w-full rounded-xl object-cover">
                </a>
                <?php if (!empty($m['body'])): ?>
                    <p class="mt-1"><?= htmlspecialchars($m['body'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            <?php else: ?>
                <?= htmlspecialchars($m['body'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
        </div>
    </div>
</div>
