<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/PostComment.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/UserFollow.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';

$pdo = (new Database())->connect();

$cafeModel = new Cafe($pdo);

$lat = isset($_GET['lat']) && is_numeric($_GET['lat']) ? (float) $_GET['lat'] : null;
$lng = isset($_GET['lng']) && is_numeric($_GET['lng']) ? (float) $_GET['lng'] : null;

$is_nearby = false;
if ($lat !== null && $lng !== null) {
    $nearby_cafes = $cafeModel->getNearby($lat, $lng, 10);
    if (!empty($nearby_cafes)) {
        $display_cafes = $nearby_cafes;
        $is_nearby = true;
    } else {
        $display_cafes = array_slice($cafeModel->getAll($lat, $lng), 0, 10);
    }
} else {
    $display_cafes = array_slice($cafeModel->getAll(), 0, 10);
}

$productModel = new Product($pdo);
$trending = $productModel->getTrending(10);

$userFollowModel = new UserFollow($pdo);
$suggested_users = $userFollowModel->getSuggestions((int) $_SESSION['user_id'], 5);

$postModel = new Post($pdo);
$posts = $postModel->getFeed((int) $_SESSION['user_id']);

$commentModel = new PostComment($pdo);
$comments_by_post = [];
foreach ($posts as $post) {
    $comments_by_post[$post['post_id']] = $commentModel->getForPost((int) $post['post_id']);
}

$categories = [
    ['label' => 'Coffee',   'icon' => 'storefront',   'q' => 'coffee'],
    ['label' => 'Milk Tea', 'icon' => 'beaker',       'q' => 'milk tea'],
    ['label' => 'Pastries', 'icon' => 'cake',         'q' => 'pastry'],
    ['label' => 'Matcha',   'icon' => 'sparkles',     'q' => 'matcha'],
    ['label' => 'Frappe',   'icon' => 'fire',         'q' => 'frappe'],
    ['label' => 'Study',    'icon' => 'academic-cap', 'q' => 'study'],
];

$wants_location = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="lg:grid lg:grid-cols-[minmax(0,1fr)_340px] lg:gap-8 lg:px-8 lg:pt-6 2xl:px-14">
<div class="min-w-0">

<!-- Search (mobile — on desktop it lives in the right rail) -->
<div class="px-4 pt-3 pb-1 lg:hidden">
    <?php render_search_bar('/cafes/discover.php', 'Search cafés, drinks, posts...'); ?>
</div>

<!-- Categories -->
<div class="no-scrollbar mt-3 flex gap-2 overflow-x-auto px-4 lg:mt-0 lg:gap-4 lg:px-0">
    <?php foreach ($categories as $cat): ?>
        <a href="/cafes/discover.php?q=<?= urlencode($cat['q']) ?>" class="group flex w-16 shrink-0 flex-col items-center gap-1 lg:w-20">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl border border-stone-200 bg-white text-coffee transition-colors group-hover:border-accent group-hover:text-accent lg:h-14 lg:w-14">
                <?php icon($cat['icon'], 'w-6 h-6'); ?>
            </span>
            <span class="text-[11px] font-medium text-stone-600"><?= htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8') ?></span>
        </a>
    <?php endforeach; ?>
</div>

<!-- Popular or Nearby cafés (mobile row — desktop uses the rail card) -->
<?php if (!empty($display_cafes)): ?>
    <section class="mt-5 lg:hidden">
        <?php render_section_header($is_nearby ? 'Cafés near you' : 'Popular cafés', '/cafes/discover.php'); ?>
        <div class="no-scrollbar flex gap-3 overflow-x-auto px-4">
            <?php foreach ($display_cafes as $cafe): ?>
                <?php render_cafe_card_h($cafe); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- Trending drinks (mobile row — desktop uses the rail card) -->
<?php if (!empty($trending)): ?>
    <section class="mt-5 lg:hidden">
        <?php render_section_header('Trending drinks'); ?>
        <div class="no-scrollbar flex gap-3 overflow-x-auto px-4">
            <?php foreach ($trending as $product): ?>
                <?php render_drink_card($product); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- Social feed -->
