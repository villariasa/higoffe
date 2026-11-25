<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../models/Conversation.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('seller');

$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);
$cafe = $cafeModel->getByOwner((int) $_SESSION['user_id']);

header("Content-Type: application/json");

if (!$cafe) {
    echo json_encode(["waiting_count" => 0]);
    exit;
}

$conversationModel = new Conversation($pdo);
echo json_encode(["waiting_count" => $conversationModel->countWaiting((int) $cafe['cafe_id'])]);
