<?php
require_once __DIR__ . '/includes/auth_check.php';

switch ($_SESSION['role']) {
    case 'customer':
        header("Location: /cafes/discover.php");
        break;
    case 'seller':
        header("Location: /queue/index.php");
        break;
    case 'admin':
        header("Location: /admin/index.php");
        break;
    default:
        header("Location: /auth/login.php");
}
exit;
