<?php
// Copy this file to smtp_credentials.php (same folder) and fill in your
// real values, OR set the equivalent SMTP_* environment variables on your
// host (Render, etc.) — Mailer.php checks environment variables first,
// this file second. smtp_credentials.php is gitignored — never commit
// real credentials, only this example template.
//
// Gmail SMTP: host smtp.gmail.com, port 587, encryption 'tls'. Requires an
// "App Password" (Google Account -> Security -> 2-Step Verification ->
// App Passwords) — your normal Google password will NOT work here.
//
// For local testing without emailing real inboxes, use Mailtrap.io's free
// sandbox SMTP instead (it gives you its own host/port/username/password).

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'yourappemail@gmail.com');
define('SMTP_PASSWORD', 'your-16-char-app-password');
define('SMTP_FROM_EMAIL', 'yourappemail@gmail.com');
define('SMTP_FROM_NAME', 'Higoffe');
define('SMTP_ENCRYPTION', 'tls'); // 'tls' for port 587, 'ssl' for port 465
