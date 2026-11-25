<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Uploads.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/image_upload_field.php';

// Anyone can post now — a seller posting shares it as their café
// (cafe_id set); a customer post is personal (cafe_id NULL). See
// migrations/004_social_first_pivot.sql.
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

require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-lg mx-auto bg-white border border-stone-200 rounded-xl p-6">
    <h1 class="text-xl font-bold mb-4">New post</h1>

    <?php if ($error !== ''): ?>
        <p class="bg-red-50 text-red-700 text-sm rounded p-3 mb-4"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-3">
        <div>
            <label class="block text-sm text-stone-600 mb-1">What's on your mind?</label>
            <textarea name="caption" rows="4" placeholder="Share a drink, a café moment, a recommendation..." class="w-full border border-stone-300 rounded p-2"></textarea>
        </div>
        <?php render_image_upload_field($upload_auth, 'image_url', 'Photo (optional)', ''); ?>
        <?php if ($cafe !== null): ?>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="post_as_cafe" <?= $post_as_cafe ? 'checked' : '' ?>>
                Post as <?= htmlspecialchars($cafe['cafe_name'], ENT_QUOTES, 'UTF-8') ?> (café announcement/promo) instead of yourself
            </label>
        <?php endif; ?>
        <button type="submit" class="w-full bg-amber-700 text-white rounded p-2 font-semibold">Post</button>
    </form>
</div>
<script src="/includes/image_upload.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
