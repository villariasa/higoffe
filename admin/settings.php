<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';
require_role('admin');

$pdo = (new Database())->connect();
$settingsModel = new Settings($pdo);

$error = '';
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brevo_api_key   = (string) trim($_POST['brevo_api_key'] ?? '');
    $clear_brevo     = isset($_POST['clear_brevo_key']);
    $resend_api_key  = (string) trim($_POST['resend_api_key'] ?? '');
    $clear_resend    = isset($_POST['clear_resend_key']);
    $smtp_host       = (string) trim($_POST['smtp_host'] ?? '');
    $smtp_port       = (string) trim($_POST['smtp_port'] ?? '');
    $smtp_username   = (string) trim($_POST['smtp_username'] ?? '');
    $smtp_password   = (string) ($_POST['smtp_password'] ?? '');
    $smtp_from_email = (string) trim($_POST['smtp_from_email'] ?? '');
    $smtp_from_name  = (string) trim($_POST['smtp_from_name'] ?? '');
    $smtp_encryption = ($_POST['smtp_encryption'] ?? '') === 'ssl' ? 'ssl' : 'tls';

    $existing_brevo  = (string) ($settingsModel->get('brevo_api_key') ?? '');
    $existing_resend = (string) ($settingsModel->get('resend_api_key') ?? '');
    $has_brevo  = !$clear_brevo  && ($brevo_api_key !== ''  || $existing_brevo !== '');
    $has_resend = !$clear_resend && ($resend_api_key !== '' || $existing_resend !== '');

    if ($has_brevo || $has_resend) {
        if ($clear_brevo) {
            $settingsModel->set('brevo_api_key', '');
        } elseif ($brevo_api_key !== '') {
            $settingsModel->set('brevo_api_key', $brevo_api_key);
        }
        if ($clear_resend) {
            $settingsModel->set('resend_api_key', '');
        } elseif ($resend_api_key !== '') {
            $settingsModel->set('resend_api_key', $resend_api_key);
        }
        $settingsModel->set('smtp_from_email', $smtp_from_email ?: 'onboarding@resend.dev');
        $settingsModel->set('smtp_from_name', $smtp_from_name ?: 'Higoffe');
        if ($smtp_host !== '')     $settingsModel->set('smtp_host', $smtp_host);
        if ($smtp_port !== '')     $settingsModel->set('smtp_port', $smtp_port);
        if ($smtp_username !== '') $settingsModel->set('smtp_username', $smtp_username);
        if ($smtp_password !== '') $settingsModel->set('smtp_password', $smtp_password);
        $settingsModel->set('smtp_encryption', $smtp_encryption);
        $saved = true;
    } elseif ($smtp_host === '' || $smtp_port === '' || $smtp_username === '' || $smtp_from_email === '') {
        $error = 'Enter a Brevo/Resend API key, or fill in SMTP host, port, username, and from-email.';
    } else {
        if ($clear_brevo)  $settingsModel->set('brevo_api_key', '');
        if ($clear_resend) $settingsModel->set('resend_api_key', '');
        $settingsModel->set('smtp_host', $smtp_host);
        $settingsModel->set('smtp_port', $smtp_port);
        $settingsModel->set('smtp_username', $smtp_username);
        // Only overwrite the stored password if a new one was actually
        // typed — the field renders blank, so empty means "keep as-is".
        if ($smtp_password !== '') {
            $settingsModel->set('smtp_password', $smtp_password);
        }
        $settingsModel->set('smtp_from_email', $smtp_from_email);
        $settingsModel->set('smtp_from_name', $smtp_from_name);
        $settingsModel->set('smtp_encryption', $smtp_encryption);
        $saved = true;
    }
}

$current = $settingsModel->getAll();
$field = 'w-full rounded-xl border border-stone-200 bg-white p-2.5 text-sm focus:border-coffee focus:outline-none';

require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-black text-stone-900">Email settings</h1>
<p class="mt-0.5 max-w-xl text-sm text-stone-500">
    Controls where account-verification emails are sent from. On Render's free tier, outbound SMTP ports are blocked — use <strong>Resend</strong> (HTTPS API) below. Values saved here take priority over environment variables.
</p>

