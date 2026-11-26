<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserFollow.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/PostImage.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';

$profile_user_id = (int) ($_GET['id'] ?? $_SESSION['user_id']);
$viewer_id = (int) $_SESSION['user_id'];

$pdo = (new Database())->connect();
$userModel = new User($pdo);
$profile_user = $userModel->getById($profile_user_id);

if (!$profile_user) {
    header("Location: /index.php");
    exit;
}

$userFollowModel = new UserFollow($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_follow' && $profile_user_id !== $viewer_id) {
    $userFollowModel->toggle($viewer_id, $profile_user_id);
    header("Location: /profile/view.php?id={$profile_user_id}");
    exit;
}

$is_own = $profile_user_id === $viewer_id;
$is_following = !$is_own && $userFollowModel->isFollowing($viewer_id, $profile_user_id);
$follower_count = $userFollowModel->countFollowers($profile_user_id);
$following_count = $userFollowModel->countFollowing($profile_user_id);

$postModel = new Post($pdo);
$posts = $postModel->getFeed($viewer_id, null, $profile_user_id);
$images_by_post = (new PostImage($pdo))->getForPosts(array_column($posts, 'post_id'));

if (!$is_own) {
    $page_title = $profile_user['full_name'];
    $back_url = '/feed/index.php';
}
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Profile header -->
<div class="bg-white px-4 pb-4 pt-5 lg:mx-auto lg:mt-4 lg:max-w-xl lg:rounded-2xl lg:border lg:border-stone-100">
    <div class="flex items-center gap-4">
        <?php render_avatar($profile_user['avatar_url'] ?? null, $profile_user['full_name'], 'w-16 h-16'); ?>
        <div class="min-w-0 flex-1">
            <h1 class="truncate text-base font-black text-stone-900"><?= htmlspecialchars($profile_user['full_name'], ENT_QUOTES, 'UTF-8') ?></h1>
            <div class="mt-1 flex gap-4 text-[13px]">
                <span><b><?= count($posts) ?></b> <span class="text-stone-500">posts</span></span>
                <span><b><?= $follower_count ?></b> <span class="text-stone-500">followers</span></span>
                <span><b><?= $following_count ?></b> <span class="text-stone-500">following</span></span>
            </div>
        </div>
    </div>

    <?php if (!empty($profile_user['bio'])): ?>
        <p class="mt-2.5 text-[13px] leading-snug text-stone-600"><?= nl2br(htmlspecialchars($profile_user['bio'], ENT_QUOTES, 'UTF-8')) ?></p>
    <?php endif; ?>

    <div class="mt-3 <?= $is_own ? 'flex gap-2' : '' ?>">
        <?php if ($is_own): ?>
            <a href="/profile/edit.php" class="flex-1 rounded-xl border border-stone-300 py-2 text-center text-sm font-bold text-stone-700 active:bg-stone-50">Edit profile</a>
            <a href="/auth/logout.php" data-confirm="Log out of Higoffe?" class="flex shrink-0 items-center justify-center gap-1.5 rounded-xl border border-stone-300 px-4 py-2 text-sm font-bold text-stone-500 active:bg-stone-50">
                <?php icon('logout', 'w-4 h-4'); ?> Log out
            </a>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="action" value="toggle_follow">
                <button type="submit" class="w-full rounded-xl py-2 text-sm font-bold <?= $is_following ? 'border border-stone-300 text-stone-600' : 'bg-coffee text-white' ?>">
                    <?= $is_following ? 'Following' : 'Follow' ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Posts -->
<section class="mt-3 lg:mx-auto lg:max-w-xl">
    <?php if (empty($posts)): ?>
        <?php render_empty_state('photo', 'No posts yet', $is_own ? 'Share your first café moment from the Post tab.' : ''); ?>
    <?php else: ?>
        <div class="space-y-2.5">
            <?php foreach ($posts as $post): ?>
                <article class="bg-white px-4 py-3 lg:rounded-2xl lg:border lg:border-stone-100">
                    <?php if (!empty($post['caption'])): ?>
                        <p class="whitespace-pre-wrap text-sm text-stone-800"><?= htmlspecialchars($post['caption'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <?php render_post_media($post, $images_by_post[$post['post_id']] ?? []); ?>
                    <p class="mt-2 flex items-center gap-3 text-[11px] text-stone-400">
                        <span class="flex items-center gap-1"><?php icon('heart', 'w-3.5 h-3.5'); ?><?= (int) $post['like_count'] ?></span>
                        <span class="flex items-center gap-1"><?php icon('chat-bubble', 'w-3.5 h-3.5'); ?><?= (int) $post['comment_count'] ?></span>
                        <?php if ($is_own): ?>
                            <form method="post" action="/feed/delete_post.php" data-confirm="Delete this post?" class="ml-auto">
                                <input type="hidden" name="post_id" value="<?= (int) $post['post_id'] ?>">
                                <input type="hidden" name="return_to" value="/profile/view.php">
                                <button type="submit" aria-label="Delete post" class="flex items-center gap-1 text-stone-300 hover:text-red-500">
                                    <?php icon('trash', 'w-3.5 h-3.5'); ?> Delete
                                </button>
                            </form>
                        <?php endif; ?>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
