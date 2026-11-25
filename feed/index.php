<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/PostComment.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';

$pdo = (new Database())->connect();

$cafeModel = new Cafe($pdo);
$popular_cafes = array_slice($cafeModel->getAll(), 0, 10);

$productModel = new Product($pdo);
$trending = $productModel->getTrending(10);

$postModel = new Post($pdo);
$posts = $postModel->getFeed((int) $_SESSION['user_id']);

$commentModel = new PostComment($pdo);
$comments_by_post = [];
foreach ($posts as $post) {
    $comments_by_post[$post['post_id']] = $commentModel->getForPost((int) $post['post_id']);
}

$categories = [
    ['label' => 'Coffee',   'emoji' => '☕', 'q' => 'coffee'],
    ['label' => 'Milk Tea', 'emoji' => '🧋', 'q' => 'milk tea'],
    ['label' => 'Pastries', 'emoji' => '🥐', 'q' => 'pastry'],
    ['label' => 'Matcha',   'emoji' => '🍵', 'q' => 'matcha'],
    ['label' => 'Frappe',   'emoji' => '🥤', 'q' => 'frappe'],
    ['label' => 'Study',    'emoji' => '📚', 'q' => 'study'],
];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Search -->
<div class="px-4 pt-3 pb-1">
    <?php render_search_bar('/cafes/discover.php', 'Search cafés, drinks, posts...'); ?>
</div>

<!-- Categories -->
<div class="no-scrollbar mt-3 flex gap-2 overflow-x-auto px-4">
    <?php foreach ($categories as $cat): ?>
        <a href="/cafes/discover.php?q=<?= urlencode($cat['q']) ?>" class="flex w-16 shrink-0 flex-col items-center gap-1">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white border border-stone-200 text-xl"><?= $cat['emoji'] ?></span>
            <span class="text-[11px] font-medium text-stone-600"><?= htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8') ?></span>
        </a>
    <?php endforeach; ?>
</div>

<!-- Popular cafés -->
<?php if (!empty($popular_cafes)): ?>
    <section class="mt-5">
        <?php render_section_header('Popular cafés', '/cafes/discover.php'); ?>
        <div class="no-scrollbar flex gap-3 overflow-x-auto px-4">
            <?php foreach ($popular_cafes as $cafe): ?>
                <?php render_cafe_card_h($cafe); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- Trending drinks -->
<?php if (!empty($trending)): ?>
    <section class="mt-5">
        <?php render_section_header('Trending drinks'); ?>
        <div class="no-scrollbar flex gap-3 overflow-x-auto px-4">
            <?php foreach ($trending as $product): ?>
                <?php render_drink_card($product); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- Social feed -->
