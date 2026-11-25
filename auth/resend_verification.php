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

$no_shell = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex min-h-screen flex-col justify-center px-6">
    <h1 class="text-center text-xl font-black text-stone-900">Resend verification email</h1>

    <?php if ($submitted): ?>
        <p class="mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-700">
            If that email is registered and not yet verified, a new link is on its way.
        </p>
        <a href="/auth/login.php" class="mt-4 block w-full rounded-xl bg-accent py-3 text-center text-sm font-bold text-white">Back to login</a>
    <?php else: ?>
        <form method="post" class="mt-5 space-y-3">
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Email</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($prefill_email, ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-stone-200 bg-white p-2.5 text-sm focus:border-coffee focus:outline-none">
            </div>
            <button type="submit" class="w-full rounded-xl bg-accent py-3 text-sm font-bold text-white active:opacity-80">Send link</button>
        </form>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
