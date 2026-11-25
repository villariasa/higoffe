<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserFollow.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../includes/auth_check.php';

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

$is_following = $profile_user_id !== $viewer_id && $userFollowModel->isFollowing($viewer_id, $profile_user_id);
$follower_count = $userFollowModel->countFollowers($profile_user_id);
$following_count = $userFollowModel->countFollowing($profile_user_id);

$postModel = new Post($pdo);
$posts = $postModel->getFeed($viewer_id, null, $profile_user_id);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="bg-white border border-stone-200 rounded-xl p-6 mb-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-4">
            <?php if (!empty($profile_user['avatar_url'])): ?>
                <img src="<?= htmlspecialchars($profile_user['avatar_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-16 h-16 rounded-full object-cover border border-stone-200">
            <?php else: ?>
                <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xl">
                    <?= htmlspecialchars(mb_strtoupper(mb_substr($profile_user['full_name'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
            <div>
                <h1 class="text-xl font-bold"><?= htmlspecialchars($profile_user['full_name'], ENT_QUOTES, 'UTF-8') ?></h1>
                <?php if (!empty($profile_user['bio'])): ?>
                    <p class="text-sm text-stone-600 mt-1"><?= nl2br(htmlspecialchars($profile_user['bio'], ENT_QUOTES, 'UTF-8')) ?></p>
                <?php endif; ?>
                <p class="text-xs text-stone-400 mt-1">
                    <span class="font-semibold text-stone-600"><?= $follower_count ?></span> followers
                    &middot;
                    <span class="font-semibold text-stone-600"><?= $following_count ?></span> following
                </p>
            </div>
        </div>
        <?php if ($profile_user_id === $viewer_id): ?>
            <a href="/profile/edit.php" class="rounded-full border border-stone-300 px-4 py-2 text-xs font-semibold text-stone-700 hover:border-amber-600 hover:text-amber-700">Edit profile</a>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="action" value="toggle_follow">
                <button type="submit" class="rounded-full <?= $is_following ? 'border border-stone-300 text-stone-700' : 'bg-amber-700 text-white' ?> px-4 py-2 text-xs font-semibold">
                    <?= $is_following ? 'Following' : 'Follow' ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<h2 class="text-lg font-bold mb-3">Posts</h2>
<div class="space-y-4 max-w-xl">
    <?php foreach ($posts as $post): ?>
        <div class="bg-white border border-stone-200 rounded-xl p-4">
            <p class="whitespace-pre-wrap"><?= htmlspecialchars($post['caption'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($post['image_url'])): ?>
                <img src="<?= htmlspecialchars($post['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-full max-h-72 object-cover rounded-lg mt-2">
            <?php endif; ?>
            <p class="text-xs text-stone-400 mt-2"><?= (int) $post['like_count'] ?> likes &middot; <?= (int) $post['comment_count'] ?> comments</p>
        </div>
    <?php endforeach; ?>
    <?php if (empty($posts)): ?>
        <p class="text-stone-500 text-sm">No posts yet.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
