<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/PostComment.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = (new Database())->connect();
$postModel = new Post($pdo);
$posts = $postModel->getFeed((int) $_SESSION['user_id']);

$commentModel = new PostComment($pdo);
$comments_by_post = [];
foreach ($posts as $post) {
    $comments_by_post[$post['post_id']] = $commentModel->getForPost((int) $post['post_id']);
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold">Feed</h1>
    <a href="/feed/create_post.php" class="bg-amber-700 text-white rounded-full px-4 py-2 text-sm font-semibold">+ Post</a>
</div>

<div class="space-y-4 max-w-xl">
    <?php foreach ($posts as $post): ?>
        <?php $is_cafe_post = !empty($post['cafe_id']); ?>
        <div class="bg-white border border-stone-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <?php if ($is_cafe_post): ?>
                    <a href="/cafes/view.php?id=<?= (int) $post['cafe_id'] ?>" class="flex items-center gap-2 hover:text-amber-700">
                        <?php if (!empty($post['logo_url'])): ?>
                            <img src="<?= htmlspecialchars($post['logo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-8 h-8 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-8 h-8 rounded-full bg-amber-100"></div>
                        <?php endif; ?>
                        <span class="font-semibold"><?= htmlspecialchars($post['cafe_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php else: ?>
                    <a href="/profile/view.php?id=<?= (int) $post['author_user_id'] ?>" class="flex items-center gap-2 hover:text-amber-700">
                        <?php if (!empty($post['author_avatar_url'])): ?>
                            <img src="<?= htmlspecialchars($post['author_avatar_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-8 h-8 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-8 h-8 rounded-full bg-stone-200 flex items-center justify-center text-xs font-bold text-stone-600">
                                <?= htmlspecialchars(mb_strtoupper(mb_substr($post['author_name'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                        <span class="font-semibold"><?= htmlspecialchars($post['author_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endif; ?>
                <form method="post" action="/reports/submit.php" class="report-form">
                    <input type="hidden" name="target_type" value="post">
                    <input type="hidden" name="target_id" value="<?= (int) $post['post_id'] ?>">
                    <input type="hidden" name="return_to" value="/feed/index.php">
                    <input type="hidden" name="reason" class="reason-input">
                    <button type="submit" class="text-xs text-stone-400 hover:text-red-600">Report</button>
                </form>
            </div>

            <p class="whitespace-pre-wrap"><?= htmlspecialchars($post['caption'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($post['image_url'])): ?>
                <img src="<?= htmlspecialchars($post['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-full max-h-72 object-cover rounded-lg mt-2">
            <?php endif; ?>

            <div class="mt-3 flex items-center gap-4">
                <button type="button" class="like-btn text-sm <?= $post['liked_by_viewer'] ? 'text-amber-700 font-semibold' : 'text-stone-500' ?>" data-post-id="<?= (int) $post['post_id'] ?>">
                    &hearts; <span class="like-count"><?= (int) $post['like_count'] ?></span>
                </button>
                <span class="text-sm text-stone-400"><?= (int) $post['comment_count'] ?> comments</span>
            </div>

            <div class="mt-3 border-t border-stone-100 pt-3 space-y-2 comment-list" data-post-id="<?= (int) $post['post_id'] ?>">
                <?php foreach ($comments_by_post[$post['post_id']] as $comment): ?>
                    <p class="text-sm">
                        <span class="font-semibold"><?= htmlspecialchars($comment['commenter_name'], ENT_QUOTES, 'UTF-8') ?>:</span>
                        <?= htmlspecialchars($comment['comment_text'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endforeach; ?>
            </div>

            <form class="comment-form mt-2 flex gap-2" data-post-id="<?= (int) $post['post_id'] ?>">
                <input type="text" placeholder="Write a comment..." maxlength="500" class="comment-input flex-1 border border-stone-300 rounded-full px-3 py-1.5 text-sm">
                <button type="submit" class="text-amber-700 text-sm font-semibold">Send</button>
            </form>
        </div>
    <?php endforeach; ?>
    <?php if (empty($posts)): ?>
        <p class="text-stone-500 text-sm">No posts yet — follow some cafés and people from Discover.</p>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.report-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        const reason = window.prompt('Why are you reporting this?');
        if (reason === null) { e.preventDefault(); return; }
        form.querySelector('.reason-input').value = reason;
    });
});

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

document.querySelectorAll('.comment-form').forEach(function (form) {
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const postId = form.dataset.postId;
        const input = form.querySelector('.comment-input');
        const text = input.value.trim();
        if (!text) return;

        const res = await fetch('/feed/comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'post_id=' + encodeURIComponent(postId) + '&comment_text=' + encodeURIComponent(text)
        });
        const data = await res.json();
        if (data.success) {
            const list = document.querySelector('.comment-list[data-post-id="' + postId + '"]');
            const p = document.createElement('p');
            p.className = 'text-sm';
            const strong = document.createElement('span');
            strong.className = 'font-semibold';
            strong.textContent = data.commenter_name + ': ';
            p.appendChild(strong);
            p.appendChild(document.createTextNode(text));
            list.appendChild(p);
            input.value = '';
        }
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
