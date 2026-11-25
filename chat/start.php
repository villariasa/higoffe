<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('customer');

$cafe_id = (int) ($_POST['cafe_id'] ?? $_GET['cafe_id'] ?? 0);

$pdo = (new Database())->connect();
$conversationModel = new Conversation($pdo);
$conversation_id = $conversationModel->startOrReuse((int) $_SESSION['user_id'], $cafe_id);

header("Location: /chat/conversation.php?id={$conversation_id}");
exit;
