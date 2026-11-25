<?php
// Reusable password input with an eye/eye-slash visibility toggle.
// Requires includes/password_toggle.js once per page.
require_once __DIR__ . '/icons.php';

function render_password_field(string $name, string $label): void {
    ?>
    <div>
        <label class="mb-1 block text-xs font-semibold text-stone-500"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
        <div class="password-field relative">
            <input type="password" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required
                   class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 pr-11 text-sm focus:border-coffee focus:outline-none">
            <button type="button" class="password-toggle-btn absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-coffee" aria-label="Show password">
                <span class="eye-show"><?php icon('eye', 'w-5 h-5'); ?></span>
                <span class="eye-hide hidden"><?php icon('eye-slash', 'w-5 h-5'); ?></span>
            </button>
        </div>
    </div>
    <?php
}
