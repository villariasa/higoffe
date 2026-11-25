<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/PostComment.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';

$post_id = (int) ($_GET['id'] ?? 0);

$pdo = (new Database())->connect();
$postModel = new Post($pdo);
$post = $postModel->getByIdWithMeta((int) $_SESSION['user_id'], $post_id);

if (!$post) {
    header("Location: /feed/index.php");
    exit;
}

$commentModel = new PostComment($pdo);
$comments = $commentModel->getForPost($post_id);
$is_cafe_post = !empty($post['cafe_id']);

$page_title = 'Post';
$back_url = '/feed/index.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="px-4 py-4 lg:mx-auto lg:max-w-2xl lg:px-0">
    <article class="bg-white px-4 py-3 lg:rounded-2xl lg:border lg:border-stone-100 lg:p-5">
        <div class="flex items-center gap-2.5">
            <?php if ($is_cafe_post): ?>
                <a href="/cafes/view.php?id=<?= (int) $post['cafe_id'] ?>" class="flex min-w-0 items-center gap-2.5">
                    <?php render_avatar($post['logo_url'] ?? null, $post['cafe_name']); ?>
                    <div class="min-w-0">
                        <p class="flex items-center gap-1 truncate text-[13px] font-bold text-stone-900">
                            <?= htmlspecialchars($post['cafe_name'], ENT_QUOTES, 'UTF-8') ?>
                            <?php icon('check-badge-solid', 'w-3.5 h-3.5 text-accent'); ?>
                        </p>
                        <p class="text-[11px] text-stone-400">Café · <?= htmlspecialchars(time_ago($post['created_at']), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </a>
            <?php else: ?>
                <a href="/profile/view.php?id=<?= (int) $post['author_user_id'] ?>" class="flex min-w-0 items-center gap-2.5">
                    <?php render_avatar($post['author_avatar_url'] ?? null, $post['author_name']); ?>
                    <div class="min-w-0">
                        <p class="truncate text-[13px] font-bold text-stone-900"><?= htmlspecialchars($post['author_name'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="flex flex-wrap items-center gap-x-1 text-[11px] text-stone-400">
                            <?php if (!empty($post['tagged_cafe_name'])): ?>
                                <?php icon('map-pin-solid', 'w-3 h-3 text-accent'); ?>
                                is at <span class="font-bold text-stone-600"><?= htmlspecialchars($post['tagged_cafe_name'], ENT_QUOTES, 'UTF-8') ?></span> ·
                            <?php endif; ?>
                            <?= htmlspecialchars(time_ago($post['created_at']), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($post['caption'])): ?>
            <p class="mt-2 whitespace-pre-wrap text-sm text-stone-800"><?= htmlspecialchars($post['caption'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if (!empty($post['image_url'])): ?>
            <img src="<?= htmlspecialchars($post['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="mt-2 w-full rounded-xl object-cover">
        <?php endif; ?>
        <?php render_product_chip($post); ?>

        <div class="mt-2.5 flex items-center gap-5 border-t border-stone-100 pt-2">
            <span class="flex items-center gap-1.5 text-[13px] font-semibold <?= $post['liked_by_viewer'] ? 'text-accent' : 'text-stone-500' ?>">
                <?php icon($post['liked_by_viewer'] ? 'heart-solid' : 'heart', 'w-[18px] h-[18px]'); ?>
                <?= (int) $post['like_count'] ?>
            </span>
            <span class="flex items-center gap-1.5 text-[13px] font-semibold text-stone-500">
                <?php icon('chat-bubble', 'w-[18px] h-[18px]'); ?>
                <?= (int) $post['comment_count'] ?>
            </span>
            <button type="button" id="copy-link-btn" class="ml-auto flex items-center gap-1.5 text-[13px] font-semibold text-stone-500 hover:text-accent">
                <?php icon('link', 'w-4 h-4'); ?> Copy link
            </button>
        </div>

        <!-- Comments -->
        <div class="mt-3 space-y-1.5 border-t border-stone-100 pt-3">
            <?php foreach ($comments as $comment): ?>
                <p class="text-[13px] text-stone-700">
                    <span class="font-bold"><?= htmlspecialchars($comment['commenter_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?= htmlspecialchars($comment['comment_text'], ENT_QUOTES, 'UTF-8') ?>
                    <span class="text-[10px] text-stone-400"> · <?= htmlspecialchars(time_ago($comment['created_at']), ENT_QUOTES, 'UTF-8') ?></span>
                </p>
            <?php endforeach; ?>
            <?php if (empty($comments)): ?>
                <p class="text-[13px] text-stone-400">No comments yet — be the first.</p>
            <?php endif; ?>
        </div>
        <form class="comment-form mt-2 flex gap-2" data-post-id="<?= (int) $post['post_id'] ?>">
            <input type="text" placeholder="Add a comment..." maxlength="500" class="comment-input flex-1 rounded-full border border-stone-200 bg-cream px-3 py-1.5 text-[13px] focus:border-coffee focus:outline-none">
            <button type="submit" aria-label="Send comment" class="text-accent"><?php icon('paper-airplane', 'w-5 h-5'); ?></button>
        </form>
    </article>
</div>

<script>
document.getElementById('copy-link-btn').addEventListener('click', function () {
    navigator.clipboard.writeText(window.location.origin + '/feed/post.php?id=<?= (int) $post['post_id'] ?>').then(function () {
        window.higoffeToast('Link copied!');
    }, function () {
        window.higoffeToast('Could not copy the link.', 'error');
    });
});

document.querySelector('.comment-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.target;
    const input = form.querySelector('.comment-input');
    const text = input.value.trim();
    if (!text) return;
    const res = await fetch('/feed/comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'post_id=' + encodeURIComponent(form.dataset.postId) + '&comment_text=' + encodeURIComponent(text)
    });
    const data = await res.json();
    if (data.success) {
        window.location.reload();
    }
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
