<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/mail_helpers.php';
require_once __DIR__ . '/../includes/password_field.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$registered = false;
$registered_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = (string) trim($_POST['full_name'] ?? '');
    $email     = (string) trim($_POST['email'] ?? '');
    $password  = (string) ($_POST['password'] ?? '');
    $phone     = (string) trim($_POST['phone'] ?? '');

    if ($full_name === '' || $email === '' || $password === '') {
        $error = 'Please fill in all required fields.';
    } else {
        $pdo = (new Database())->connect();
        $userModel = new User($pdo);

        if ($userModel->getByEmail($email)) {
            $error = 'That email is already registered.';
        } else {
            $user_id = (int) $userModel->create('customer', $full_name, $email, $password, $phone);
            $sent = send_verification_email($pdo, $userModel, $user_id, $full_name, $email);
            $registered = true;
            $registered_email = $email;
            if (!$sent) {
                $error = "We couldn't send the verification email right now — you can request a new one from the login page.";
            }
        }
    }
}

$no_shell = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen flex-col justify-center px-6 py-10">
    <div class="mb-6 text-center">
        <p class="text-2xl font-black text-coffee">Create your account</p>
        <p class="mt-1 text-sm text-stone-500">Discover cafés, share moments, order through chat.</p>
    </div>

    <?php if ($registered): ?>
        <p class="rounded-xl bg-green-50 p-3 text-sm text-green-700">
            Account created — check <b><?= htmlspecialchars($registered_email, ENT_QUOTES, 'UTF-8') ?></b> for a verification link before logging in.
        </p>
        <?php if ($error !== ''): ?>
            <p class="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <a href="/auth/login.php" class="mt-4 block w-full rounded-xl bg-accent py-3 text-center text-sm font-bold text-white">Go to login</a>
    <?php else: ?>
        <?php if ($error !== ''): ?>
            <p class="mb-4 rounded-xl bg-red-50 p-3 text-sm text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="post" class="space-y-3">
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Full name</label>
                <input type="text" name="full_name" required class="w-full rounded-xl border border-stone-200 bg-white p-2.5 text-sm focus:border-coffee focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Email</label>
                <input type="email" name="email" required class="w-full rounded-xl border border-stone-200 bg-white p-2.5 text-sm focus:border-coffee focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Phone (optional)</label>
                <input type="text" name="phone" class="w-full rounded-xl border border-stone-200 bg-white p-2.5 text-sm focus:border-coffee focus:outline-none">
            </div>
            <?php render_password_field('password', 'Password'); ?>
            <button type="submit" class="w-full rounded-xl bg-accent py-3 text-sm font-bold text-white active:opacity-80">Register</button>
        </form>

        <div class="mt-6 space-y-1.5 text-center text-[13px] text-stone-500">
            <p>Already have an account? <a href="/auth/login.php" class="font-semibold text-accent">Log in</a></p>
            <p>Café owner? <a href="/auth/register_seller.php" class="font-semibold text-accent">Register your café</a></p>
        </div>
    <?php endif; ?>
</div>
<script src="/includes/password_toggle.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
