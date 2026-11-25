<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Uploads.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';

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
        $sent_id = $messageModel->create($conversation_id, $user_id, 'text', $body);
        $conversationModel->touchLastMessageAt($conversation_id);
        $conversationModel->clearTyping($conversation_id, $role);
        $conversationModel->markRead($conversation_id, $role, $sent_id);
    }
    header("Location: /chat/conversation.php?id={$conversation_id}");
    exit;
}

$messageModel = new Message($pdo);
$messages = $messageModel->getAllForConversation($conversation_id);
$orderModel = new Order($pdo);
$upload_auth = Uploads::issueToken($user_id, 'chat');

// Header identity: customer sees the café; seller sees the customer.
if ($role === 'customer') {
    $cafeModel = new Cafe($pdo);
    $other = $cafeModel->getById((int) $conversation['cafe_id']);
    $other_name = $other['cafe_name'] ?? 'Café';
    $other_avatar = $other['logo_url'] ?? null;
    $back = '/chat/inbox.php';
} else {
    $userModel = new User($pdo);
    $other = $userModel->getById((int) $conversation['customer_user_id']);
    $other_name = $other['full_name'] ?? 'Customer';
    $other_avatar = $other['avatar_url'] ?? null;
    $back = '/queue/index.php';
}

$quick_replies = [
    "Hi! What can I get started for you today?",
    "That's available — any size or add-ons?",
    "Got it, preparing your order card now.",
    "Your order is ready for pickup!",
];

$no_shell = true; // custom chat top bar below
require_once __DIR__ . '/../includes/header.php';
?>

<div class="lg:grid lg:h-[calc(100vh-6rem)] lg:grid-cols-[360px_minmax(0,1fr)] lg:px-8 lg:pt-4">
    <!-- Desktop conversation list pane -->
    <div class="hidden lg:block lg:overflow-y-auto lg:rounded-l-2xl lg:border lg:border-stone-200 lg:bg-white">
        <?php $active_conversation_id = $conversation_id; include __DIR__ . '/_conversation_list.php'; ?>
    </div>

