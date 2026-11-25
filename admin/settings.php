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
    $smtp_host       = (string) trim($_POST['smtp_host'] ?? '');
    $smtp_port       = (string) trim($_POST['smtp_port'] ?? '');
    $smtp_username   = (string) trim($_POST['smtp_username'] ?? '');
    $smtp_password   = (string) ($_POST['smtp_password'] ?? '');
    $smtp_from_email = (string) trim($_POST['smtp_from_email'] ?? '');
    $smtp_from_name  = (string) trim($_POST['smtp_from_name'] ?? '');
    $smtp_encryption = ($_POST['smtp_encryption'] ?? '') === 'ssl' ? 'ssl' : 'tls';

    if ($smtp_host === '' || $smtp_port === '' || $smtp_username === '' || $smtp_from_email === '') {
        $error = 'Host, port, username, and from-email are required.';
    } else {
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
<h1 class="text-xl font-black text-stone-900">SMTP settings</h1>
<p class="mt-0.5 max-w-xl text-sm text-stone-500">
    Controls where account-verification emails are sent from. Values saved here take priority
    over the SMTP_* environment variables / <code>config/smtp_credentials.php</code> fallback.
</p>

<div class="mt-5 max-w-xl rounded-2xl border border-stone-200 bg-white p-5">
    <?php if ($saved): ?>
        <p class="mb-4 flex items-center gap-2 rounded-xl bg-green-50 p-3 text-sm text-green-700"><?php icon('check-circle', 'w-4 h-4'); ?> Saved.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="mb-4 rounded-xl bg-red-50 p-3 text-sm text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-3.5">
        <div class="grid gap-3.5 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-stone-400">SMTP host</label>
                <input type="text" name="smtp_host" required value="<?= htmlspecialchars($current['smtp_host'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="smtp.gmail.com" class="<?= $field ?>">
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-stone-400">Port</label>
                <input type="number" name="smtp_port" required value="<?= htmlspecialchars($current['smtp_port'] ?? '587', ENT_QUOTES, 'UTF-8') ?>" class="<?= $field ?>">
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
            <input type="text" name="smtp_username" required value="<?= htmlspecialchars($current['smtp_username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="<?= $field ?>">
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
        <div class="grid gap-3.5 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-stone-400">From email</label>
                <input type="email" name="smtp_from_email" required value="<?= htmlspecialchars($current['smtp_from_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="<?= $field ?>">
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-stone-400">From name</label>
                <input type="text" name="smtp_from_name" value="<?= htmlspecialchars($current['smtp_from_name'] ?? 'Higoffe', ENT_QUOTES, 'UTF-8') ?>" class="<?= $field ?>">
            </div>
        </div>
        <button type="submit" class="w-full rounded-xl bg-accent py-2.5 text-sm font-bold text-white hover:opacity-90">Save</button>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