<section class="mt-5 lg:mt-6">
    <div class="lg:max-w-2xl">
        <?php render_section_header('Café moments'); ?>

        <?php if (empty($posts)): ?>
            <?php render_empty_state('storefront', 'No posts yet', 'Follow cafés and people from Explore, or share your first café moment.'); ?>
        <?php endif; ?>

        <div class="space-y-2.5">
            <?php foreach ($posts as $post): ?>
                <?php $is_cafe_post = !empty($post['cafe_id']); ?>
                <article class="bg-white px-4 py-3 lg:rounded-2xl lg:border lg:border-stone-100">
                    <!-- Post header -->
                    <div class="flex items-center justify-between">
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
                                        <?php if (($post['visibility'] ?? 'public') === 'cafe_only'): ?>
                                            <span class="rounded-full bg-stone-100 px-1.5 py-0.5 text-[9px] font-bold uppercase text-stone-500">Café only</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </a>
                        <?php endif; ?>
                        <div class="flex shrink-0 items-center gap-1">
                            <?php if ((int) $post['author_user_id'] === (int) $_SESSION['user_id']): ?>
                                <form method="post" action="/feed/delete_post.php" data-confirm="Delete this post?">
                                    <input type="hidden" name="post_id" value="<?= (int) $post['post_id'] ?>">
                                    <input type="hidden" name="return_to" value="/feed/index.php">
                                    <button type="submit" aria-label="Delete post" class="p-1 text-stone-300 hover:text-red-500">
                                        <?php icon('trash', 'w-4 h-4'); ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="/reports/submit.php" class="report-form">
                                    <input type="hidden" name="target_type" value="post">
                                    <input type="hidden" name="target_id" value="<?= (int) $post['post_id'] ?>">
                                    <input type="hidden" name="return_to" value="/feed/index.php">
                                    <input type="hidden" name="reason" class="reason-input">
                                    <button type="submit" aria-label="Report post" class="p-1 text-stone-300 hover:text-red-500">
                                        <?php icon('ellipsis-vertical', 'w-5 h-5'); ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Caption + image -->
                    <?php if (!empty($post['caption'])): ?>
                        <p class="mt-2 whitespace-pre-wrap text-sm text-stone-800"><?= htmlspecialchars($post['caption'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <?php if (!empty($post['image_url'])): ?>
                        <img src="<?= htmlspecialchars($post['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" decoding="async" class="mt-2 max-h-80 w-full rounded-xl object-cover">
                    <?php endif; ?>
                    <?php render_product_chip($post); ?>

                    <!-- Actions -->
                    <div class="mt-2.5 flex items-center gap-5 border-t border-stone-100 pt-2">
                        <button type="button" class="like-btn flex items-center gap-1.5 text-[13px] font-semibold <?= $post['liked_by_viewer'] ? 'text-accent' : 'text-stone-500' ?>" data-post-id="<?= (int) $post['post_id'] ?>">
                            <span class="heart-outline <?= $post['liked_by_viewer'] ? 'hidden' : '' ?>"><?php icon('heart', 'w-[18px] h-[18px]'); ?></span>
                            <span class="heart-solid <?= $post['liked_by_viewer'] ? '' : 'hidden' ?>"><?php icon('heart-solid', 'w-[18px] h-[18px]'); ?></span>
                            <span class="like-count"><?= (int) $post['like_count'] ?></span>
                        </button>
                        <button type="button" class="comment-toggle flex items-center gap-1.5 text-[13px] font-semibold text-stone-500" data-post-id="<?= (int) $post['post_id'] ?>">
                            <?php icon('chat-bubble', 'w-[18px] h-[18px]'); ?>
                            <span><?= (int) $post['comment_count'] ?></span>
                        </button>
                        <button type="button" class="share-btn flex items-center gap-1.5 text-[13px] font-semibold text-stone-500 hover:text-accent" data-post-id="<?= (int) $post['post_id'] ?>" aria-label="Copy link to post">
                            <?php icon('link', 'w-[16px] h-[16px]'); ?>
                        </button>
                        <?php if ($is_cafe_post && $_SESSION['role'] === 'customer'): ?>
                            <form method="post" action="/chat/start.php" class="ml-auto">
                                <input type="hidden" name="cafe_id" value="<?= (int) $post['cafe_id'] ?>">
                                <button type="submit" class="flex items-center gap-1 rounded-full bg-coffee px-3 py-1 text-[11px] font-bold text-white hover:opacity-90">
                                    <?php icon('chat-bubble', 'w-3 h-3'); ?> Inquire
                                </button>
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
                            <button type="submit" aria-label="Send comment" class="text-accent"><?php icon('paper-airplane', 'w-5 h-5'); ?></button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

</div><!-- /center column -->

<!-- Desktop right rail -->
<aside class="hidden space-y-4 self-start lg:sticky lg:top-6 lg:block">
    <?php render_search_bar('/cafes/discover.php', 'Search cafés, drinks, posts...'); ?>

    <?php if (!empty($display_cafes)): ?>
        <div class="rounded-2xl border border-stone-100 bg-white p-4">
            <div class="mb-2.5 flex items-center justify-between">
                <h2 class="text-sm font-bold text-stone-900"><?= $is_nearby ? 'Cafés near you' : 'Popular cafés' ?></h2>
                <a href="/cafes/discover.php" class="text-xs font-semibold text-accent hover:underline">See all</a>
            </div>
            <div class="space-y-2.5">
                <?php foreach (array_slice($display_cafes, 0, 5) as $cafe): ?>
                    <a href="/cafes/view.php?id=<?= (int) $cafe['cafe_id'] ?>" class="flex items-center gap-2.5 rounded-xl p-1.5 transition-colors hover:bg-cream">
                        <?php render_avatar($cafe['logo_url'] ?? ($cafe['cover_url'] ?? null), $cafe['cafe_name'], 'w-10 h-10'); ?>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-bold text-stone-900"><?= htmlspecialchars($cafe['cafe_name'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="flex items-center gap-1.5 text-[11px] text-stone-400">
                                <?php if (!empty($cafe['rating_count'])): ?>
                                    <span class="flex items-center gap-0.5 font-bold text-stone-600"><?php icon('star-solid', 'w-3 h-3 text-amber-400'); ?><?= number_format((float) $cafe['rating_avg'], 1) ?></span>
                                <?php endif; ?>
                                <?php if (isset($cafe['distance_km'])): ?>
                                    <span class="font-semibold text-accent"><?= number_format((float) $cafe['distance_km'], 1) ?> km</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($trending)): ?>
        <div class="rounded-2xl border border-stone-100 bg-white p-4">
            <h2 class="mb-2.5 text-sm font-bold text-stone-900">Trending drinks</h2>
            <div class="space-y-2.5">
                <?php foreach (array_slice($trending, 0, 5) as $product): ?>
                    <a href="/cafes/view.php?id=<?= (int) $product['cafe_id'] ?>" class="flex items-center gap-2.5 rounded-xl p-1.5 transition-colors hover:bg-cream">
                        <div class="h-10 w-10 shrink-0 overflow-hidden rounded-xl bg-stone-200">
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?= htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover">
                            <?php else: ?>
                                <div class="flex h-full w-full items-center justify-center text-stone-400"><?php icon('beaker', 'w-5 h-5'); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-bold text-stone-900"><?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="truncate text-[11px] text-stone-400"><?= htmlspecialchars($product['cafe_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <span class="shrink-0 text-xs font-black text-coffee">&#8369;<?= number_format((float) $product['base_price'], 2) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($suggested_users)): ?>
        <div class="rounded-2xl border border-stone-100 bg-white p-4">
            <h2 class="mb-2.5 text-sm font-bold text-stone-900">Suggested to follow</h2>
            <div class="space-y-2.5">
                <?php foreach ($suggested_users as $su): ?>
                    <a href="/profile/view.php?id=<?= (int) $su['user_id'] ?>" class="flex items-center gap-2.5 rounded-xl p-1.5 transition-colors hover:bg-cream">
                        <?php render_avatar($su['avatar_url'] ?? null, $su['full_name'], 'w-10 h-10'); ?>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-bold text-stone-900"><?= htmlspecialchars($su['full_name'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-[11px] text-stone-400"><?= (int) $su['follower_count'] ?> follower<?= (int) $su['follower_count'] === 1 ? '' : 's' ?></p>
                        </div>
                        <span class="shrink-0 rounded-full border border-coffee/30 px-2.5 py-1 text-[11px] font-bold text-coffee">View</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</aside>

</div><!-- /desktop grid -->

<script>
document.querySelectorAll('.share-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const url = window.location.origin + '/feed/post.php?id=' + btn.dataset.postId;
        navigator.clipboard.writeText(url).then(function () {
            window.higoffeToast('Link copied!');
        }, function () {
            window.higoffeToast('Could not copy the link.', 'error');
        });
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
        btn.querySelector('.heart-outline').classList.toggle('hidden', data.liked);
        btn.querySelector('.heart-solid').classList.toggle('hidden', !data.liked);
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
<script src="/includes/location.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
