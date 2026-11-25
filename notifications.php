<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Notification.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/components.php';

$pdo = (new Database())->connect();
$notificationModel = new Notification($pdo);
$notifications = $notificationModel->getForUser((int) $_SESSION['user_id']);
// Viewing the page clears the badge — items keep their unread styling
// for this one render so the user can still see what's new.
$notificationModel->markAllRead((int) $_SESSION['user_id']);

$page_title = 'Notifications';
$back_url = '/index.php';
require_once __DIR__ . '/includes/header.php';
?>

<section class="px-4 py-4 lg:mx-auto lg:max-w-2xl">
    <?php if (empty($notifications)): ?>
        <?php render_empty_state('bell', 'No notifications yet', "You'll see updates about your posts and account here."); ?>
    <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($notifications as $n): ?>
                <div class="flex items-start gap-3 rounded-2xl bg-white p-3.5 <?= $n['is_read'] ? 'opacity-70' : 'border border-accent/30' ?>">
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-stone-100 text-stone-500">
                        <?php icon('bell', 'w-4 h-4'); ?>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-stone-800"><?= htmlspecialchars($n['body'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mt-0.5 text-[11px] text-stone-400"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($n['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <?php if (!$n['is_read']): ?>
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-accent"></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
