<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../includes/auth_check.php';

$target_type = (string) ($_POST['target_type'] ?? '');
$target_id   = (int) ($_POST['target_id'] ?? 0);
$reason      = (string) trim($_POST['reason'] ?? '');
$return_to   = (string) ($_POST['return_to'] ?? '/index.php');

if (in_array($target_type, ['post', 'cafe'], true) && $target_id > 0) {
    $pdo = (new Database())->connect();
    $reportModel = new Report($pdo);
    $reportModel->create((int) $_SESSION['user_id'], $target_type, $target_id, $reason);
}

// Only allow redirecting back within the app — no open-redirect.
if (!preg_match('#^/[a-zA-Z0-9/_.?=&-]*$#', $return_to)) {
    $return_to = '/index.php';
}
header("Location: {$return_to}");
exit;
