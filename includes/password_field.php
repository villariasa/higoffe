<?php
// Reusable password input with a Show/Hide toggle. Requires
// includes/password_toggle.js to be included once per page.
function render_password_field(string $name, string $label): void {
    ?>
    <div>
        <label class="block text-sm text-stone-600 mb-1"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
        <div class="password-field relative">
            <input type="password" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required
                   class="w-full border border-stone-300 rounded p-2 pr-16">
            <button type="button" class="password-toggle-btn absolute right-2 top-1/2 -translate-y-1/2 text-xs font-semibold text-amber-700" aria-label="Show password">Show</button>
        </div>
    </div>
    <?php
}
