<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cafe_id = (int) ($_POST['cafe_id'] ?? 0);
    $cafeModel->setVerified($cafe_id, ($_POST['action'] ?? '') === 'verify');
}

$cafes = $cafeModel->getAll();

require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-bold mb-4">Cafés</h1>

<div class="bg-white border border-stone-200 rounded-lg divide-y divide-stone-200">
    <?php foreach ($cafes as $c): ?>
        <div class="flex items-center justify-between p-4">
            <div>
                <p class="font-semibold"><?= htmlspecialchars($c['cafe_name'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-xs text-stone-500"><?= htmlspecialchars($c['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <form method="post">
                <input type="hidden" name="cafe_id" value="<?= (int) $c['cafe_id'] ?>">
                <input type="hidden" name="action" value="<?= $c['is_verified'] ? 'unverify' : 'verify' ?>">
                <button type="submit" class="text-sm <?= $c['is_verified'] ? 'text-stone-500' : 'text-amber-700' ?> underline">
                    <?= $c['is_verified'] ? 'Unverify' : 'Verify' ?>
                </button>
            </form>
        </div>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
