// Expand-in-place order details (slide animation) — replaces navigating to
// a separate page just to check an order's status. Lazy-fetches
// orders/detail_fragment.php once per row, then toggles via max-height.
// Whichever panel is open also self-refreshes every few seconds, so a
// seller confirming an order / changing its status shows up on the
// customer's side (and vice versa) without a manual reload.
(function () {
    let refreshTimer = null;

    async function refreshOpenPanel() {
        const panel = document.querySelector('.order-detail-panel[data-open="1"]');
        if (!panel || !document.body.contains(panel)) return;
        try {
            const res = await fetch('/orders/detail_fragment.php?id=' + panel.dataset.orderId);
            if (res.ok) panel.innerHTML = await res.text();
        } catch (err) { /* next tick retries */ }
    }

    function ensureRefreshLoop() {
        if (refreshTimer) return;
        refreshTimer = setInterval(function () {
            if (!document.querySelector('.order-row-toggle')) { clearInterval(refreshTimer); refreshTimer = null; return; }
            if (document.hidden) return;
            refreshOpenPanel();
        }, 4000);
    }

    function wire() {
        ensureRefreshLoop();
        document.querySelectorAll('.order-row-toggle').forEach(function (btn) {
            if (btn.dataset.toggleBound) return;
            btn.dataset.toggleBound = '1';
            btn.addEventListener('click', async function () {
                const id = btn.dataset.orderId;
                const panel = document.querySelector('.order-detail-panel[data-order-id="' + id + '"]');
                if (!panel) return;
                const isOpen = panel.dataset.open === '1';

                // Accordion — collapse any other open row first.
                document.querySelectorAll('.order-detail-panel[data-open="1"]').forEach(function (p) {
                    if (p !== panel) { p.dataset.open = '0'; p.style.maxHeight = '0px'; }
                });

                if (isOpen) {
                    panel.dataset.open = '0';
                    panel.style.maxHeight = '0px';
                    return;
                }

                if (!panel.dataset.loaded) {
                    panel.innerHTML = '<p class="p-4 text-xs text-stone-400">Loading order details…</p>';
                    try {
                        const res = await fetch('/orders/detail_fragment.php?id=' + id);
                        panel.innerHTML = res.ok ? await res.text() : '<p class="p-4 text-xs text-red-500">Could not load this order.</p>';
                        panel.dataset.loaded = res.ok ? '1' : '';
                    } catch (err) {
                        panel.innerHTML = '<p class="p-4 text-xs text-red-500">Network error — tap to retry.</p>';
                    }
                }
                panel.dataset.open = '1';
                panel.style.maxHeight = '2000px'; // generous cap, not exact — content includes async-loading images
            });
        });
    }
    document.addEventListener('DOMContentLoaded', wire);
    document.addEventListener('turbo:load', wire);
})();
