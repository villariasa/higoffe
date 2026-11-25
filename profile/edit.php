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

require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-md mx-auto bg-white border border-stone-200 rounded-xl p-6">
    <h1 class="text-xl font-bold mb-4">Edit profile</h1>

    <?php if ($saved): ?>
        <p class="bg-green-50 text-green-700 text-sm rounded p-3 mb-4">Saved.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="bg-red-50 text-red-700 text-sm rounded p-3 mb-4"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-3">
        <?php render_image_upload_field($upload_auth, 'avatar_url', 'Profile photo', $user['avatar_url'] ?? ''); ?>
        <div>
            <label class="block text-sm text-stone-600 mb-1">Bio</label>
            <textarea name="bio" rows="3" maxlength="280" class="w-full border border-stone-300 rounded p-2"><?= htmlspecialchars($user['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <button type="submit" class="w-full bg-amber-700 text-white rounded p-2 font-semibold">Save</button>
    </form>

    <p class="text-sm mt-4"><a href="/profile/view.php" class="text-amber-700 underline">View your profile</a></p>
</div>
<script src="/includes/image_upload.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