<div class="mt-5 max-w-xl rounded-2xl border border-stone-200 bg-white p-5">
    <?php if ($saved): ?>
        <script>document.addEventListener('DOMContentLoaded', function () { window.higoffeToast('Settings saved.', 'success'); });</script>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <script>document.addEventListener('DOMContentLoaded', function () { window.higoffeToast(<?= json_encode($error, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, 'error'); });</script>
    <?php endif; ?>

    <form method="post" class="space-y-4">
        <!-- Brevo HTTP API (recommended when using Gmail without a custom domain) -->
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3.5 space-y-2">
            <label class="block text-[11px] font-bold uppercase tracking-wide text-emerald-800">
                Brevo API Key (Best for Gmail — no custom domain needed)
                <?php if (!empty($current['brevo_api_key'])): ?>
                    <span class="font-normal normal-case text-green-700">— Active ✅ (leave blank to keep)</span>
                <?php endif; ?>
            </label>
            <input type="password" name="brevo_api_key" placeholder="<?= !empty($current['brevo_api_key']) ? 'xkeysib-•••••••• (already set)' : 'xkeysib-xxxxxxxxx' ?>" class="<?= $field ?>">
            <p class="text-[11px] text-stone-500">
                Free 300 emails/day at <a href="https://brevo.com" target="_blank" rel="noopener" class="font-semibold text-accent underline">brevo.com</a>. Verify your Gmail as a sender in Brevo, then set <strong>From email</strong> below to your Gmail.
            </p>
            <?php if (!empty($current['brevo_api_key'])): ?>
                <label class="flex items-center gap-1.5 text-[11px] text-red-600 cursor-pointer">
                    <input type="checkbox" name="clear_brevo_key" value="1" class="accent-red-600">
                    Clear Brevo API key
                </label>
            <?php endif; ?>
        </div>

        <!-- Resend HTTP API -->
        <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3.5 space-y-2">
            <div class="flex items-center justify-between">
                <label class="block text-[11px] font-bold uppercase tracking-wide text-amber-800">
                    Resend API Key (Requires verified custom domain for other recipients)
                    <?php if (!empty($current['resend_api_key'])): ?>
                        <span class="font-normal normal-case text-green-700">— Set ✅ (leave blank to keep)</span>
                    <?php endif; ?>
                </label>
            </div>
            <input type="password" name="resend_api_key" placeholder="<?= !empty($current['resend_api_key']) ? 're_•••••••• (already set)' : 're_xxxxxxxxx' ?>" class="<?= $field ?>">
            <p class="text-[11px] text-stone-500">
                Free at <a href="https://resend.com" target="_blank" rel="noopener" class="font-semibold text-accent underline">resend.com</a>. Set From Email to <code>onboarding@resend.dev</code> (for testing to your own email) or an address on your verified domain.
            </p>
            <?php if (!empty($current['resend_api_key'])): ?>
                <label class="flex items-center gap-1.5 text-[11px] text-red-600 cursor-pointer">
                    <input type="checkbox" name="clear_resend_key" value="1" class="accent-red-600">
                    Clear Resend API key and fall back to SMTP
                </label>
            <?php endif; ?>
        </div>

        <div class="grid gap-3.5 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-stone-400">From email</label>
                <input type="email" name="smtp_from_email" value="<?= htmlspecialchars($current['smtp_from_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="onboarding@resend.dev" class="<?= $field ?>">
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-stone-400">From name</label>
                <input type="text" name="smtp_from_name" value="<?= htmlspecialchars($current['smtp_from_name'] ?? 'Higoffe', ENT_QUOTES, 'UTF-8') ?>" class="<?= $field ?>">
            </div>
        </div>

        <details class="rounded-xl border border-stone-200 p-3.5" <?= empty($current['resend_api_key']) ? 'open' : '' ?>>
            <summary class="cursor-pointer text-[11px] font-bold uppercase tracking-wide text-stone-500">SMTP Fallback (blocked on Render free tier)</summary>
            <div class="mt-3 space-y-3.5">
                <div class="grid gap-3.5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-stone-400">SMTP host</label>
                        <input type="text" name="smtp_host" value="<?= htmlspecialchars($current['smtp_host'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="smtp.gmail.com" class="<?= $field ?>">
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-stone-400">Port</label>
                        <input type="number" name="smtp_port" value="<?= htmlspecialchars($current['smtp_port'] ?? '587', ENT_QUOTES, 'UTF-8') ?>" class="<?= $field ?>">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-stone-400">Encryption</label>
                    <select name="smtp_encryption" class="<?= $field ?>">
                        <option value="tls" <?= ($current['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (port 587)</option>
                        <option value="ssl" <?= ($current['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (port 465)</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-stone-400">Username</label>
                    <input type="text" name="smtp_username" value="<?= htmlspecialchars($current['smtp_username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="<?= $field ?>">
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-stone-400">
                        Password
                        <?php if (!empty($current['smtp_password'])): ?>
                            <span class="font-normal normal-case text-stone-400">(already set — leave blank to keep it)</span>
                        <?php endif; ?>
                    </label>
                    <input type="password" name="smtp_password" placeholder="<?= !empty($current['smtp_password']) ? '••••••••' : '' ?>" class="<?= $field ?>">
                </div>
            </div>
        </details>

        <button type="submit" class="w-full rounded-xl bg-accent py-2.5 text-sm font-bold text-white hover:opacity-90">Save</button>
    </form>
    <a href="/admin/test_email.php" class="mt-3 block w-full rounded-xl border border-coffee/30 py-2.5 text-center text-sm font-bold text-coffee hover:bg-cream">Test email sending →</a>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
