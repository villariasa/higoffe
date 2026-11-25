<?php
// Reusable upload field: a file input that uploads directly from the
// browser to the Cloudflare Worker (bypassing the PHP server entirely)
// and fills a hidden field with the resulting R2 URL. Every existing form
// (menu, posts, café profile) keeps accepting a plain URL string exactly
// as before — this only changes how that string gets populated.
// Requires includes/image_upload.js to be included once per page.
function render_image_upload_field(array $auth, string $name, string $label, string $current_url = ''): void {
    ?>
    <div class="image-upload-field"
         data-worker-url="<?= htmlspecialchars($auth['worker_url'], ENT_QUOTES, 'UTF-8') ?>"
         data-token="<?= htmlspecialchars((string) $auth['token'], ENT_QUOTES, 'UTF-8') ?>"
         data-expiry="<?= (int) $auth['expiry'] ?>"
         data-user-id="<?= (int) $auth['user_id'] ?>"
         data-scope="<?= htmlspecialchars($auth['scope'], ENT_QUOTES, 'UTF-8') ?>">
        <label class="block text-sm text-stone-600 mb-1"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
        <div class="flex items-center gap-3">
            <img
                src="<?= htmlspecialchars($current_url, ENT_QUOTES, 'UTF-8') ?>"
                alt=""
                class="preview-img w-20 h-20 object-cover rounded-xl border border-stone-200 bg-stone-50 <?= $current_url === '' ? 'hidden' : '' ?>"
            >
            <div>
                <label class="file-trigger inline-flex items-center gap-2 rounded-full border border-stone-300 bg-white px-4 py-2 text-xs font-semibold text-stone-700 cursor-pointer active:border-accent active:text-accent">
                    &#128247; Choose photo
                    <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="file-input hidden">
                </label>
                <p class="status-text text-xs text-stone-400 mt-1"></p>
            </div>
        </div>
        <input type="hidden" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($current_url, ENT_QUOTES, 'UTF-8') ?>" class="url-input">
    </div>
    <?php
}
