<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../includes/auth_check.php';

$conversation_id = (int) ($_POST['conversation_id'] ?? 0);
$body = (string) trim($_POST['body'] ?? '');

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

if (!$is_participant || $body === '') {
    echo json_encode(["success" => false]);
    exit;
}

$messageModel = new Message($pdo);
$message_id = $messageModel->create($conversation_id, $user_id, 'text', $body);
$conversationModel->touchLastMessageAt($conversation_id);

echo json_encode(["success" => true, "message_id" => $message_id]);
