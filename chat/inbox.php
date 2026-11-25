<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';

// Both roles land here now: customers see their café chats, sellers see
// their active conversations (claiming still happens in the queue).
$pdo = (new Database())->connect();
$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role !== 'customer' && $role !== 'seller') {
    header("Location: /index.php");
    exit;
}

$page_title = 'Messages';
$back_url = '/feed/index.php';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Mobile: full-width list. Desktop: two-pane with a placeholder pane. -->
<div class="lg:grid lg:h-[calc(100vh-7rem)] lg:grid-cols-[360px_minmax(0,1fr)] lg:gap-0 lg:px-8 lg:pt-2">
    <div class="lg:overflow-y-auto lg:rounded-l-2xl lg:border lg:border-stone-200 lg:bg-white">
        <?php include __DIR__ . '/_conversation_list.php'; ?>
    </div>
    <div class="hidden lg:flex lg:items-center lg:justify-center lg:rounded-r-2xl lg:border lg:border-l-0 lg:border-stone-200 lg:bg-cream">
        <div class="text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white text-stone-400"><?php icon('chat-bubble', 'w-7 h-7'); ?></span>
            <p class="mt-3 text-sm font-semibold text-stone-500">Select a conversation</p>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
