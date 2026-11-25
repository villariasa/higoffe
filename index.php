<?php
require_once __DIR__ . '/includes/auth_check.php';

// Feed is the primary landing surface for both roles — social-first
// per the pivot; ordering/queue stay a tap away, not the front door.
switch ($_SESSION['role']) {
    case 'customer':
    case 'seller':
        header("Location: /feed/index.php");
        break;
    case 'admin':
        header("Location: /admin/index.php");
        break;
    default:
        header("Location: /auth/login.php");
}
exit;
