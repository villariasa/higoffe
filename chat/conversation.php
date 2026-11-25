<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Uploads.php';
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
$upload_auth = Uploads::issueToken($user_id, 'chat');

require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-xl mx-auto bg-white border border-stone-200 rounded-2xl shadow-sm flex flex-col" style="height: 70vh;">
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

    <form id="send-form" class="p-3 border-t border-stone-200 flex items-center gap-2">
        <label class="shrink-0 cursor-pointer text-stone-500 hover:text-amber-700" title="Send a photo">
            &#128247;
            <input type="file" id="chat-image-input" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden">
        </label>
        <input type="text" id="body-input" placeholder="Type a message..." class="flex-1 border border-stone-300 rounded-full px-3 py-2 text-sm">
        <button type="submit" class="bg-amber-700 text-white rounded-full px-4 py-2 text-sm font-semibold">Send</button>
    </form>
    <p id="chat-status" class="px-3 pb-2 text-xs text-stone-400"></p>
</div>

<script>
(function () {
    const list = document.getElementById('message-list');
    const conversationId = list.dataset.conversationId;
    let afterId = parseInt(list.dataset.afterId, 10) || 0;
    const myUserId = <?= (int) $user_id ?>;
    const status = document.getElementById('chat-status');

    const uploadAuth = {
        workerUrl: <?= json_encode($upload_auth['worker_url']) ?>,
        token: <?= json_encode($upload_auth['token']) ?>,
        expiry: <?= (int) $upload_auth['expiry'] ?>,
        userId: <?= (int) $upload_auth['user_id'] ?>,
        scope: <?= json_encode($upload_auth['scope']) ?>,
    };

    function renderBubble(m) {
        if (m.message_type === 'system') {
            const p = document.createElement('p');
            p.className = 'text-center text-xs text-stone-400 italic py-1';
            p.textContent = m.body || '';
            return p;
        }
        const isMine = m.sender_user_id == myUserId;
        const wrap = document.createElement('div');
        wrap.className = 'flex items-end gap-2 ' + (isMine ? 'justify-end' : 'justify-start');
        if (!isMine) {
            const avatar = document.createElement('div');
            avatar.className = 'w-6 h-6 rounded-full bg-stone-200 shrink-0';
            wrap.appendChild(avatar);
        }
        const bubble = document.createElement('div');
        bubble.className = 'max-w-[75%] rounded-2xl px-3 py-2 text-sm ' +
            (isMine ? 'bg-amber-700 text-white rounded-br-sm' : 'bg-stone-100 text-stone-800 rounded-bl-sm');
        if (m.message_type === 'order_card') {
            const a = document.createElement('a');
            a.className = 'underline text-xs';
            a.href = '/orders/view.php?id=' + m.order_id;
            a.textContent = 'New order card — view / respond';
            bubble.appendChild(a);
        } else if (m.message_type === 'image' && m.image_url) {
            const img = document.createElement('img');
            img.src = m.image_url;
            img.className = 'rounded-lg max-w-full max-h-64 object-cover';
            bubble.appendChild(img);
        } else {
            bubble.textContent = m.body || '';
        }
        wrap.appendChild(bubble);
        return wrap;
    }

    async function poll() {
        if (document.hidden) return;
        const res = await fetch('/chat/poll_messages.php?conversation_id=' + conversationId + '&after_id=' + afterId);
        const rows = await res.json();
        rows.forEach(function (m) {
            list.appendChild(renderBubble(m));
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

    document.getElementById('chat-image-input').addEventListener('change', async function (e) {
        const file = e.target.files[0];
        if (!file) return;
        status.textContent = 'Uploading photo...';

        const form = new FormData();
        form.append('file', file);
        form.append('token', uploadAuth.token);
        form.append('expiry', uploadAuth.expiry);
        form.append('user_id', uploadAuth.userId);
        form.append('scope', uploadAuth.scope);

        try {
            const res = await fetch(uploadAuth.workerUrl + '/upload', { method: 'POST', body: form });
            const data = await res.json();
            if (!data.ok) {
                status.textContent = data.error || 'Upload failed.';
                return;
            }
            await fetch('/chat/send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'conversation_id=' + conversationId + '&type=image&image_url=' + encodeURIComponent(data.url)
            });
            status.textContent = '';
            poll();
        } catch (err) {
            status.textContent = 'Upload failed — check your connection.';
        }
        e.target.value = '';
    });

    setInterval(poll, 3000);
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
