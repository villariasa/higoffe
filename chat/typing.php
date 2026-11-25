<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../includes/auth_check.php';

$conversation_id = (int) ($_POST['conversation_id'] ?? 0);

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

if ($is_participant) {
    $conversationModel->setTyping($conversation_id, $role);
}

echo json_encode(["success" => (bool) $is_participant]);
