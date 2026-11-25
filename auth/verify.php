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

require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-md mx-auto bg-white border border-stone-200 rounded-lg p-6 text-center">
    <?php if ($user && !$expired): ?>
        <h1 class="text-xl font-bold mb-2">Email verified</h1>
        <p class="text-stone-600 mb-4">Your account is confirmed.</p>
        <a href="/index.php" class="inline-block bg-amber-700 text-white rounded px-4 py-2 font-semibold">Continue</a>
    <?php elseif ($user && $expired): ?>
        <h1 class="text-xl font-bold mb-2">Link expired</h1>
        <p class="text-stone-600 mb-4">That verification link is more than 24 hours old.</p>
        <a href="/auth/resend_verification.php?email=<?= urlencode($user['email']) ?>" class="inline-block bg-amber-700 text-white rounded px-4 py-2 font-semibold">Send a new link</a>
    <?php else: ?>
        <h1 class="text-xl font-bold mb-2">Invalid link</h1>
        <p class="text-stone-600 mb-4">This verification link isn't valid.</p>
        <a href="/auth/login.php" class="inline-block bg-amber-700 text-white rounded px-4 py-2 font-semibold">Back to login</a>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
