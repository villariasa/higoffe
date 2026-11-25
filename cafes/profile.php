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

require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-xl mx-auto bg-white border border-stone-200 rounded-lg p-6">
    <h1 class="text-xl font-bold mb-4"><?= $cafe ? 'Edit your café' : 'Set up your café' ?></h1>

    <?php if ($saved): ?>
        <p class="bg-green-50 text-green-700 text-sm rounded p-3 mb-4">Saved.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="bg-red-50 text-red-700 text-sm rounded p-3 mb-4"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-3">
        <div>
            <label class="block text-sm text-stone-600 mb-1">Café name</label>
            <input type="text" name="cafe_name" required value="<?= htmlspecialchars($cafe['cafe_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="w-full border border-stone-300 rounded p-2">
        </div>
        <div>
            <label class="block text-sm text-stone-600 mb-1">Description</label>
            <textarea name="description" rows="3" class="w-full border border-stone-300 rounded p-2"><?= htmlspecialchars($cafe['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div>
            <label class="block text-sm text-stone-600 mb-1">Address</label>
            <input type="text" name="address" value="<?= htmlspecialchars($cafe['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="w-full border border-stone-300 rounded p-2">
        </div>
        <?php render_image_upload_field($upload_auth, 'logo_url', 'Logo', $cafe['logo_url'] ?? ''); ?>
        <?php render_image_upload_field($upload_auth, 'cover_url', 'Cover photo', $cafe['cover_url'] ?? ''); ?>
        <button type="submit" class="w-full bg-amber-700 text-white rounded p-2 font-semibold">Save</button>
    </form>

    <?php if ($cafe): ?>
        <p class="text-sm text-stone-500 mt-4">
            <a href="/cafes/view.php?id=<?= (int) $cafe['cafe_id'] ?>" class="text-amber-700 underline">View your public café page</a>
        </p>
    <?php endif; ?>
</div>
<script src="/includes/image_upload.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
