<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('customer');

$pdo = (new Database())->connect();
$conversationModel = new Conversation($pdo);
$conversations = $conversationModel->getForCustomer((int) $_SESSION['user_id']);

require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-bold mb-4">Messages</h1>

<div class="bg-white border border-stone-200 rounded-lg divide-y divide-stone-200 max-w-xl">
    <?php foreach ($conversations as $conv): ?>
        <a href="/chat/conversation.php?id=<?= (int) $conv['conversation_id'] ?>" class="flex items-center justify-between p-4 hover:bg-stone-50">
            <div class="flex items-center gap-3">
                <?php if (!empty($conv['logo_url'])): ?>
                    <img src="<?= htmlspecialchars($conv['logo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-8 h-8 rounded-full object-cover">
                <?php endif; ?>
                <span class="font-semibold"><?= htmlspecialchars($conv['cafe_name'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <span class="text-xs text-stone-400 capitalize"><?= htmlspecialchars($conv['status'], ENT_QUOTES, 'UTF-8') ?></span>
        </a>
    <?php endforeach; ?>
    <?php if (empty($conversations)): ?>
        <p class="p-4 text-stone-500 text-sm">No conversations yet — visit a café and start an inquiry.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
