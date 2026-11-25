<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';
require_role('customer');

$pdo = (new Database())->connect();
$conversationModel = new Conversation($pdo);
$conversations = $conversationModel->getForCustomer((int) $_SESSION['user_id']);

$page_title = 'Messages';
$back_url = '/feed/index.php';
require_once __DIR__ . '/../includes/header.php';
?>

<?php if (empty($conversations)): ?>
    <?php render_empty_state('💬', 'No conversations yet', 'Visit a café and tap "Chat & Order" to start an inquiry.'); ?>
<?php else: ?>
    <div class="divide-y divide-stone-100">
        <?php foreach ($conversations as $conv): ?>
            <a href="/chat/conversation.php?id=<?= (int) $conv['conversation_id'] ?>" class="flex items-center gap-3 bg-white px-4 py-3 active:bg-stone-50">
                <?php render_avatar($conv['logo_url'] ?? null, $conv['cafe_name'], 'w-11 h-11'); ?>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-stone-900"><?= htmlspecialchars($conv['cafe_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-[11px] text-stone-400">Last activity <?= htmlspecialchars(date('M j, g:i A', strtotime($conv['last_message_at'])), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <?php
                $pill = ['waiting' => 'bg-amber-100 text-amber-700', 'active' => 'bg-green-100 text-green-700', 'resolved' => 'bg-stone-100 text-stone-500'][$conv['status']] ?? 'bg-stone-100 text-stone-500';
                ?>
                <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase <?= $pill ?>"><?= htmlspecialchars($conv['status'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
