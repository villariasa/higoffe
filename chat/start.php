<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('customer');

$cafe_id = (int) ($_POST['cafe_id'] ?? $_GET['cafe_id'] ?? 0);

$pdo = (new Database())->connect();
$conversationModel = new Conversation($pdo);
$conversation_id = $conversationModel->startOrReuse((int) $_SESSION['user_id'], $cafe_id);

// Scripted auto-reply — fires once, only for a genuinely empty
// conversation (brand new or reused-but-never-messaged), so a customer
// never lands in silence while waiting for a seller to claim it.
$messageModel = new Message($pdo);
if ($messageModel->countForConversation($conversation_id) === 0) {
    $messageModel->createSystem($conversation_id, (int) $_SESSION['user_id'], "Thanks for reaching out! A barista will be with you shortly.");
}

header("Location: /chat/conversation.php?id={$conversation_id}");
exit;
