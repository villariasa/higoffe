<?php
// Reusable upload field: a file input that uploads directly from the
// browser to the Cloudflare Worker (bypassing the PHP server entirely)
// and fills a hidden field with the resulting R2 URL. Every existing form
// (menu, posts, café profile) keeps accepting a plain URL string exactly
// as before — this only changes how that string gets populated.
// Requires includes/image_upload.js to be included once per page.
// $max_mb mirrors the Worker's per-scope cap (avatar 2MB, product 3MB,
// cafe 4MB, default 5MB) — this is UX-only; the Worker enforces the real
// limit server-side regardless of what the client claims.
// $crop_aspect: null = free-form crop box, or a ratio like 1 (square) or
// 16/9 (cover photos). Set to false to skip the crop step entirely (e.g.
// payment-proof screenshots, which are evidence, not a presentational
// photo that benefits from framing).
function render_image_upload_field(array $auth, string $name, string $label, string $current_url = '', int $max_mb = 5, $crop_aspect = null): void {
    ?>
    <div class="image-upload-field"
         data-worker-url="<?= htmlspecialchars($auth['worker_url'], ENT_QUOTES, 'UTF-8') ?>"
         data-token="<?= htmlspecialchars((string) $auth['token'], ENT_QUOTES, 'UTF-8') ?>"
         data-expiry="<?= (int) $auth['expiry'] ?>"
         data-user-id="<?= (int) $auth['user_id'] ?>"
         data-max-mb="<?= $max_mb ?>"
         data-scope="<?= htmlspecialchars($auth['scope'], ENT_QUOTES, 'UTF-8') ?>"
         <?= $crop_aspect === false ? 'data-no-crop="1"' : '' ?>
         <?= ($crop_aspect !== false && $crop_aspect !== null) ? 'data-crop-aspect="' . htmlspecialchars((string) $crop_aspect, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
        <label class="block text-sm text-stone-600 mb-1"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
        <div class="flex items-center gap-3">
            <img
                src="<?= htmlspecialchars($current_url, ENT_QUOTES, 'UTF-8') ?>"
                alt=""
                class="preview-img w-20 h-20 object-cover rounded-xl border border-stone-200 bg-stone-50 <?= $current_url === '' ? 'hidden' : '' ?>"
            >
            <div>
                <?php require_once __DIR__ . '/icons.php'; ?>
                <label class="file-trigger inline-flex items-center gap-2 rounded-full border border-stone-300 bg-white px-4 py-2 text-xs font-semibold text-stone-700 cursor-pointer hover:border-accent hover:text-accent">
                    <?php icon('camera', 'w-4 h-4'); ?> Choose photo
                    <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="file-input hidden">
                </label>
                <p class="text-[10px] text-stone-400 mt-1">JPG / PNG / WEBP / GIF, up to <?= $max_mb ?>MB</p>
                <p class="status-text text-xs text-stone-400 mt-0.5"></p>
            </div>
        </div>
        <input type="hidden" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($current_url, ENT_QUOTES, 'UTF-8') ?>" class="url-input">
    </div>
    <?php
}
