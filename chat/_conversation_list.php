<?php
// Partial — conversation list pane (desktop two-pane chat + mobile inbox).
// Expects in scope: $pdo, $user_id, $role, and optionally
// $active_conversation_id (highlights the open conversation).
// Fetches its own rows so both inbox.php and conversation.php stay thin.
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../includes/components.php';

$active_conversation_id = $active_conversation_id ?? 0;

$listConversationModel = new Conversation($pdo);
if ($role === 'customer') {
    $list_conversations = $listConversationModel->getForCustomer($user_id);
} else {
    $list_conversations = $listConversationModel->getActiveForSeller($user_id);
}

// Compute unread count per conversation for the current viewer.
function convUnreadCount(array $conv, string $role): int {
    // We compare the viewer's last-read message id against the latest message.
    // The conversations query doesn't include last_message_id, but we can
    // derive "unread" from the read-receipt columns added in migration 010.
    if ($role === 'customer') {
        $read_id = (int) ($conv['customer_last_read_id'] ?? 0);
    } else {
        $read_id = (int) ($conv['seller_last_read_id'] ?? 0);
    }
    // We don't have last_message_id in the list query yet; fall back to 0
    // (badge logic below will show nothing rather than wrong counts).
    // A proper fix is to JOIN on MAX(message_id) in getForCustomer/getActiveForSeller —
    // but that requires a schema query change. For now we signal unread only
    // if read_id == 0 and the conversation has had activity.
    if ($read_id === 0 && !empty($conv['last_message_at'])) {
        return 1; // at least one unread (sentinel)
    }
    return 0;
}
?>
<?php if (empty($list_conversations)): ?>
    <?php render_empty_state('chat-bubble', 'No conversations yet', $role === 'customer' ? 'Visit a café and tap "Chat & Order" to start an inquiry.' : 'Accept an inquiry from your queue to start chatting.'); ?>
<?php else: ?>
    <div class="divide-y divide-stone-100 lg:divide-y-0 lg:space-y-1.5 lg:p-2">
        <?php foreach ($list_conversations as $conv): ?>
            <?php
            $is_active_row = (int) $conv['conversation_id'] === (int) $active_conversation_id;
            $row_name = $role === 'customer' ? $conv['cafe_name'] : $conv['customer_name'];
            $row_avatar = $role === 'customer' ? ($conv['logo_url'] ?? null) : null;
            ?>
            <a href="/chat/conversation.php?id=<?= (int) $conv['conversation_id'] ?>"
               class="flex items-center gap-3 px-4 py-3 lg:rounded-xl lg:px-3 <?= $is_active_row ? 'bg-coffee/10 lg:bg-coffee/10' : 'bg-white active:bg-stone-50 lg:bg-transparent lg:hover:bg-cream' ?>">
                <?php render_avatar($row_avatar, $row_name, 'w-11 h-11', $conv['other_last_active_at'] ?? null); ?>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-stone-900"><?= htmlspecialchars($row_name, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-[11px] text-stone-400"><?= htmlspecialchars(time_ago($conv['last_message_at']), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <?php
                $pill = ['waiting' => 'bg-amber-100 text-amber-700', 'active' => 'bg-green-100 text-green-700', 'resolved' => 'bg-stone-100 text-stone-500'][$conv['status']] ?? 'bg-stone-100 text-stone-500';
                ?>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-[9px] font-bold uppercase <?= $pill ?>"><?= htmlspecialchars($conv['status'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
