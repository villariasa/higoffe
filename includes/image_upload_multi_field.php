<?php
// Multi-photo upload field (Facebook/Instagram-style multi-image post).
// Each picked file uploads straight to the Cloudflare Worker (same path
// as the single-image field); successful uploads collect into one hidden
// JSON array input the server parses as $_POST['image_urls'].
// No crop step here — unlike a single framed asset (avatar/logo/cover),
// a multi-photo post is meant to carry photos as-shot, same as FB/IG.
function render_image_upload_multi_field(array $auth, string $name, int $max_mb = 5, int $max_count = 10): void {
    require_once __DIR__ . '/icons.php';
    ?>
    <div class="image-upload-multi-field"
         data-worker-url="<?= htmlspecialchars($auth['worker_url'], ENT_QUOTES, 'UTF-8') ?>"
         data-token="<?= htmlspecialchars((string) $auth['token'], ENT_QUOTES, 'UTF-8') ?>"
         data-expiry="<?= (int) $auth['expiry'] ?>"
         data-user-id="<?= (int) $auth['user_id'] ?>"
         data-max-mb="<?= $max_mb ?>"
         data-max-count="<?= $max_count ?>"
         data-scope="<?= htmlspecialchars($auth['scope'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="thumb-grid grid grid-cols-4 gap-2"></div>
        <label class="file-trigger mt-2.5 flex items-center justify-center gap-2 rounded-xl border border-dashed border-stone-300 bg-cream py-3 text-xs font-semibold text-stone-600 cursor-pointer hover:border-accent hover:text-accent">
            <?php icon('camera', 'w-4 h-4'); ?> Add photos
            <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple class="file-input hidden">
        </label>
        <p class="status-text mt-1 text-[11px] text-stone-400">Up to <?= $max_count ?> photos, <?= $max_mb ?>MB each.</p>
        <input type="hidden" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" value="[]" class="urls-input">
    </div>
    <?php
}
