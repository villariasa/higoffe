<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$pdo = (new Database())->connect();
$postModel = new Post($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = (int) ($_POST['post_id'] ?? 0);
    $postModel->delete($post_id);
}

$posts = $postModel->getFeed((int) $_SESSION['user_id']);

require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-bold mb-4">Moderate posts</h1>

<div class="space-y-3 max-w-xl">
    <?php foreach ($posts as $post): ?>
        <div class="bg-white border border-stone-200 rounded-lg p-4">
            <p class="font-semibold text-sm">
                <?= htmlspecialchars($post['cafe_name'] ?? $post['author_name'], ENT_QUOTES, 'UTF-8') ?>
                <?php if (empty($post['cafe_name'])): ?><span class="text-xs text-stone-400 font-normal">(personal post)</span><?php endif; ?>
            </p>
            <p class="whitespace-pre-wrap text-sm"><?= htmlspecialchars($post['caption'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <form method="post" class="mt-2" onsubmit="return confirm('Delete this post?');">
                <input type="hidden" name="post_id" value="<?= (int) $post['post_id'] ?>">
                <button type="submit" class="text-red-600 underline text-sm">Delete</button>
            </form>
        </div>
    <?php endforeach; ?>
    <?php if (empty($posts)): ?>
        <p class="text-stone-500 text-sm">No posts.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
