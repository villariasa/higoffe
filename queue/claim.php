<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('seller');

$conversation_id = (int) ($_POST['conversation_id'] ?? 0);

$pdo = (new Database())->connect();
$conversationModel = new Conversation($pdo);
$success = $conversationModel->claim($conversation_id, (int) $_SESSION['user_id']);

if ($success) {
    $messageModel = new Message($pdo);
    $seller_name = (string) ($_SESSION['full_name'] ?? 'the café team');
    $messageModel->createSystem($conversation_id, (int) $_SESSION['user_id'], "You're now chatting with {$seller_name}.");
}

header("Content-Type: application/json");
echo json_encode(["success" => $success]);
