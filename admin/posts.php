<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';
require_role('admin');

$pdo = (new Database())->connect();
$postModel = new Post($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = (int) ($_POST['post_id'] ?? 0);
    $post = $postModel->getById($post_id);
    if ($post) {
        $postModel->delete($post_id);
        // Tell the poster their content was removed by moderation.
        $snippet = mb_substr(trim((string) ($post['caption'] ?? '')), 0, 60);
        $notificationModel = new Notification($pdo);
        $notificationModel->create(
            (int) $post['author_user_id'],
            'An admin removed your post'
                . ($snippet !== '' ? ' ("' . $snippet . '…")' : '')
                . ' for violating community guidelines.'
        );
    }
}

$posts = $postModel->getFeed((int) $_SESSION['user_id']);

require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-black text-stone-900">Moderate posts</h1>
<p class="mt-0.5 text-sm text-stone-500">Deleting a post notifies its author.</p>

<div class="mt-5 grid gap-3 lg:grid-cols-2 xl:grid-cols-3">
    <?php foreach ($posts as $post): ?>
        <div class="flex flex-col rounded-2xl border border-stone-200 bg-white p-4">
            <div class="flex items-center gap-2.5">
                <?php render_avatar($post['cafe_id'] ? ($post['logo_url'] ?? null) : ($post['author_avatar_url'] ?? null), $post['cafe_name'] ?? $post['author_name'], 'w-8 h-8'); ?>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[13px] font-bold text-stone-900"><?= htmlspecialchars($post['cafe_name'] ?? $post['author_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-[10px] text-stone-400"><?= htmlspecialchars(time_ago($post['created_at']), ENT_QUOTES, 'UTF-8') ?><?= empty($post['cafe_name']) ? ' · personal post' : ' · café post' ?></p>
                </div>
            </div>
            <p class="mt-2 line-clamp-1 flex-1 whitespace-pre-wrap text-sm text-stone-700"><?= htmlspecialchars($post['caption'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($post['image_url'])): ?>
                <img src="<?= htmlspecialchars($post['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="mt-2 h-28 w-full rounded-xl object-cover">
            <?php endif; ?>
            <form method="post" class="mt-3" data-confirm="Delete this post and notify the author?">
                <input type="hidden" name="post_id" value="<?= (int) $post['post_id'] ?>">
                <button type="submit" class="flex items-center gap-1.5 rounded-full border border-red-300 px-3 py-1 text-[11px] font-bold text-red-600 hover:bg-red-50">
                    <?php icon('trash', 'w-3.5 h-3.5'); ?> Delete
                </button>
            </form>
        </div>
    <?php endforeach; ?>
    <?php if (empty($posts)): ?>
        <p class="text-sm text-stone-400">No posts.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
