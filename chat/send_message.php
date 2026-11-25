<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Uploads.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../includes/auth_check.php';

$conversation_id = (int) ($_POST['conversation_id'] ?? 0);
$body = (string) trim($_POST['body'] ?? '');
$message_type = ($_POST['type'] ?? 'text') === 'image' ? 'image' : 'text';
$image_url = (string) trim($_POST['image_url'] ?? '');

$pdo = (new Database())->connect();
$conversationModel = new Conversation($pdo);
$conversation = $conversationModel->getById($conversation_id);

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];
$is_participant = $conversation && (
    ($role === 'customer' && (int) $conversation['customer_user_id'] === $user_id)
    || ($role === 'seller' && (int) $conversation['assigned_seller_user_id'] === $user_id)
);

header("Content-Type: application/json");

$has_content = $message_type === 'image' ? $image_url !== '' : $body !== '';
// Blocks a crafted request (not from our own upload JS) from storing a
// javascript: URL that would execute when rendered inside an <a href>.
if (!$is_participant || !$has_content || !Uploads::isAllowedMediaUrl($image_url)) {
    echo json_encode(["success" => false]);
    exit;
}

$messageModel = new Message($pdo);
$message_id = $messageModel->create($conversation_id, $user_id, $message_type, $body, null, $image_url);
$conversationModel->touchLastMessageAt($conversation_id);
$conversationModel->clearTyping($conversation_id, $role);
$conversationModel->markRead($conversation_id, $role, $message_id);

echo json_encode(["success" => true, "message_id" => $message_id]);
