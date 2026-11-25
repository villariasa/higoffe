<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';

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

require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-md mx-auto bg-white border border-stone-200 rounded-lg p-6">
    <h1 class="text-xl font-bold mb-4">Log in</h1>

    <?php if ($error !== ''): ?>
        <p class="bg-red-50 text-red-700 text-sm rounded p-3 mb-4"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-3">
        <div>
            <label class="block text-sm text-stone-600 mb-1">Email</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($submitted_email, ENT_QUOTES, 'UTF-8') ?>" class="w-full border border-stone-300 rounded p-2">
        </div>
        <div>
            <label class="block text-sm text-stone-600 mb-1">Password</label>
            <input type="password" name="password" required class="w-full border border-stone-300 rounded p-2">
        </div>
        <button type="submit" class="w-full bg-amber-700 text-white rounded p-2 font-semibold">Log in</button>
    </form>

    <p class="text-sm text-stone-500 mt-4">
        Trouble logging in? <a href="/auth/resend_verification.php" class="text-amber-700 underline">Resend verification email</a>.<br>
        New here? <a href="/auth/register_customer.php" class="text-amber-700 underline">Create a customer account</a>
        or <a href="/auth/register_seller.php" class="text-amber-700 underline">register your café</a>.
    </p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
