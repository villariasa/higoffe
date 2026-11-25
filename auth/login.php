<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/password_field.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$submitted_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = (string) trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $submitted_email = $email;

    $pdo = (new Database())->connect();
    $userModel = new User($pdo);
    $user = $userModel->getByEmail($email);

    if ($user && $user['is_suspended']) {
        $error = 'This account has been suspended.';
    } elseif (!$user || !password_verify($password, $user['password_hash']) || !$user['email_verified']) {
        // Deliberately identical wording whether the account doesn't
        // exist, the password is wrong, or the account just isn't
        // verified yet — distinguishing these leaks which emails are
        // registered. The resend-verification link below is always
        // shown too, so its presence doesn't leak anything either.
        $error = 'Incorrect email/password, or your account is not verified yet.';
    } else {
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        header("Location: /index.php");
        exit;
    }
}

$no_shell = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen flex-col justify-center px-6 py-10">
    <div class="mb-8 text-center">
        <p class="text-3xl font-black text-coffee">Higoffe<span class="text-accent">.</span></p>
        <p class="mt-1 text-sm text-stone-500">Kape ta, storya ta.</p>
    </div>

    <?php if ($error !== ''): ?>
        <p class="mb-4 rounded-xl bg-red-50 p-3 text-sm text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-3">
        <div>
            <label class="mb-1 block text-xs font-semibold text-stone-500">Email</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($submitted_email, ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-stone-200 bg-white p-2.5 text-sm focus:border-coffee focus:outline-none">
        </div>
        <?php render_password_field('password', 'Password'); ?>
        <button type="submit" class="w-full rounded-xl bg-accent py-3 text-sm font-bold text-white active:opacity-80">Log in</button>
    </form>

    <div class="mt-6 space-y-1.5 text-center text-[13px] text-stone-500">
        <p>Trouble logging in? <a href="/auth/resend_verification.php" class="font-semibold text-accent">Resend verification email</a></p>
        <p>New here? <a href="/auth/register_customer.php" class="font-semibold text-accent">Create an account</a>
           or <a href="/auth/register_seller.php" class="font-semibold text-accent">register your café</a></p>
    </div>
</div>
<script src="/includes/password_toggle.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
