<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/PostLike.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../includes/auth_check.php';

$post_id = (int) ($_POST['post_id'] ?? 0);
$user_id = (int) $_SESSION['user_id'];

$pdo = (new Database())->connect();
$postLikeModel = new PostLike($pdo);
$liked = $postLikeModel->toggle($post_id, $user_id);

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = :post_id");
$count_stmt->execute(["post_id" => $post_id]);
$like_count = (int) $count_stmt->fetchColumn();

if ($liked) {
    $post = (new Post($pdo))->getById($post_id);
    if ($post && (int) $post['author_user_id'] !== $user_id) {
        (new Notification($pdo))->create((int) $post['author_user_id'], $_SESSION['full_name'] . ' liked your post.');
    }
}

header("Content-Type: application/json");
echo json_encode(["liked" => $liked, "like_count" => $like_count]);
