<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = (new Database())->connect();
$postModel = new Post($pdo);
$posts = $postModel->getFeed((int) $_SESSION['user_id']);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold">Feed</h1>
    <?php if ($_SESSION['role'] === 'seller'): ?>
        <a href="/feed/create_post.php" class="bg-amber-700 text-white rounded px-4 py-2 text-sm font-semibold">+ New post</a>
    <?php endif; ?>
</div>

<div class="space-y-4 max-w-xl">
    <?php foreach ($posts as $post): ?>
        <div class="bg-white border border-stone-200 rounded-lg p-4">
            <div class="flex items-center gap-2 mb-2">
                <?php if (!empty($post['logo_url'])): ?>
                    <img src="<?= htmlspecialchars($post['logo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-8 h-8 rounded-full object-cover">
                <?php endif; ?>
                <a href="/cafes/view.php?id=<?= (int) $post['cafe_id'] ?>" class="font-semibold hover:text-amber-700"><?= htmlspecialchars($post['cafe_name'], ENT_QUOTES, 'UTF-8') ?></a>
            </div>
            <p class="whitespace-pre-wrap"><?= htmlspecialchars($post['caption'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($post['image_url'])): ?>
                <img src="<?= htmlspecialchars($post['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-full max-h-72 object-cover rounded mt-2">
            <?php endif; ?>
            <div class="mt-3 flex items-center gap-3">
                <button type="button" class="like-btn text-sm <?= $post['liked_by_viewer'] ? 'text-amber-700 font-semibold' : 'text-stone-500' ?>" data-post-id="<?= (int) $post['post_id'] ?>">
                    &hearts; <span class="like-count"><?= (int) $post['like_count'] ?></span>
                </button>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($posts)): ?>
        <p class="text-stone-500 text-sm">No posts yet — follow some cafés from Discover.</p>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.like-btn').forEach(function (btn) {
    btn.addEventListener('click', async function () {
        const postId = btn.dataset.postId;
        const res = await fetch('/feed/like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'post_id=' + encodeURIComponent(postId)
        });
        const data = await res.json();
        btn.querySelector('.like-count').textContent = data.like_count;
        btn.classList.toggle('text-amber-700', data.liked);
        btn.classList.toggle('font-semibold', data.liked);
        btn.classList.toggle('text-stone-500', !data.liked);
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
