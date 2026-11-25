<?php
// Reusable password input with a Show/Hide toggle. Requires
// includes/password_toggle.js to be included once per page.
function render_password_field(string $name, string $label): void {
    ?>
    <div>
        <label class="mb-1 block text-xs font-semibold text-stone-500"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
        <div class="password-field relative">
            <input type="password" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required
                   class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 pr-16 text-sm focus:border-coffee focus:outline-none">
            <button type="button" class="password-toggle-btn absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-accent" aria-label="Show password">Show</button>
        </div>
    </div>
    <?php
}
