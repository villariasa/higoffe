<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../models/Order.php';
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

// is_array(...) ?: [] guards, not (array) casts — (array) false would
// wrap it into a 1-element [false] array instead of an empty list.
$conversationModel = new Conversation($pdo);
$waiting_raw = $conversationModel->getWaitingQueue((int) $cafe['cafe_id']);
$waiting = is_array($waiting_raw) ? $waiting_raw : [];
$active_raw = $conversationModel->getActiveForSeller((int) $_SESSION['user_id']);
$active = is_array($active_raw) ? $active_raw : [];
$resolved_raw = $conversationModel->getResolvedForCafe((int) $cafe['cafe_id']);
$resolved = is_array($resolved_raw) ? $resolved_raw : [];

$orderModel = new Order($pdo);
$order_summary = [];
foreach (['Pending', 'Confirmed', 'Preparing', 'Ready for Pickup'] as $status_label) {
    $order_summary[$status_label] = count($orderModel->getForSellerByStatus((int) $cafe['cafe_id'], $status_label));
}
$week_sales = $orderModel->getCafeSalesSummary((int) $cafe['cafe_id'], 7);

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Café header strip -->
<div class="flex items-center gap-3 px-4 pt-3 lg:px-6 lg:pt-6">
    <?php render_avatar($cafe['logo_url'] ?? null, $cafe['cafe_name'], 'w-10 h-10'); ?>
    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-black text-stone-900"><?= htmlspecialchars($cafe['cafe_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="text-[11px] text-stone-400">Seller dashboard</p>
    </div>
    <a href="/menu/index.php" class="shrink-0 rounded-full border border-stone-200 bg-white px-3 py-1.5 text-[11px] font-bold text-stone-600">Menu</a>
    <a href="/cafes/profile.php" class="shrink-0 rounded-full border border-stone-200 bg-white px-3 py-1.5 text-[11px] font-bold text-stone-600">Edit café</a>
</div>

<!-- This week -->
<div class="mt-4 grid grid-cols-2 gap-2 px-4 lg:gap-3 lg:px-6">
    <div class="rounded-2xl bg-white p-3.5 lg:border lg:border-stone-100">
        <p class="text-[10px] font-bold uppercase tracking-wide text-stone-400">Completed (7 days)</p>
        <p class="mt-0.5 text-xl font-black text-stone-900"><?= (int) $week_sales['order_count'] ?></p>
    </div>
    <div class="rounded-2xl bg-white p-3.5 lg:border lg:border-stone-100">
        <p class="text-[10px] font-bold uppercase tracking-wide text-stone-400">Revenue (7 days)</p>
        <p class="mt-0.5 text-xl font-black text-coffee">&#8369;<?= number_format((float) $week_sales['total_revenue'], 2) ?></p>
    </div>
</div>

<!-- Order status summary -->
<div class="mt-3 grid grid-cols-4 gap-2 px-4 lg:gap-3 lg:px-6">
    <?php foreach ($order_summary as $label => $count): ?>
        <a href="/orders/seller_dashboard.php" class="rounded-xl bg-white p-2.5 text-center">
            <p class="text-lg font-black <?= $count > 0 ? 'text-accent' : 'text-stone-300' ?>"><?= $count ?></p>
            <p class="text-[9px] font-semibold uppercase leading-tight text-stone-500"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></p>
        </a>
    <?php endforeach; ?>
</div>

<!-- Waiting queue -->
<section class="mt-5">
    <?php render_section_header('New inquiries (' . count($waiting) . ')'); ?>
    <?php if (empty($waiting)): ?>
        <?php render_empty_state('check-circle', 'Queue is clear', 'New customer inquiries will appear here, oldest first.'); ?>
    <?php else: ?>
        <div id="waiting-list" class="space-y-2 px-4 lg:grid lg:grid-cols-2 lg:gap-3 lg:space-y-0 lg:px-6">
            <?php foreach ($waiting as $conv): ?>
                <div class="flex items-center gap-3 rounded-2xl bg-white p-3">
                    <?php render_avatar(null, $conv['customer_name'], 'w-10 h-10'); ?>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-stone-900"><?= htmlspecialchars($conv['customer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-[11px] text-stone-400">Waiting since <?= htmlspecialchars(date('g:i A', strtotime($conv['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <button type="button" class="claim-btn shrink-0 rounded-full bg-accent px-4 py-2 text-xs font-bold text-white active:opacity-80" data-conversation-id="<?= (int) $conv['conversation_id'] ?>">Accept</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Active conversations -->
<section class="mt-5">
    <?php render_section_header('Active chats (' . count($active) . ')'); ?>
    <?php if (empty($active)): ?>
        <?php render_empty_state('chat-bubble', 'No active conversations'); ?>
    <?php else: ?>
        <div class="divide-y divide-stone-100 lg:grid lg:grid-cols-2 lg:gap-3 lg:divide-y-0 lg:px-6">
            <?php foreach ($active as $conv): ?>
                <a href="/chat/conversation.php?id=<?= (int) $conv['conversation_id'] ?>" class="flex items-center gap-3 bg-white px-4 py-3 active:bg-stone-50 lg:rounded-2xl lg:border lg:border-stone-100 lg:hover:shadow-md lg:transition-shadow">
                    <?php render_avatar(null, $conv['customer_name'], 'w-10 h-10'); ?>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-stone-900"><?= htmlspecialchars($conv['customer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-[11px] text-stone-400">Last activity <?= htmlspecialchars(date('M j, g:i A', strtotime($conv['last_message_at'])), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <span class="shrink-0 rounded-full bg-green-100 px-2.5 py-1 text-[10px] font-bold uppercase text-green-700">Active</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Resolved -->
<?php if (!empty($resolved)): ?>
    <section class="mt-5">
        <?php render_section_header('Resolved (' . count($resolved) . ')'); ?>
        <div class="divide-y divide-stone-100 opacity-70">
            <?php foreach (array_slice($resolved, 0, 5) as $conv): ?>
                <div class="flex items-center gap-3 bg-white px-4 py-2.5">
                    <?php render_avatar(null, $conv['customer_name'], 'w-8 h-8'); ?>
                    <p class="truncate text-[13px] font-semibold text-stone-600"><?= htmlspecialchars($conv['customer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<script>
document.querySelectorAll('.claim-btn').forEach(function (btn) {
    btn.addEventListener('click', async function () {
        const conversationId = btn.dataset.conversationId;
        const res = await fetch('/queue/claim.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'conversation_id=' + encodeURIComponent(conversationId)
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = '/chat/conversation.php?id=' + conversationId;
        } else {
            window.higoffeToast('Already claimed by another team member.', 'error');
            setTimeout(function () { window.location.reload(); }, 1200);
        }
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
