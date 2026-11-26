<?php
// AJAX endpoint for the notification bell dropdown (includes/notif_drawer.js)
// — latest notifications as JSON. Opening the drawer clears the unread
// badge, same as visiting the full notifications.php page.
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Notification.php';
require_once __DIR__ . '/includes/auth_check.php';

$pdo = (new Database())->connect();
$notificationModel = new Notification($pdo);

$notifications = $notificationModel->getForUser((int) $_SESSION['user_id'], 8);
$notificationModel->markAllRead((int) $_SESSION['user_id']);

header("Content-Type: application/json");
echo json_encode(["notifications" => $notifications]);
