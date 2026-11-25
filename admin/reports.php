<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$pdo = (new Database())->connect();
$reportModel = new Report($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportModel->resolve((int) ($_POST['report_id'] ?? 0));
}

$reports = $reportModel->getPending();

require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-bold mb-4">Reports</h1>

<div class="space-y-3 max-w-xl">
    <?php foreach ($reports as $r): ?>
        <div class="bg-white border border-stone-200 rounded-lg p-4">
            <p class="font-semibold text-sm">
                <?= htmlspecialchars(ucfirst($r['target_type']), ENT_QUOTES, 'UTF-8') ?> #<?= (int) $r['target_id'] ?>
                reported by <?= htmlspecialchars($r['reporter_name'], ENT_QUOTES, 'UTF-8') ?>
            </p>
            <p class="text-sm text-stone-600 mt-1"><?= htmlspecialchars($r['reason'] !== '' ? $r['reason'] : '(no reason given)', ENT_QUOTES, 'UTF-8') ?></p>
            <form method="post" class="mt-2">
                <input type="hidden" name="report_id" value="<?= (int) $r['report_id'] ?>">
                <button type="submit" class="text-amber-700 underline text-sm">Mark resolved</button>
            </form>
        </div>
    <?php endforeach; ?>
    <?php if (empty($reports)): ?>
        <p class="text-stone-500 text-sm">No pending reports.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
