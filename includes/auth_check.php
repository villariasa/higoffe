<?php
// Include at the very top of every page that requires a logged-in user:
//   require_once __DIR__ . '/../includes/auth_check.php';
// For a role-restricted page, call require_role() afterwards:
//   require_role('admin');          // single role
//   require_role(['seller','admin']); // multiple roles allowed

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    // Assumes the app is deployed at the web root. If deployed in a
    // subfolder, adjust this path (and the ones in header.php) to match.
    header("Location: /auth/login.php");
    exit;
}

function require_role($allowed_roles) {
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    if (!in_array($_SESSION['role'], $allowed_roles, true)) {
        header("Location: /index.php");
        exit;
    }
}
