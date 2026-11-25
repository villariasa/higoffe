<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

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
            $user_id = $userModel->create('seller', $full_name, $email, $password, $phone);
            $_SESSION['user_id']   = $user_id;
            $_SESSION['role']      = 'seller';
            $_SESSION['full_name'] = $full_name;
            // Next step after this redirect is cafes/profile.php, where the
            // seller sets up their café — handled by that module, not here.
            header("Location: /cafes/profile.php");
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-md mx-auto bg-white border border-stone-200 rounded-lg p-6">
    <h1 class="text-xl font-bold mb-4">Register your café</h1>

    <?php if ($error !== ''): ?>
        <p class="bg-red-50 text-red-700 text-sm rounded p-3 mb-4"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-3">
        <div>
            <label class="block text-sm text-stone-600 mb-1">Your full name</label>
            <input type="text" name="full_name" required class="w-full border border-stone-300 rounded p-2">
        </div>
        <div>
            <label class="block text-sm text-stone-600 mb-1">Email</label>
            <input type="email" name="email" required class="w-full border border-stone-300 rounded p-2">
        </div>
        <div>
            <label class="block text-sm text-stone-600 mb-1">Phone (optional)</label>
            <input type="text" name="phone" class="w-full border border-stone-300 rounded p-2">
        </div>
        <div>
            <label class="block text-sm text-stone-600 mb-1">Password</label>
            <input type="password" name="password" required class="w-full border border-stone-300 rounded p-2">
        </div>
        <button type="submit" class="w-full bg-amber-700 text-white rounded p-2 font-semibold">Register</button>
    </form>

    <p class="text-sm text-stone-500 mt-4">
        Already have an account? <a href="/auth/login.php" class="text-amber-700 underline">Log in</a>.
        Looking to order from a café instead? <a href="/auth/register_customer.php" class="text-amber-700 underline">Customer sign up</a>.
    </p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
