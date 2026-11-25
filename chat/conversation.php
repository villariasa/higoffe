<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../includes/auth_check.php';

$conversation_id = (int) ($_GET['id'] ?? 0);

$pdo = (new Database())->connect();
$conversationModel = new Conversation($pdo);
$conversation = $conversationModel->getById($conversation_id);

if (!$conversation) {
    header("Location: /index.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

$is_participant = ($role === 'customer' && (int) $conversation['customer_user_id'] === $user_id)
    || ($role === 'seller' && (int) $conversation['assigned_seller_user_id'] === $user_id);

if (!$is_participant) {
    header("Location: /index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'send_message';

    if ($action === 'resolve' && $role === 'seller') {
        $conversationModel->resolve($conversation_id);
        header("Location: /queue/index.php");
        exit;
    }

    $body = (string) trim($_POST['body'] ?? '');
    if ($body !== '') {
        $messageModel = new Message($pdo);
        $messageModel->create($conversation_id, $user_id, 'text', $body);
        $conversationModel->touchLastMessageAt($conversation_id);
    }
    header("Location: /chat/conversation.php?id={$conversation_id}");
    exit;
}

$messageModel = new Message($pdo);
$messages = $messageModel->getAllForConversation($conversation_id);
$orderModel = new Order($pdo);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-xl mx-auto bg-white border border-stone-200 rounded-lg flex flex-col" style="height: 70vh;">
    <div class="p-4 border-b border-stone-200 flex items-center justify-between">
        <h1 class="font-bold">Conversation #<?= (int) $conversation_id ?></h1>
        <?php if ($role === 'seller' && $conversation['status'] === 'active'): ?>
            <div class="flex gap-3 text-sm">
                <a href="/orders/create_order_card.php?conversation_id=<?= $conversation_id ?>" class="text-amber-700 underline">Create order</a>
                <form method="post" onsubmit="return confirm('Mark this inquiry resolved?');">
                    <input type="hidden" name="action" value="resolve">
                    <button type="submit" class="text-stone-500 underline">Resolve</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div id="message-list" class="flex-1 overflow-y-auto p-4 space-y-3"
         data-conversation-id="<?= $conversation_id ?>"
         data-after-id="<?= !empty($messages) ? (int) end($messages)['message_id'] : 0 ?>">
        <?php foreach ($messages as $m): ?>
            <?php include __DIR__ . '/_message_bubble.php'; ?>
        <?php endforeach; ?>
    </div>

    <form id="send-form" method="post" class="p-3 border-t border-stone-200 flex gap-2">
        <input type="hidden" name="action" value="send_message">
        <input type="text" id="body-input" name="body" placeholder="Type a message..." required class="flex-1 border border-stone-300 rounded p-2 text-sm">
        <button type="submit" class="bg-amber-700 text-white rounded px-4 text-sm font-semibold">Send</button>
    </form>
</div>

<script>
(function () {
    const list = document.getElementById('message-list');
    const conversationId = list.dataset.conversationId;
    let afterId = parseInt(list.dataset.afterId, 10) || 0;
    const myUserId = <?= (int) $user_id ?>;

    function escapeHtml(s) {
        return s.replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    async function poll() {
        if (document.hidden) return;
        const res = await fetch('/chat/poll_messages.php?conversation_id=' + conversationId + '&after_id=' + afterId);
        const rows = await res.json();
        rows.forEach(function (m) {
            const wrap = document.createElement('div');
            wrap.className = 'flex ' + (m.sender_user_id == myUserId ? 'justify-end' : 'justify-start');
            const bubble = document.createElement('div');
            bubble.className = 'max-w-[80%] rounded-lg px-3 py-2 text-sm ' + (m.sender_user_id == myUserId ? 'bg-amber-700 text-white' : 'bg-stone-100 text-stone-800');
            if (m.message_type === 'order_card') {
                bubble.innerHTML = 'New order card — <a class="underline" href="/orders/view.php?id=' + m.order_id + '">view / respond</a>';
            } else {
                bubble.textContent = m.body || '';
            }
            wrap.appendChild(bubble);
            list.appendChild(wrap);
            afterId = Math.max(afterId, m.message_id);
        });
        if (rows.length) list.scrollTop = list.scrollHeight;
    }

    document.getElementById('send-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const input = document.getElementById('body-input');
        const body = input.value.trim();
        if (!body) return;
        input.value = '';
        await fetch('/chat/send_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'conversation_id=' + conversationId + '&body=' + encodeURIComponent(body)
        });
        poll();
    });

    setInterval(poll, 3000);
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
