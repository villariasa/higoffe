// Shared in-app UI helpers — replaces every browser dialog (alert /
// confirm / prompt) in customer/seller pages. Loaded once in <head>
// (defer); all listeners are document-level, so they survive Turbo body
// swaps without re-binding.

// ---- Toast (replaces alert) -------------------------------------------
window.higoffeToast = function (message, type) {
    let wrap = document.getElementById('higoffe-toast-wrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'higoffe-toast-wrap';
        wrap.className = 'fixed inset-x-0 top-3 z-[100] flex flex-col items-center gap-2 px-4 pointer-events-none';
        document.body.appendChild(wrap);
    }
    const toast = document.createElement('div');
    toast.className = 'pointer-events-auto max-w-sm rounded-xl px-4 py-2.5 text-sm font-semibold shadow-lg ' +
        (type === 'error' ? 'bg-red-600 text-white' : 'bg-stone-900 text-white');
    toast.textContent = message; // textContent — messages are never parsed as HTML
    wrap.appendChild(toast);
    setTimeout(function () {
        toast.style.transition = 'opacity 300ms';
        toast.style.opacity = '0';
        setTimeout(function () { toast.remove(); }, 320);
    }, 3200);
};

// ---- Two-tap confirm (replaces confirm()) -----------------------------
// Usage: <form data-confirm="Delete this product?"> — the first submit is
// held and the button becomes "Tap again to confirm"; a second submit
// within 4s goes through. No modal, no dialog.
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    const message = form.dataset.confirm;
    if (!message) return;

    if (form.dataset.confirmArmed === '1') {
        delete form.dataset.confirmArmed;
        return; // second tap — let it submit
    }

    e.preventDefault();
    form.dataset.confirmArmed = '1';
    window.higoffeToast(message + ' Tap again to confirm.');

    const btn = form.querySelector('button[type="submit"], button:not([type])');
    let original = null;
    if (btn) {
        original = btn.innerHTML;
        btn.textContent = 'Tap again to confirm';
        btn.classList.add('ring-2', 'ring-red-400');
    }
    setTimeout(function () {
        delete form.dataset.confirmArmed;
        if (btn && original !== null) {
            btn.innerHTML = original;
            btn.classList.remove('ring-2', 'ring-red-400');
        }
    }, 4000);
}, true);

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