<section class="mt-5">
    <?php render_section_header('Café moments'); ?>

    <?php if (empty($posts)): ?>
        <?php render_empty_state('☕', 'No posts yet', 'Follow cafés and people from Explore, or share your first café moment.'); ?>
    <?php endif; ?>

    <div class="space-y-2.5">
        <?php foreach ($posts as $post): ?>
            <?php $is_cafe_post = !empty($post['cafe_id']); ?>
            <article class="bg-white px-4 py-3">
                <!-- Post header -->
                <div class="flex items-center justify-between">
                    <?php if ($is_cafe_post): ?>
                        <a href="/cafes/view.php?id=<?= (int) $post['cafe_id'] ?>" class="flex min-w-0 items-center gap-2.5">
                            <?php render_avatar($post['logo_url'] ?? null, $post['cafe_name']); ?>
                            <div class="min-w-0">
                                <p class="truncate text-[13px] font-bold text-stone-900"><?= htmlspecialchars($post['cafe_name'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-[11px] text-stone-400">Café · Promo &amp; updates</p>
                            </div>
                        </a>
                    <?php else: ?>
                        <a href="/profile/view.php?id=<?= (int) $post['author_user_id'] ?>" class="flex min-w-0 items-center gap-2.5">
                            <?php render_avatar($post['author_avatar_url'] ?? null, $post['author_name']); ?>
                            <div class="min-w-0">
                                <p class="truncate text-[13px] font-bold text-stone-900"><?= htmlspecialchars($post['author_name'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-[11px] text-stone-400">Café moment</p>
                            </div>
                        </a>
                    <?php endif; ?>
                    <form method="post" action="/reports/submit.php" class="report-form shrink-0">
                        <input type="hidden" name="target_type" value="post">
                        <input type="hidden" name="target_id" value="<?= (int) $post['post_id'] ?>">
                        <input type="hidden" name="return_to" value="/feed/index.php">
                        <input type="hidden" name="reason" class="reason-input">
                        <button type="submit" aria-label="Report post" class="px-1 text-stone-300 active:text-red-500">&#8942;</button>
                    </form>
                </div>

                <!-- Caption + image -->
                <?php if (!empty($post['caption'])): ?>
                    <p class="mt-2 whitespace-pre-wrap text-sm text-stone-800"><?= htmlspecialchars($post['caption'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php if (!empty($post['image_url'])): ?>
                    <img src="<?= htmlspecialchars($post['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="mt-2 max-h-80 w-full rounded-xl object-cover">
                <?php endif; ?>

                <!-- Actions -->
                <div class="mt-2.5 flex items-center gap-5 border-t border-stone-100 pt-2">
                    <button type="button" class="like-btn flex items-center gap-1.5 text-[13px] font-semibold <?= $post['liked_by_viewer'] ? 'text-accent' : 'text-stone-500' ?>" data-post-id="<?= (int) $post['post_id'] ?>">
                        &hearts; <span class="like-count"><?= (int) $post['like_count'] ?></span>
                    </button>
                    <button type="button" class="comment-toggle flex items-center gap-1.5 text-[13px] font-semibold text-stone-500" data-post-id="<?= (int) $post['post_id'] ?>">
                        &#128172; <span><?= (int) $post['comment_count'] ?></span>
                    </button>
                    <?php if ($is_cafe_post && $_SESSION['role'] === 'customer'): ?>
                        <form method="post" action="/chat/start.php" class="ml-auto">
                            <input type="hidden" name="cafe_id" value="<?= (int) $post['cafe_id'] ?>">
                            <button type="submit" class="rounded-full bg-coffee px-3 py-1 text-[11px] font-bold text-white">Inquire</button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Comments -->
                <div class="comment-section mt-1 hidden" data-post-id="<?= (int) $post['post_id'] ?>">
                    <div class="comment-list space-y-1.5 pt-1" data-post-id="<?= (int) $post['post_id'] ?>">
                        <?php foreach ($comments_by_post[$post['post_id']] as $comment): ?>
                            <p class="text-[13px] text-stone-700">
                                <span class="font-bold"><?= htmlspecialchars($comment['commenter_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?= htmlspecialchars($comment['comment_text'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        <?php endforeach; ?>
                    </div>
                    <form class="comment-form mt-2 flex gap-2" data-post-id="<?= (int) $post['post_id'] ?>">
                        <input type="text" placeholder="Add a comment..." maxlength="500" class="comment-input flex-1 rounded-full border border-stone-200 bg-cream px-3 py-1.5 text-[13px] focus:border-coffee focus:outline-none">
                        <button type="submit" class="text-[13px] font-bold text-accent">Send</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<script>
document.querySelectorAll('.report-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        const reason = window.prompt('Why are you reporting this?');
        if (reason === null) { e.preventDefault(); return; }
        form.querySelector('.reason-input').value = reason;
    });
});

document.querySelectorAll('.comment-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const section = document.querySelector('.comment-section[data-post-id="' + btn.dataset.postId + '"]');
        section.classList.toggle('hidden');
        if (!section.classList.contains('hidden')) {
            section.querySelector('.comment-input').focus();
        }
    });
});

document.querySelectorAll('.like-btn').forEach(function (btn) {
    btn.addEventListener('click', async function () {
        const res = await fetch('/feed/like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'post_id=' + encodeURIComponent(btn.dataset.postId)
        });
        const data = await res.json();
        btn.querySelector('.like-count').textContent = data.like_count;
        btn.classList.toggle('text-accent', data.liked);
        btn.classList.toggle('text-stone-500', !data.liked);
    });
});

document.querySelectorAll('.comment-form').forEach(function (form) {
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
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
            const list = document.querySelector('.comment-list[data-post-id="' + form.dataset.postId + '"]');
            const p = document.createElement('p');
            p.className = 'text-[13px] text-stone-700';
            const strong = document.createElement('span');
            strong.className = 'font-bold';
            strong.textContent = data.commenter_name + ' ';
            p.appendChild(strong);
            p.appendChild(document.createTextNode(text));
            list.appendChild(p);
            input.value = '';
        }
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
