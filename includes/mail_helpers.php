<?php
// Shared by auth/register_customer.php, register_seller.php, and
// resend_verification.php — generates a fresh verification token, stores
// it, and emails the verification link. One place, not copy-pasted three
// times across the auth pages.
require_once __DIR__ . '/../config/Mailer.php';

function send_verification_email(PDO $pdo, User $userModel, int $user_id, string $full_name, string $email): bool {
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + 24 * 60 * 60);
    $userModel->setVerificationToken($user_id, $token, $expires_at);

    // Never build this link from $_SERVER['HTTP_HOST'] — that header is
    // attacker-controlled (a forged Host header would send the victim a
    // real token wrapped in an attacker's domain). Use a fixed app origin
    // instead: set APP_URL as an environment variable in production, or
    // edit the fallback below for local development.
    $base_url = getenv('APP_URL') ?: 'http://localhost';
    $verify_url = rtrim($base_url, '/') . '/auth/verify.php?token=' . urlencode($token);

    $safe_name = htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8');
    $safe_url = htmlspecialchars($verify_url, ENT_QUOTES, 'UTF-8');

    $html = "<p>Hi {$safe_name},</p>"
          . "<p>Confirm your Higoffe account by clicking the link below:</p>"
          . "<p><a href=\"{$safe_url}\">{$safe_url}</a></p>"
          . "<p>This link expires in 24 hours. If you didn't create this account, you can ignore this email.</p>";

    $mailer = new Mailer($pdo);
    return $mailer->send($email, 'Verify your Higoffe account', $html);
}
