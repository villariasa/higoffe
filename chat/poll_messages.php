<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../includes/auth_check.php';

$conversation_id = (int) ($_GET['conversation_id'] ?? 0);
$after_id = (int) ($_GET['after_id'] ?? 0);
$my_read_id = (int) ($_GET['my_read_id'] ?? 0);

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

if (!$is_participant) {
    echo json_encode(["messages" => [], "other_typing" => false, "other_read_id" => 0]);
    exit;
}

if ($my_read_id > 0) {
    $conversationModel->markRead($conversation_id, $role, $my_read_id);
}

$messageModel = new Message($pdo);
$messages = $messageModel->getNewMessages($conversation_id, $after_id);
$other_status = $conversationModel->getOtherStatus($conversation_id, $role);

echo json_encode([
    "messages" => $messages,
    "other_typing" => $other_status['other_typing'],
    "other_read_id" => $other_status['other_read_id'],
]);
