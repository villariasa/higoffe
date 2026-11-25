<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Uploads.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/image_upload_field.php';

// Anyone can post — a seller can share as their café (cafe_id set); a
// customer post is personal (cafe_id NULL).
$pdo = (new Database())->connect();
$cafe = null;
if ($_SESSION['role'] === 'seller') {
    $cafeModel = new Cafe($pdo);
    $cafe = $cafeModel->getByOwner((int) $_SESSION['user_id']);
}

$error = '';
$post_as_cafe = $cafe !== null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption   = (string) trim($_POST['caption'] ?? '');
    $image_url = (string) trim($_POST['image_url'] ?? '');
    $post_as_cafe = $cafe !== null && isset($_POST['post_as_cafe']);

    if ($caption === '' && $image_url === '') {
        $error = 'Write something or add a photo.';
    } elseif (!Uploads::isAllowedMediaUrl($image_url)) {
        $error = 'Invalid image URL.';
    } else {
        $postModel = new Post($pdo);
        $postModel->create(
            (int) $_SESSION['user_id'],
            $post_as_cafe ? (int) $cafe['cafe_id'] : null,
            $caption,
            $image_url
        );
        header("Location: /feed/index.php");
        exit;
    }
}

$upload_auth = Uploads::issueToken((int) $_SESSION['user_id'], 'post');

$page_title = 'New post';
$back_url = '/feed/index.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="px-4 py-4 lg:mx-auto lg:max-w-xl lg:px-0">
    <?php if ($error !== ''): ?>
        <p class="mb-3 rounded-xl bg-red-50 p-3 text-sm text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-4">
        <textarea name="caption" rows="4" placeholder="Share a drink, a café moment, a recommendation..."
                  class="w-full rounded-2xl border border-stone-200 bg-white p-3.5 text-sm focus:border-coffee focus:outline-none"></textarea>

        <div class="rounded-2xl bg-white p-3.5">
            <?php render_image_upload_field($upload_auth, 'image_url', 'Photo (optional)', ''); ?>
        </div>

        <?php if ($cafe !== null): ?>
            <label class="flex items-center gap-2.5 rounded-2xl bg-white p-3.5 text-[13px] text-stone-700">
                <input type="checkbox" name="post_as_cafe" <?= $post_as_cafe ? 'checked' : '' ?> class="accent-accent">
                Post as <span class="font-bold"><?= htmlspecialchars($cafe['cafe_name'], ENT_QUOTES, 'UTF-8') ?></span> (promo/announcement)
            </label>
        <?php endif; ?>

        <button type="submit" class="w-full rounded-xl bg-accent py-3 text-sm font-bold text-white active:opacity-80">Post</button>
    </form>
</div>
<script src="/includes/image_upload.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
