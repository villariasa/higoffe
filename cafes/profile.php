<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Uploads.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/image_upload_field.php';
require_role('seller');

$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);
$cafe = $cafeModel->getByOwner((int) $_SESSION['user_id']);

$error = '';
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cafe_name   = (string) trim($_POST['cafe_name'] ?? '');
    $description = (string) trim($_POST['description'] ?? '');
    $address     = (string) trim($_POST['address'] ?? '');
    $logo_url    = (string) trim($_POST['logo_url'] ?? '');
    $cover_url   = (string) trim($_POST['cover_url'] ?? '');

    if ($cafe_name === '') {
        $error = 'Café name is required.';
    } elseif (!Uploads::isAllowedMediaUrl($logo_url) || !Uploads::isAllowedMediaUrl($cover_url)) {
        $error = 'Invalid image URL.';
    } elseif ($cafe) {
        $cafeModel->update((int) $cafe['cafe_id'], $cafe_name, $description, $address, $logo_url, $cover_url);
        $cafe = $cafeModel->getByOwner((int) $_SESSION['user_id']);
        $saved = true;
    } else {
        $cafe_id = $cafeModel->create((int) $_SESSION['user_id'], $cafe_name, $description, $address);
        $cafe = $cafeModel->getById((int) $cafe_id);
        $saved = true;
    }
}

$upload_auth = Uploads::issueToken((int) $_SESSION['user_id'], 'cafe');

$page_title = $cafe ? 'Edit your café' : 'Set up your café';
$back_url = '/queue/index.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="px-4 py-4">
    <?php if ($saved): ?>
        <p class="mb-3 rounded-xl bg-green-50 p-3 text-sm text-green-700">Saved.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="mb-3 rounded-xl bg-red-50 p-3 text-sm text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-3">
        <div class="space-y-3 rounded-2xl bg-white p-4">
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Café name</label>
                <input type="text" name="cafe_name" required value="<?= htmlspecialchars($cafe['cafe_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Description</label>
                <textarea name="description" rows="3" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none"><?= htmlspecialchars($cafe['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($cafe['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
            </div>
            <?php render_image_upload_field($upload_auth, 'logo_url', 'Logo', $cafe['logo_url'] ?? ''); ?>
            <?php render_image_upload_field($upload_auth, 'cover_url', 'Cover photo', $cafe['cover_url'] ?? ''); ?>
        </div>
        <button type="submit" class="w-full rounded-xl bg-accent py-3 text-sm font-bold text-white active:opacity-80">Save</button>
    </form>

    <?php if ($cafe): ?>
        <p class="mt-3 text-center text-sm">
            <a href="/cafes/view.php?id=<?= (int) $cafe['cafe_id'] ?>" class="font-semibold text-accent">View your public café page &rarr;</a>
        </p>
    <?php endif; ?>
</div>
<script src="/includes/image_upload.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
