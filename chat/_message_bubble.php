<?php
// Partial include — renders one message bubble. Expects $m, $user_id,
// and $orderModel to already be in scope from the including page.

if ($m['message_type'] === 'system') {
    ?>
    <p class="text-center text-xs text-stone-400 italic py-1"><?= htmlspecialchars($m['body'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    <?php
    return;
}

$is_mine = (int) $m['sender_user_id'] === $user_id;
?>
<div class="flex items-end gap-2 <?= $is_mine ? 'justify-end' : 'justify-start' ?>">
    <?php if (!$is_mine): ?>
        <div class="w-6 h-6 rounded-full bg-stone-200 shrink-0"></div>
    <?php endif; ?>
    <div class="max-w-[75%] rounded-2xl px-3 py-2 text-sm <?= $is_mine ? 'bg-amber-700 text-white rounded-br-sm' : 'bg-stone-100 text-stone-800 rounded-bl-sm' ?>">
        <?php if ($m['message_type'] === 'order_card' && $m['order_id']): ?>
            <?php $order = $orderModel->getById((int) $m['order_id']); ?>
            <?php if ($order): ?>
                <p class="font-semibold">&#9749; Order card — &#8369;<?= number_format((float) $order['total_amount'], 2) ?></p>
                <p class="text-xs opacity-80">Status: <?= htmlspecialchars($order['status_label'], ENT_QUOTES, 'UTF-8') ?></p>
                <a href="/orders/view.php?id=<?= (int) $order['order_id'] ?>" class="text-xs underline">View / respond</a>
            <?php endif; ?>
        <?php elseif ($m['message_type'] === 'image' && !empty($m['image_url'])): ?>
            <a href="<?= htmlspecialchars($m['image_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                <img src="<?= htmlspecialchars($m['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="rounded-lg max-w-full max-h-64 object-cover">
            </a>
            <?php if (!empty($m['body'])): ?>
                <p class="mt-1"><?= htmlspecialchars($m['body'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        <?php else: ?>
            <?= htmlspecialchars($m['body'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
    </div>
</div>
