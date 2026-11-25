<?php
// Admin-only: test the SMTP config and show the raw error if it fails.
// Access: /admin/test_email.php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Mailer.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$pdo    = (new Database())->connect();
$result = null;
$error  = null;
$to     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = (string) trim($_POST['to'] ?? '');

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        // Temporarily redirect error_log output so we can capture it.
        $log_file = sys_get_temp_dir() . '/higoffe_mailer_test.log';
        ini_set('error_log', $log_file);
        @file_put_contents($log_file, ''); // clear previous

        $mailer = new Mailer($pdo);
        $ok = $mailer->send(
            $to,
            'Higoffe SMTP test',
            '<p>This is a test email from your Higoffe app. If you see this, SMTP is working! ☕</p>'
        );

        $log = @file_get_contents($log_file) ?: '';
        ini_restore('error_log');

        if ($ok) {
            $result = 'success';
        } else {
            $result = 'fail';
            $error  = $log ?: 'send() returned false but no error was logged. Check Render logs.';
        }
    }
}

// Also show current resolved config (passwords redacted).
require_once __DIR__ . '/../models/Settings.php';
$settings = new Settings($pdo);
$has_gas    = (bool) ($settings->get('gas_webhook_url') ?: getenv('GAS_WEBHOOK_URL'));
$has_brevo  = (bool) ($settings->get('brevo_api_key') ?: getenv('BREVO_API_KEY'));
$has_resend = (bool) ($settings->get('resend_api_key') ?: getenv('RESEND_API_KEY'));
$config = [
    'transport'       => $has_gas ? 'Google Apps Script (Gmail) ✅' : ($has_brevo ? 'Brevo HTTPS API ✅' : ($has_resend ? 'Resend HTTPS API ✅' : 'SMTP (ports 587/465 blocked on Render free tier)')),
    'gas_webhook_url' => $settings->get('gas_webhook_url') ?: getenv('GAS_WEBHOOK_URL') ?: '(not set)',
    'brevo_api_key'   => $settings->get('brevo_api_key') ? '•••••••• (DB)' : (getenv('BREVO_API_KEY') ? '•••••••• (env)' : '(not set)'),
    'resend_api_key'  => $settings->get('resend_api_key') ? '•••••••• (DB)' : (getenv('RESEND_API_KEY') ? '•••••••• (env)' : '(not set)'),
    'smtp_host'       => $settings->get('smtp_host') ?: getenv('SMTP_HOST') ?: '(not set)',
    'smtp_port'       => $settings->get('smtp_port') ?: getenv('SMTP_PORT') ?: '(not set)',
    'smtp_encryption' => $settings->get('smtp_encryption') ?: getenv('SMTP_ENCRYPTION') ?: '(not set)',
    'smtp_username'   => $settings->get('smtp_username') ?: getenv('SMTP_USERNAME') ?: '(not set)',
    'smtp_password'   => $settings->get('smtp_password') ? '••••••••' : (getenv('SMTP_PASSWORD') ? '••••••••' : '(NOT SET ❌)'),
    'smtp_from_email' => $settings->get('smtp_from_email') ?: getenv('SMTP_FROM_EMAIL') ?: '(not set)',
    'APP_URL'         => getenv('APP_URL') ?: '(NOT SET — verify links will point to localhost ❌)',
];

$page_title = 'Test SMTP email';
$back_url   = '/admin/settings.php';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-xl space-y-4 px-4 py-6">

    <!-- Current config -->
    <div class="rounded-2xl bg-white p-4 text-sm">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">Resolved SMTP config</p>
        <table class="w-full text-left">
            <?php foreach ($config as $k => $v): ?>
                <tr class="border-b border-stone-100 last:border-0">
                    <td class="py-1.5 pr-4 font-mono text-[11px] text-stone-500"><?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="py-1.5 font-semibold <?= str_contains($v, '❌') ? 'text-red-600' : 'text-stone-800' ?>"><?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Result -->
    <?php if ($result === 'success'): ?>
        <div class="rounded-2xl bg-green-50 p-4 text-sm font-semibold text-green-700">
            ✅ Email sent successfully to <?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>! Check your inbox.
        </div>
    <?php elseif ($result === 'fail'): ?>
        <div class="rounded-2xl bg-red-50 p-4 text-sm text-red-700">
            <p class="font-bold">❌ Send failed</p>
            <pre class="mt-2 overflow-x-auto whitespace-pre-wrap font-mono text-[11px]"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></pre>
        </div>
    <?php endif; ?>
    <?php if ($error && $result !== 'fail'): ?>
        <div class="rounded-2xl bg-red-50 p-4 text-sm text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <!-- Send form -->
    <div class="rounded-2xl bg-white p-4">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">Send a test email</p>
        <form method="post" class="flex gap-2">
            <input type="email" name="to" required value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="recipient@example.com"
                   class="flex-1 rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
            <button type="submit" class="rounded-xl bg-accent px-4 py-2.5 text-sm font-bold text-white active:opacity-80">Send test</button>
        </form>
        <p class="mt-2 text-[11px] text-stone-400">Send to your own email to confirm delivery. The error message above will tell you exactly what went wrong if it fails.</p>
    </div>

    <!-- Common fixes -->
    <div class="rounded-2xl bg-cream p-4 text-sm text-stone-600">
        <p class="mb-2 font-bold text-stone-800">Common Gmail SMTP issues</p>
        <ul class="list-disc space-y-1 pl-4 text-[12px]">
            <li><strong>Wrong password</strong> — Must be a 16-char <em>App Password</em>, not your Gmail login password. Generate at: Google Account → Security → 2-Step Verification → App passwords.</li>
            <li><strong>2FA not enabled</strong> — App Passwords require 2-Step Verification to be ON in your Google account.</li>
            <li><strong>Port blocked</strong> — Render allows port 587. If you see "connection refused", try port 465 with SSL encryption instead.</li>
            <li><strong>APP_URL not set</strong> — Without this env var, verification links point to <code>localhost</code> and clicking them does nothing. Set it to your Render URL.</li>
            <li><strong>"Username and Password not accepted"</strong> — The App Password was revoked or typed wrong. Regenerate it.</li>
        </ul>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
