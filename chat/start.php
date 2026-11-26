<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Notification.php';
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

    // Nothing else pings the seller that a new inquiry landed in their
    // waiting queue — without this they'd only find out by sitting on
    // /queue/index.php watching its live counter.
    $cafe = (new Cafe($pdo))->getById($cafe_id);
    if ($cafe) {
        (new Notification($pdo))->create((int) $cafe['owner_user_id'], $_SESSION['full_name'] . ' sent a new inquiry — check your queue to reply.', '/queue/index.php');
    }
}

header("Location: /chat/conversation.php?id={$conversation_id}");
exit;
