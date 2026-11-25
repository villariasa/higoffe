<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../includes/auth_check.php';
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
        // typed — the field is always rendered blank below, so an empty
        // submit means "leave it as-is", not "clear it".
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

require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-lg mx-auto bg-white border border-stone-200 rounded-lg p-6">
    <h1 class="text-xl font-bold mb-4">SMTP settings</h1>
    <p class="text-sm text-stone-500 mb-4">
        Controls where account-verification emails are sent from. Saved here, these take
        priority over the SMTP_* environment variables / config/smtp_credentials.php fallback.
    </p>

    <?php if ($saved): ?>
        <p class="bg-green-50 text-green-700 text-sm rounded p-3 mb-4">Saved.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="bg-red-50 text-red-700 text-sm rounded p-3 mb-4"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-3">
        <div>
            <label class="block text-sm text-stone-600 mb-1">SMTP host</label>
            <input type="text" name="smtp_host" required value="<?= htmlspecialchars($current['smtp_host'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="smtp.gmail.com" class="w-full border border-stone-300 rounded p-2">
        </div>
        <div>
            <label class="block text-sm text-stone-600 mb-1">Port</label>
            <input type="number" name="smtp_port" required value="<?= htmlspecialchars($current['smtp_port'] ?? '587', ENT_QUOTES, 'UTF-8') ?>" class="w-full border border-stone-300 rounded p-2">
        </div>
        <div>
            <label class="block text-sm text-stone-600 mb-1">Encryption</label>
            <select name="smtp_encryption" class="w-full border border-stone-300 rounded p-2">
                <option value="tls" <?= ($current['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (port 587)</option>
                <option value="ssl" <?= ($current['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (port 465)</option>
            </select>
        </div>
        <div>
            <label class="block text-sm text-stone-600 mb-1">Username</label>
            <input type="text" name="smtp_username" required value="<?= htmlspecialchars($current['smtp_username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="w-full border border-stone-300 rounded p-2">
        </div>
        <div>
            <label class="block text-sm text-stone-600 mb-1">
                Password
                <?php if (!empty($current['smtp_password'])): ?>
                    <span class="text-xs text-stone-400">(already set — leave blank to keep it)</span>
                <?php endif; ?>
            </label>
            <input type="password" name="smtp_password" placeholder="<?= !empty($current['smtp_password']) ? '••••••••' : '' ?>" class="w-full border border-stone-300 rounded p-2">
        </div>
        <div>
            <label class="block text-sm text-stone-600 mb-1">From email</label>
            <input type="email" name="smtp_from_email" required value="<?= htmlspecialchars($current['smtp_from_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="w-full border border-stone-300 rounded p-2">
        </div>
        <div>
            <label class="block text-sm text-stone-600 mb-1">From name</label>
            <input type="text" name="smtp_from_name" value="<?= htmlspecialchars($current['smtp_from_name'] ?? 'Higoffe', ENT_QUOTES, 'UTF-8') ?>" class="w-full border border-stone-300 rounded p-2">
        </div>
        <button type="submit" class="w-full bg-amber-700 text-white rounded p-2 font-semibold">Save</button>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
