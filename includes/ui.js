// Shared in-app UI helpers — replaces every browser dialog (alert /
// confirm / prompt) in customer/seller pages. Loaded once in <head>
// (defer); all listeners are document-level, so they survive Turbo body
// swaps without re-binding.

// ---- Toast (replaces alert) -------------------------------------------
// type: 'success' | 'warning' | 'error' | 'info' (default).
window.higoffeToast = function (message, type) {
    let wrap = document.getElementById('higoffe-toast-wrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'higoffe-toast-wrap';
        wrap.className = 'fixed inset-x-0 top-3 z-[100] flex flex-col items-center gap-2 px-4 pointer-events-none';
        document.body.appendChild(wrap);
    }
    const styles = {
        success: 'bg-green-600 text-white',
        warning: 'bg-amber-500 text-white',
        error:   'bg-red-600 text-white',
        info:    'bg-stone-900 text-white'
    };
    const icons = {
        success: '<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>',
        warning: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78A1.5 1.5 0 0 0 22.18 18L13.71 3.86a1.5 1.5 0 0 0-2.42 0Z"/>',
        error:   '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 4.5c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>',
        info:    '<path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25.75.75H12v8.25"/><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25h1.5v5.25h-1.5zM12 7.5h.007v.008H12V7.5Z"/>'
    };
    const key = styles[type] ? type : 'info';
    const toast = document.createElement('div');
    toast.className = 'pointer-events-auto flex max-w-sm items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-lg ' + styles[key];
    toast.innerHTML = '<svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">' + icons[key] + '</svg><span></span>';
    toast.querySelector('span').textContent = message; // textContent — messages are never parsed as HTML
    wrap.appendChild(toast);
    setTimeout(function () {
        toast.style.transition = 'opacity 300ms';
        toast.style.opacity = '0';
        setTimeout(function () { toast.remove(); }, 320);
    }, 3200);
};

// ---- Confirmation modal (replaces confirm()) ---------------------------
// Usage: <form data-confirm="Delete this product?"> — submit is held and a
// custom in-page modal (not the browser's native confirm()) asks the user
// to confirm or cancel; confirming re-submits the same form.
(function () {
    let overlay = null;

    function buildOverlay() {
        overlay = document.createElement('div');
        overlay.id = 'higoffe-confirm-overlay';
        overlay.className = 'fixed inset-0 z-[110] hidden items-center justify-center bg-stone-900/50 p-4';
        overlay.innerHTML =
            '<div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl" role="alertdialog" aria-modal="true">' +
            '  <div class="flex items-start gap-3">' +
            '    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-500">' +
            '      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 4.5c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>' +
            '    </div>' +
            '    <p id="higoffe-confirm-message" class="mt-1.5 flex-1 text-sm font-semibold text-stone-800"></p>' +
            '  </div>' +
            '  <div class="mt-5 flex justify-end gap-2">' +
            '    <button type="button" id="higoffe-confirm-cancel" class="rounded-xl px-4 py-2 text-sm font-bold text-stone-500 hover:bg-stone-100">Cancel</button>' +
            '    <button type="button" id="higoffe-confirm-ok" class="rounded-xl bg-red-500 px-4 py-2 text-sm font-bold text-white hover:bg-red-600">Confirm</button>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(overlay);
    }

    function closeModal() {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
    }

    function askConfirm(message, onConfirm) {
        if (!overlay) buildOverlay();
        overlay.querySelector('#higoffe-confirm-message').textContent = message;
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');

        const okBtn = overlay.querySelector('#higoffe-confirm-ok');
        const cancelBtn = overlay.querySelector('#higoffe-confirm-cancel');

        function cleanup() {
            okBtn.removeEventListener('click', onOk);
            cancelBtn.removeEventListener('click', onCancel);
            overlay.removeEventListener('click', onBackdrop);
            document.removeEventListener('keydown', onKey);
        }
        function onOk() { cleanup(); closeModal(); onConfirm(); }
        function onCancel() { cleanup(); closeModal(); }
        function onBackdrop(e) { if (e.target === overlay) onCancel(); }
        function onKey(e) { if (e.key === 'Escape') onCancel(); }

        okBtn.addEventListener('click', onOk);
        cancelBtn.addEventListener('click', onCancel);
        overlay.addEventListener('click', onBackdrop);
        document.addEventListener('keydown', onKey);
    }

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        const message = form.dataset.confirm;
        if (!message || form.dataset.confirmArmed === '1') return;

        e.preventDefault();
        askConfirm(message, function () {
            form.dataset.confirmArmed = '1';
            form.requestSubmit ? form.requestSubmit() : form.submit();
        });
    }, true);
})();

// ---- Inline report-reason (replaces window.prompt) --------------------
// Usage: <form class="report-form"> with a hidden input.reason-input.
// First submit reveals a small inline reason box; second submits with
// whatever was typed (reason optional).
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.classList.contains('report-form')) return;

    if (form.dataset.reasonOpen === '1') {
        const box = form.querySelector('.report-reason-box input');
        if (box) form.querySelector('.reason-input').value = box.value.trim();
        return; // submit proceeds
    }

    e.preventDefault();
    form.dataset.reasonOpen = '1';

    const row = document.createElement('div');
    row.className = 'report-reason-box mt-1.5 flex items-center gap-1.5';
    row.innerHTML =
        '<input type="text" maxlength="300" placeholder="Reason (optional)" ' +
        'class="w-36 rounded-full border border-stone-200 bg-white px-2.5 py-1 text-[11px] focus:border-coffee focus:outline-none">' +
        '<button type="submit" class="rounded-full bg-red-500 px-2.5 py-1 text-[10px] font-bold text-white">Report</button>';
    form.appendChild(row);
    row.querySelector('input').focus();
}, true);
