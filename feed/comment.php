<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/PostComment.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../includes/auth_check.php';

$post_id = (int) ($_POST['post_id'] ?? 0);
$comment_text = (string) trim($_POST['comment_text'] ?? '');
$user_id = (int) $_SESSION['user_id'];

header("Content-Type: application/json");

if ($post_id <= 0 || $comment_text === '') {
    echo json_encode(["success" => false]);
    exit;
}

$pdo = (new Database())->connect();
$commentModel = new PostComment($pdo);
$commentModel->create($post_id, $user_id, $comment_text);

$post = (new Post($pdo))->getById($post_id);
if ($post && (int) $post['author_user_id'] !== $user_id) {
    (new Notification($pdo))->create((int) $post['author_user_id'], $_SESSION['full_name'] . ' commented on your post.');
}

echo json_encode([
    "success" => true,
    "commenter_name" => $_SESSION['full_name'],
    "commenter_avatar_url" => $_SESSION['avatar_url'] ?? '',
]);
