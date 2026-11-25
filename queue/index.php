<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('seller');

$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);
$cafe = $cafeModel->getByOwner((int) $_SESSION['user_id']);

if (!$cafe) {
    header("Location: /cafes/profile.php");
    exit;
}

$conversationModel = new Conversation($pdo);
$waiting = $conversationModel->getWaitingQueue((int) $cafe['cafe_id']);
$active = $conversationModel->getActiveForSeller((int) $_SESSION['user_id']);
$resolved = $conversationModel->getResolvedForCafe((int) $cafe['cafe_id']);

require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-bold mb-4">Inquiry queue</h1>

<div class="grid md:grid-cols-3 gap-4">
    <div>
        <h2 class="font-semibold mb-2">Waiting (<?= count($waiting) ?>)</h2>
        <div id="waiting-list" class="space-y-2">
            <?php foreach ($waiting as $conv): ?>
                <div class="bg-white border border-stone-200 rounded-lg p-3">
                    <p class="font-semibold text-sm"><?= htmlspecialchars($conv['customer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-stone-400">Waiting since <?= htmlspecialchars($conv['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
                    <button type="button" class="claim-btn mt-2 text-sm bg-amber-700 text-white rounded px-3 py-1" data-conversation-id="<?= (int) $conv['conversation_id'] ?>">Accept</button>
                </div>
            <?php endforeach; ?>
            <?php if (empty($waiting)): ?>
                <p class="text-stone-500 text-sm">No waiting inquiries.</p>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <h2 class="font-semibold mb-2">Active (<?= count($active) ?>)</h2>
        <div class="space-y-2">
            <?php foreach ($active as $conv): ?>
                <a href="/chat/conversation.php?id=<?= (int) $conv['conversation_id'] ?>" class="block bg-white border border-stone-200 rounded-lg p-3 hover:border-amber-400">
                    <p class="font-semibold text-sm"><?= htmlspecialchars($conv['customer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-stone-400">Last activity <?= htmlspecialchars($conv['last_message_at'], ENT_QUOTES, 'UTF-8') ?></p>
                </a>
            <?php endforeach; ?>
            <?php if (empty($active)): ?>
                <p class="text-stone-500 text-sm">No active conversations.</p>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <h2 class="font-semibold mb-2">Resolved (<?= count($resolved) ?>)</h2>
        <div class="space-y-2">
            <?php foreach ($resolved as $conv): ?>
                <div class="bg-white border border-stone-200 rounded-lg p-3 opacity-70">
                    <p class="font-semibold text-sm"><?= htmlspecialchars($conv['customer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            <?php endforeach; ?>
            <?php if (empty($resolved)): ?>
                <p class="text-stone-500 text-sm">Nothing resolved yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

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
            alert('Already claimed by another team member.');
            window.location.reload();
        }
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
