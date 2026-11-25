<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = (string) ($_GET['token'] ?? '');

$pdo = (new Database())->connect();
$userModel = new User($pdo);
$user = $token !== '' ? $userModel->getByVerificationToken($token) : null;
$expired = $user && strtotime($user['verification_token_expires_at']) < time();

if ($user && !$expired) {
    $userModel->markVerified((int) $user['user_id']);
    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
}

$no_shell = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex min-h-screen flex-col items-center justify-center px-6 text-center">
    <?php if ($user && !$expired): ?>
        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-green-600"><?php icon('check-circle', 'w-9 h-9'); ?></span>
        <h1 class="mt-3 text-xl font-black text-stone-900">Email verified</h1>
        <p class="mt-1 text-sm text-stone-500">Your account is confirmed. Welcome to Higoffe!</p>
        <a href="/index.php" class="mt-5 w-full rounded-xl bg-accent py-3 text-sm font-bold text-white">Continue</a>
    <?php elseif ($user && $expired): ?>
        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600"><?php icon('clock', 'w-9 h-9'); ?></span>
        <h1 class="mt-3 text-xl font-black text-stone-900">Link expired</h1>
        <p class="mt-1 text-sm text-stone-500">That verification link is more than 24 hours old.</p>
        <a href="/auth/resend_verification.php?email=<?= urlencode($user['email']) ?>" class="mt-5 w-full rounded-xl bg-accent py-3 text-sm font-bold text-white">Send a new link</a>
    <?php else: ?>
        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-red-500"><?php icon('x-circle', 'w-9 h-9'); ?></span>
        <h1 class="mt-3 text-xl font-black text-stone-900">Invalid link</h1>
        <p class="mt-1 text-sm text-stone-500">This verification link isn't valid.</p>
        <a href="/auth/login.php" class="mt-5 w-full rounded-xl bg-accent py-3 text-sm font-bold text-white">Back to login</a>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