<div class="flex h-[calc(100dvh-5rem)] w-full flex-col lg:h-full lg:rounded-r-2xl lg:border lg:border-l-0 lg:border-stone-200 lg:bg-white">
    <!-- Chat top bar -->
    <header class="sticky top-0 z-40 flex items-center gap-2 border-b border-stone-200 bg-cream/95 px-2 py-2 backdrop-blur lg:rounded-t-2xl lg:border lg:bg-white">
        <a href="<?= $back ?>" aria-label="Back" class="flex h-9 w-9 items-center justify-center rounded-full active:bg-stone-200">
            <?php icon('arrow-left', 'w-5 h-5 text-stone-700'); ?>
        </a>
        <?php render_avatar($other_avatar, $other_name, 'w-8 h-8'); ?>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-bold text-stone-900"><?= htmlspecialchars($other_name, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-[10px] uppercase tracking-wide text-stone-400"><?= htmlspecialchars($conversation['status'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <?php if ($role === 'customer'): ?>
            <a href="/orders/create_order_card.php?conversation_id=<?= $conversation_id ?>" class="shrink-0 rounded-full bg-accent px-3 py-1.5 text-[11px] font-bold text-white">+ Order</a>
        <?php elseif ($role === 'seller' && $conversation['status'] === 'active'): ?>
            <form method="post" data-confirm="Mark this inquiry resolved?" class="shrink-0">
                <input type="hidden" name="action" value="resolve">
                <button type="submit" class="px-2 text-[11px] font-semibold text-stone-400">Resolve</button>
            </form>
        <?php endif; ?>
    </header>

    <!-- Messages -->
    <div id="message-list" class="flex-1 space-y-2.5 overflow-y-auto px-3 py-3"
         data-conversation-id="<?= $conversation_id ?>"
         data-after-id="<?= !empty($messages) ? (int) end($messages)['message_id'] : 0 ?>">
        <?php foreach ($messages as $m): ?>
            <?php include __DIR__ . '/_message_bubble.php'; ?>
        <?php endforeach; ?>
    </div>

    <!-- Quick replies (seller only) -->
    <?php if ($role === 'seller'): ?>
        <div class="no-scrollbar flex gap-2 overflow-x-auto border-t border-stone-100 bg-cream px-3 py-2">
            <?php foreach ($quick_replies as $qr): ?>
                <button type="button" class="quick-reply shrink-0 rounded-full border border-coffee/30 bg-white px-3 py-1.5 text-[11px] font-semibold text-coffee active:bg-coffee active:text-white"
                        data-text="<?= htmlspecialchars($qr, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($qr, ENT_QUOTES, 'UTF-8') ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Typing indicator -->
    <p id="typing-indicator" class="hidden items-center gap-1 bg-white px-4 pb-1 text-[11px] italic text-stone-400">
        <span class="flex gap-0.5">
            <span class="h-1 w-1 animate-bounce rounded-full bg-stone-400" style="animation-delay:0ms"></span>
            <span class="h-1 w-1 animate-bounce rounded-full bg-stone-400" style="animation-delay:150ms"></span>
            <span class="h-1 w-1 animate-bounce rounded-full bg-stone-400" style="animation-delay:300ms"></span>
        </span>
        <span><?= htmlspecialchars($other_name, ENT_QUOTES, 'UTF-8') ?> is typing...</span>
    </p>

    <!-- Composer -->
    <form id="send-form" class="flex items-center gap-2 border-t border-stone-200 bg-white px-3 py-2.5 lg:rounded-b-2xl lg:border lg:border-t-stone-200">
        <label class="shrink-0 cursor-pointer text-stone-400 hover:text-accent" title="Send a photo">
            <?php icon('camera', 'w-6 h-6'); ?>
            <input type="file" id="chat-image-input" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden">
        </label>
        <input type="text" id="body-input" placeholder="Type a message..." class="h-10 flex-1 rounded-full border border-stone-200 bg-cream px-4 text-sm focus:border-coffee focus:outline-none">
        <button type="submit" aria-label="Send" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-accent text-white active:opacity-80 hover:opacity-90">
            <?php icon('paper-airplane', 'w-4 h-4'); ?>
        </button>
    </form>
    <p id="chat-status" class="bg-white px-4 pb-1 text-[11px] text-stone-400"></p>
</div>
</div><!-- /two-pane grid -->

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

    const typingIndicator = document.getElementById('typing-indicator');

    function renderBubble(m) {
        if (m.message_type === 'system') {
            const p = document.createElement('p');
            p.className = 'text-center text-[11px] text-stone-400 italic py-0.5';
            p.textContent = m.body || '';
            return p;
        }
        const isMine = m.sender_user_id == myUserId;
        const wrap = document.createElement('div');
        wrap.className = 'flex flex-col ' + (isMine ? 'items-end' : 'items-start');
        wrap.dataset.messageId = m.message_id;
        wrap.dataset.mine = isMine ? '1' : '0';
        const row = document.createElement('div');
        row.className = 'flex items-end gap-2 ' + (isMine ? 'justify-end' : 'justify-start');
        const bubble = document.createElement('div');
        bubble.className = 'max-w-[75%] rounded-2xl px-3 py-2 text-sm ' +
            (isMine ? 'bg-coffee text-white rounded-br-md' : 'bg-white border border-stone-200 text-stone-800 rounded-bl-md');
        if (m.message_type === 'order_card') {
            const a = document.createElement('a');
            a.className = 'underline text-xs font-semibold';
            a.href = '/orders/view.php?id=' + m.order_id;
            a.textContent = 'New order card — view / respond';
            bubble.appendChild(a);
        } else if (m.message_type === 'image' && m.image_url) {
            const img = document.createElement('img');
            img.src = m.image_url;
            img.loading = 'lazy';
            img.decoding = 'async';
            img.className = 'rounded-xl max-w-full max-h-64 object-cover';
            bubble.appendChild(img);
        } else {
            bubble.textContent = m.body || '';
        }
        row.appendChild(bubble);
        wrap.appendChild(row);
        return wrap;
    }

    // Shows "Seen" under the single latest message I sent that the other
    // party has read up to — not on every past message, Messenger-style.
    function updateReadReceipt(otherReadId) {
        list.querySelectorAll('.seen-label').forEach(function (el) { el.remove(); });
        if (!otherReadId) return;
        const mine = Array.from(list.querySelectorAll('[data-mine="1"]'))
            .filter(function (el) { return parseInt(el.dataset.messageId, 10) <= otherReadId; });
        const last = mine[mine.length - 1];
        if (!last) return;
        const seen = document.createElement('span');
        seen.className = 'seen-label mt-0.5 mr-1 text-[10px] text-stone-400';
        seen.textContent = 'Seen';
        last.appendChild(seen);
    }

    async function poll() {
        // Under Turbo there is no page unload, so navigating away would
        // leak this interval forever — self-clear once our DOM is gone.
        if (!document.body.contains(list)) { clearInterval(pollTimer); return; }
        if (document.hidden) return;
        const res = await fetch('/chat/poll_messages.php?conversation_id=' + conversationId + '&after_id=' + afterId + '&my_read_id=' + afterId);
        const data = await res.json();
        const rows = data.messages || [];
        rows.forEach(function (m) {
            list.appendChild(renderBubble(m));
            afterId = Math.max(afterId, m.message_id);
        });
        if (rows.length) list.scrollTop = list.scrollHeight;
        typingIndicator.classList.toggle('hidden', !data.other_typing);
        typingIndicator.classList.toggle('flex', !!data.other_typing);
        updateReadReceipt(data.other_read_id || 0);
    }

    let lastTypingPing = 0;
    function pingTyping() {
        const now = Date.now();
        if (now - lastTypingPing < 1500) return;
        lastTypingPing = now;
        fetch('/chat/typing.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'conversation_id=' + conversationId
        });
    }

    async function sendText(body) {
        if (!body) return;
        await fetch('/chat/send_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'conversation_id=' + conversationId + '&body=' + encodeURIComponent(body)
        });
        poll();
    }

    document.getElementById('body-input').addEventListener('input', pingTyping);

    document.getElementById('send-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const input = document.getElementById('body-input');
        const body = input.value.trim();
        input.value = '';
        sendText(body);
    });

    document.querySelectorAll('.quick-reply').forEach(function (btn) {
        btn.addEventListener('click', function () { sendText(btn.dataset.text); });
    });

    document.getElementById('chat-image-input').addEventListener('change', async function (e) {
        const rawFile = e.target.files[0];
        if (!rawFile) return;
        status.textContent = 'Optimizing & uploading photo...';

        const file = window.higoffeOptimizeImage
            ? await window.higoffeOptimizeImage(rawFile)
            : rawFile;

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

    list.scrollTop = list.scrollHeight;
    poll(); // immediate sync — marks read + shows typing/seen without waiting out the interval
    const pollTimer = setInterval(poll, 3000);
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
