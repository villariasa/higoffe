<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';
require_role('admin');

$pdo = (new Database())->connect();
$userModel = new User($pdo);
$admin_id = (int) $_SESSION['user_id'];

$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int) ($_POST['user_id'] ?? 0);
    $action  = $_POST['action'] ?? '';

    if ($action === 'suspend') {
        $userModel->setSuspended($user_id, true);
        $flash = 'User suspended.';
    } elseif ($action === 'unsuspend') {
        $userModel->setSuspended($user_id, false);
        $flash = 'User unsuspended.';
    } elseif ($action === 'delete') {
        $ok = $userModel->delete($user_id, $admin_id);
        $flash = $ok ? 'User deleted.' : 'Could not delete that user (admins are protected).';
    }
    header('Location: /admin/users.php');
    exit;
}

$users = $userModel->getAll();

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($flash !== ''): ?>
    <script>document.addEventListener('DOMContentLoaded',function(){window.higoffeToast(<?= json_encode($flash, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>,'success');});</script>
<?php endif; ?>
<h1 class="text-xl font-black text-stone-900">Users</h1>
<p class="mt-0.5 text-sm text-stone-500"><?= count($users) ?> registered accounts.</p>

<div class="mt-5 overflow-hidden rounded-2xl border border-stone-200 bg-white">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-stone-200 bg-cream text-[11px] font-bold uppercase tracking-wide text-stone-400">
            <tr>
                <th class="px-4 py-3">User</th>
                <th class="hidden px-4 py-3 md:table-cell">Email</th>
                <th class="px-4 py-3">Role</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-100">
            <?php foreach ($users as $u): ?>
                <tr class="hover:bg-cream/60">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <?php render_avatar($u['avatar_url'] ?? null, $u['full_name'], 'w-8 h-8'); ?>
                            <span class="font-bold text-stone-900"><?= htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </td>
                    <td class="hidden px-4 py-3 text-stone-500 md:table-cell"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3"><span class="rounded-full bg-stone-100 px-2 py-0.5 text-[10px] font-bold uppercase text-stone-500"><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td class="px-4 py-3">
                        <?php if ($u['is_suspended']): ?>
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold uppercase text-red-600">Suspended</span>
                        <?php else: ?>
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-bold uppercase text-green-700">Active</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <!-- Suspend / Unsuspend -->
                            <?php if ($u['role'] !== 'admin'): ?>
                                <form method="post" class="inline" data-confirm="<?= $u['is_suspended'] ? 'Unsuspend' : 'Suspend' ?> this account?">
                                    <input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">
                                    <input type="hidden" name="action" value="<?= $u['is_suspended'] ? 'unsuspend' : 'suspend' ?>">
                                    <button type="submit" class="rounded-full border px-3 py-1 text-[11px] font-bold <?= $u['is_suspended'] ? 'border-green-300 text-green-700 hover:bg-green-50' : 'border-amber-300 text-amber-700 hover:bg-amber-50' ?>">
                                        <?= $u['is_suspended'] ? 'Unsuspend' : 'Suspend' ?>
                                    </button>
                                </form>
                                <!-- Delete (permanent) -->
                                <form method="post" class="inline" data-confirm="Permanently DELETE <?= htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8') ?>? This cannot be undone.">
                                    <input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="rounded-full border border-red-300 px-3 py-1 text-[11px] font-bold text-red-600 hover:bg-red-50">
                                        Delete
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-[11px] text-stone-400">Admin — protected</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
