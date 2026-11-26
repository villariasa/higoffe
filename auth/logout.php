<?php
require_once __DIR__ . '/../includes/session_bootstrap.php';
higoffe_session_start();
session_unset();
session_destroy();
header("Location: /auth/login.php");
exit;
