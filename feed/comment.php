<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/PostComment.php';
require_once __DIR__ . '/../includes/auth_check.php';

$post_id = (int) ($_POST['post_id'] ?? 0);
$comment_text = (string) trim($_POST['comment_text'] ?? '');

header("Content-Type: application/json");

if ($post_id <= 0 || $comment_text === '') {
    echo json_encode(["success" => false]);
    exit;
}

$pdo = (new Database())->connect();
$commentModel = new PostComment($pdo);
$commentModel->create($post_id, (int) $_SESSION['user_id'], $comment_text);

echo json_encode(["success" => true, "commenter_name" => $_SESSION['full_name']]);
