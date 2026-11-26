<?php
// AJAX polling endpoint for browser push notifications (includes/push_notify.js).
// Returns any notifications newer than ?after_id for the logged-in user —
// works everywhere in the app, not just while sitting on one chat page,
// which is the gap that made sellers think they had to refresh to see a
// new order/inquiry.
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Notification.php';
require_once __DIR__ . '/includes/auth_check.php';

$after_id = (int) ($_GET['after_id'] ?? 0);

$pdo = (new Database())->connect();
$notificationModel = new Notification($pdo);

header("Content-Type: application/json");

if ($after_id <= 0) {
    // First call from a fresh tab — just sync to "now" instead of
    // replaying the user's entire notification history as push alerts.
    echo json_encode(["notifications" => [], "latest_id" => $notificationModel->getLatestId((int) $_SESSION['user_id'])]);
    exit;
}

$rows = $notificationModel->getNewForUser((int) $_SESSION['user_id'], $after_id);
$latest_id = $after_id;
foreach ($rows as $row) {
    $latest_id = max($latest_id, (int) $row['notification_id']);
}

echo json_encode(["notifications" => $rows, "latest_id" => $latest_id]);
