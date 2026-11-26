<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';
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
<h1 class="text-xl font-black text-stone-900">Cafés</h1>
<p class="mt-0.5 text-sm text-stone-500"><?= count($cafes) ?> café profiles.</p>

<div class="mt-5 overflow-hidden rounded-2xl border border-stone-200 bg-white">
  <div class="max-h-[70vh] overflow-auto">
    <table class="w-full min-w-[640px] text-left text-sm">
        <thead class="sticky top-0 z-10 border-b border-stone-200 bg-cream text-[11px] font-bold uppercase tracking-wide text-stone-400">
            <tr>
                <th class="px-4 py-3">Café</th>
                <th class="hidden px-4 py-3 md:table-cell">Address</th>
                <th class="px-4 py-3">Rating</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-100">
            <?php foreach ($cafes as $c): ?>
                <tr class="hover:bg-cream/60">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <?php render_avatar($c['logo_url'] ?? null, $c['cafe_name'], 'w-8 h-8'); ?>
                            <a href="/cafes/view.php?id=<?= (int) $c['cafe_id'] ?>" class="font-bold text-stone-900 hover:text-accent"><?= htmlspecialchars($c['cafe_name'], ENT_QUOTES, 'UTF-8') ?></a>
                        </div>
                    </td>
                    <td class="hidden px-4 py-3 text-stone-500 md:table-cell"><?= htmlspecialchars($c['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3">
                        <?php if (!empty($c['rating_count'])): ?>
                            <span class="flex items-center gap-1 font-bold text-stone-700"><?php icon('star-solid', 'w-3.5 h-3.5 text-amber-400'); ?><?= number_format((float) $c['rating_avg'], 1) ?> <span class="font-normal text-stone-400">(<?= (int) $c['rating_count'] ?>)</span></span>
                        <?php else: ?>
                            <span class="text-stone-400">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <?php if ($c['is_verified']): ?>
                            <span class="flex w-fit items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-bold uppercase text-green-700"><?php icon('check-badge-solid', 'w-3 h-3'); ?>Verified</span>
                        <?php else: ?>
                            <span class="rounded-full bg-stone-100 px-2 py-0.5 text-[10px] font-bold uppercase text-stone-500">Unverified</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <form method="post" class="inline" data-confirm="<?= $c['is_verified'] ? 'Remove verification from' : 'Verify' ?> this café?">
                            <input type="hidden" name="cafe_id" value="<?= (int) $c['cafe_id'] ?>">
                            <input type="hidden" name="action" value="<?= $c['is_verified'] ? 'unverify' : 'verify' ?>">
                            <button type="submit" class="rounded-full border px-3 py-1 text-[11px] font-bold <?= $c['is_verified'] ? 'border-stone-300 text-stone-500 hover:bg-stone-50' : 'border-accent text-accent hover:bg-amber-50' ?>">
                                <?= $c['is_verified'] ? 'Unverify' : 'Verify' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
