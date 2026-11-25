<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('seller');

$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);
$cafe = $cafeModel->getByOwner((int) $_SESSION['user_id']);

if (!$cafe) {
    header("Location: /cafes/profile.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption   = (string) trim($_POST['caption'] ?? '');
    $image_url = (string) trim($_POST['image_url'] ?? '');

    if ($caption === '' && $image_url === '') {
        $error = 'Write something or add an image.';
    } else {
        $postModel = new Post($pdo);
        $postModel->create((int) $cafe['cafe_id'], $caption, $image_url);
        header("Location: /feed/index.php");
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-lg mx-auto bg-white border border-stone-200 rounded-lg p-6">
    <h1 class="text-xl font-bold mb-4">New post</h1>

    <?php if ($error !== ''): ?>
        <p class="bg-red-50 text-red-700 text-sm rounded p-3 mb-4"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-3">
        <div>
            <label class="block text-sm text-stone-600 mb-1">Caption</label>
            <textarea name="caption" rows="4" class="w-full border border-stone-300 rounded p-2"></textarea>
        </div>
        <div>
            <label class="block text-sm text-stone-600 mb-1">Image URL (optional)</label>
            <input type="text" name="image_url" class="w-full border border-stone-300 rounded p-2">
        </div>
        <button type="submit" class="w-full bg-amber-700 text-white rounded p-2 font-semibold">Post</button>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
