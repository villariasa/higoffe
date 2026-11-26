<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Uploads.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/image_upload_field.php';

$pdo = (new Database())->connect();
$userModel = new User($pdo);
$user = $userModel->getById((int) $_SESSION['user_id']);

$saved = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio        = (string) trim($_POST['bio'] ?? '');
    $avatar_url = (string) trim($_POST['avatar_url'] ?? '');
    if (!Uploads::isAllowedMediaUrl($avatar_url)) {
        $error = 'Invalid image URL.';
    } else {
        $userModel->updateProfile((int) $_SESSION['user_id'], $bio, $avatar_url);
        $user = $userModel->getById((int) $_SESSION['user_id']);
        $saved = true;
    }
}

$upload_auth = Uploads::issueToken((int) $_SESSION['user_id'], 'avatar');

$page_title = 'Edit profile';
$back_url = '/profile/view.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="px-4 py-4 lg:mx-auto lg:max-w-xl lg:px-0">
    <?php if ($saved): ?>
        <script>document.addEventListener('DOMContentLoaded', function () { window.higoffeToast('Saved.', 'success'); });</script>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <script>document.addEventListener('DOMContentLoaded', function () { window.higoffeToast(<?= json_encode($error, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, 'error'); });</script>
    <?php endif; ?>

    <form method="post" class="space-y-4">
        <div class="rounded-2xl bg-white p-3.5">
            <?php render_image_upload_field($upload_auth, 'avatar_url', 'Profile photo', $user['avatar_url'] ?? '', 2); ?>
        </div>
        <div class="rounded-2xl bg-white p-3.5">
            <label class="mb-1 block text-xs font-semibold text-stone-500">Bio</label>
            <textarea name="bio" rows="3" maxlength="280" class="w-full rounded-xl border border-stone-200 bg-cream p-3 text-sm focus:border-coffee focus:outline-none"><?= htmlspecialchars($user['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <button type="submit" class="w-full rounded-xl bg-accent py-3 text-sm font-bold text-white active:opacity-80">Save</button>
    </form>

    <div class="mt-4 rounded-2xl bg-white p-3.5">
        <p class="text-xs font-semibold text-stone-500">Tutorials</p>
        <p class="mt-0.5 text-[11px] text-stone-400">Replay the guided walkthrough of the app's main features.</p>
        <button type="button" id="btn-restart-tour" class="mt-2.5 rounded-xl border border-stone-300 px-3.5 py-2 text-xs font-bold text-stone-600 hover:border-coffee">Restart tutorial</button>
    </div>
</div>
<script src="/includes/image_upload.js"></script>
<script>
document.getElementById('btn-restart-tour')?.addEventListener('click', function () {
    if (window.HigoffeTour) window.HigoffeTour.reset('all');
    window.higoffeToast('Tutorial reset — it will start again on your next page.', 'success');
    const home = <?= json_encode($_SESSION['role'] === 'seller' ? '/queue/index.php' : '/feed/index.php') ?>;
    setTimeout(function () {
        if (window.Turbo) { window.Turbo.visit(home); } else { window.location.href = home; }
    }, 900);
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
