<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/mail_helpers.php';

$prefill_email = (string) ($_GET['email'] ?? '');
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = (string) trim($_POST['email'] ?? '');
    $prefill_email = $email;

    if ($email !== '') {
        $pdo = (new Database())->connect();
        $userModel = new User($pdo);
        $user = $userModel->getByEmail($email);

        // Only actually send if the account exists and isn't already
        // verified — but the page shows the same message either way, so
        // this endpoint never reveals which emails are registered.
        if ($user && !$user['email_verified']) {
            send_verification_email($pdo, $userModel, (int) $user['user_id'], $user['full_name'], $email);
        }
    }
    $submitted = true;
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-md mx-auto bg-white border border-stone-200 rounded-lg p-6">
    <h1 class="text-xl font-bold mb-4">Resend verification email</h1>

    <?php if ($submitted): ?>
        <p class="bg-green-50 text-green-700 text-sm rounded p-3 mb-4">
            If that email is registered and not yet verified, a new link is on its way.
        </p>
        <p class="text-sm"><a href="/auth/login.php" class="text-amber-700 underline">Back to login</a></p>
    <?php else: ?>
        <form method="post" class="space-y-3">
            <div>
                <label class="block text-sm text-stone-600 mb-1">Email</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($prefill_email, ENT_QUOTES, 'UTF-8') ?>" class="w-full border border-stone-300 rounded p-2">
            </div>
            <button type="submit" class="w-full bg-amber-700 text-white rounded p-2 font-semibold">Send link</button>
        </form>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
