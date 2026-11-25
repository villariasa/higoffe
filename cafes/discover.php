<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../includes/auth_check.php';

$keyword = (string) trim($_GET['q'] ?? '');

$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);
$cafes = $keyword !== '' ? $cafeModel->search($keyword) : $cafeModel->getAll();

require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-bold mb-4">Discover cafés</h1>

<form method="get" class="mb-6">
    <input type="text" name="q" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search café name..." class="w-full max-w-md border border-stone-300 rounded p-2">
    <button type="submit" class="bg-amber-700 text-white rounded px-4 py-2 ml-2">Search</button>
</form>

<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($cafes as $cafe): ?>
        <a href="/cafes/view.php?id=<?= (int) $cafe['cafe_id'] ?>" class="block bg-white border border-stone-200 rounded-lg overflow-hidden hover:border-amber-400">
            <?php if (!empty($cafe['cover_url'])): ?>
                <img src="<?= htmlspecialchars($cafe['cover_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-full h-28 object-cover">
            <?php endif; ?>
            <div class="p-3">
                <p class="font-semibold"><?= htmlspecialchars($cafe['cafe_name'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-xs text-stone-500 truncate"><?= htmlspecialchars($cafe['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </a>
    <?php endforeach; ?>
    <?php if (empty($cafes)): ?>
        <p class="text-stone-500 text-sm">No cafés found.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
