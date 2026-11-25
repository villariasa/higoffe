<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../includes/auth_check.php';

$post_id = (int) ($_POST['post_id'] ?? 0);
$return_to = (string) ($_POST['return_to'] ?? '/feed/index.php');

$pdo = (new Database())->connect();
$postModel = new Post($pdo);
$post = $postModel->getById($post_id);

// Only the author may delete their own post here (admins moderate via
// admin/posts.php, which also notifies the poster).
if ($post && (int) $post['author_user_id'] === (int) $_SESSION['user_id']) {
    $postModel->delete($post_id);
}

// Only allow redirecting back within the app — must start with a single
// "/" (a second "/" or "\" would make it protocol-relative, i.e. an open
// redirect to another host).
if ($return_to === '' || $return_to[0] !== '/'
    || (isset($return_to[1]) && ($return_to[1] === '/' || $return_to[1] === '\\'))
    || !preg_match('#^/[a-zA-Z0-9/_.?=&-]*$#', $return_to)) {
    $return_to = '/feed/index.php';
}
header("Location: {$return_to}");
exit;
