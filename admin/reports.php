<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/components.php';
require_role('admin');

$pdo = (new Database())->connect();
$reportModel = new Report($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportModel->resolve((int) ($_POST['report_id'] ?? 0));
}

$reports = $reportModel->getPending();

require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-black text-stone-900">Reports</h1>
<p class="mt-0.5 text-sm text-stone-500"><?= count($reports) ?> pending.</p>

<div class="mt-5 space-y-3 lg:max-w-3xl">
    <?php foreach ($reports as $r): ?>
        <div class="rounded-2xl border border-stone-200 bg-white p-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-500"><?php icon('flag', 'w-4 h-4'); ?></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-stone-900">
                        <?= htmlspecialchars(ucfirst($r['target_type']), ENT_QUOTES, 'UTF-8') ?> #<?= (int) $r['target_id'] ?>
                        <?php if ($r['target_type'] === 'post'): ?>
                            <a href="/feed/post.php?id=<?= (int) $r['target_id'] ?>" class="text-[11px] font-bold text-accent hover:underline">view &rarr;</a>
                        <?php elseif ($r['target_type'] === 'cafe'): ?>
                            <a href="/cafes/view.php?id=<?= (int) $r['target_id'] ?>" class="text-[11px] font-bold text-accent hover:underline">view &rarr;</a>
                        <?php endif; ?>
                    </p>
                    <p class="text-[11px] text-stone-400">Reported by <?= htmlspecialchars($r['reporter_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(time_ago($r['created_at']), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <form method="post" data-confirm="Mark this report resolved?">
                    <input type="hidden" name="report_id" value="<?= (int) $r['report_id'] ?>">
                    <button type="submit" class="rounded-full border border-green-300 px-3 py-1 text-[11px] font-bold text-green-700 hover:bg-green-50">Mark resolved</button>
                </form>
            </div>
            <p class="mt-2 text-sm text-stone-600"><?= htmlspecialchars($r['reason'] !== '' ? $r['reason'] : '(no reason given)', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php endforeach; ?>
    <?php if (empty($reports)): ?>
        <?php render_empty_state('check-circle', 'No pending reports', 'Flagged content will appear here for review.'); ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
