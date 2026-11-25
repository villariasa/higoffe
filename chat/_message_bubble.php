<?php
// Partial include — renders one message bubble. Expects $m, $user_id,
// and $orderModel to already be in scope from the including page.
$is_mine = (int) $m['sender_user_id'] === $user_id;
?>
<div class="flex <?= $is_mine ? 'justify-end' : 'justify-start' ?>">
    <div class="max-w-[80%] rounded-lg px-3 py-2 text-sm <?= $is_mine ? 'bg-amber-700 text-white' : 'bg-stone-100 text-stone-800' ?>">
        <?php if ($m['message_type'] === 'order_card' && $m['order_id']): ?>
            <?php $order = $orderModel->getById((int) $m['order_id']); ?>
            <?php if ($order): ?>
                <p class="font-semibold">Order card — &#8369;<?= number_format((float) $order['total_amount'], 2) ?></p>
                <p class="text-xs opacity-80">Status: <?= htmlspecialchars($order['status_label'], ENT_QUOTES, 'UTF-8') ?></p>
                <a href="/orders/view.php?id=<?= (int) $order['order_id'] ?>" class="text-xs underline">View / respond</a>
            <?php endif; ?>
        <?php else: ?>
            <?= htmlspecialchars($m['body'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
    </div>
</div>
