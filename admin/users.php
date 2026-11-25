<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$pdo = (new Database())->connect();
$userModel = new User($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int) ($_POST['user_id'] ?? 0);
    $is_suspended = ($_POST['action'] ?? '') === 'suspend';
    $userModel->setSuspended($user_id, $is_suspended);
}

$users = $userModel->getAll();

require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-bold mb-4">Users</h1>

<div class="bg-white border border-stone-200 rounded-lg divide-y divide-stone-200">
    <?php foreach ($users as $u): ?>
        <div class="flex items-center justify-between p-4">
            <div>
                <p class="font-semibold"><?= htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8') ?> <span class="text-xs text-stone-400">(<?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?>)</span></p>
                <p class="text-xs text-stone-500"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <form method="post">
                <input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">
                <input type="hidden" name="action" value="<?= $u['is_suspended'] ? 'unsuspend' : 'suspend' ?>">
                <button type="submit" class="text-sm <?= $u['is_suspended'] ? 'text-green-600' : 'text-red-600' ?> underline">
                    <?= $u['is_suspended'] ? 'Unsuspend' : 'Suspend' ?>
                </button>
            </form>
        </div>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
