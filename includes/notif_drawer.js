// Notification bell → toggle drawer, instead of a static badge that only
// ever links to the full page. Clicking a row jumps to wherever it's
// actually about (an order, a post, the queue, etc.); "See all" at the
// bottom still goes to the full notifications.php page.
(function () {
    let panel = null;
    let loaded = false;

    function timeAgo(iso) {
        const diffMs = Date.now() - new Date(iso.replace(' ', 'T') + 'Z').getTime();
        const mins = Math.floor(diffMs / 60000);
        if (mins < 1) return 'just now';
        if (mins < 60) return mins + 'm ago';
        const hrs = Math.floor(mins / 60);
        if (hrs < 24) return hrs + 'h ago';
        return Math.floor(hrs / 24) + 'd ago';
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function buildPanel() {
        panel = document.createElement('div');
        panel.id = 'higoffe-notif-panel';
        panel.className = 'fixed right-3 top-16 z-[105] hidden w-[92vw] max-w-sm overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-2xl lg:right-8';
        panel.innerHTML =
            '<div class="flex items-center justify-between border-b border-stone-100 px-4 py-3">' +
            '  <p class="text-sm font-bold text-stone-900">Notifications</p>' +
            '  <button type="button" id="higoffe-notif-close" class="text-stone-400 hover:text-stone-600" aria-label="Close">' +
            '    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>' +
            '  </button>' +
            '</div>' +
            '<div id="higoffe-notif-list" class="max-h-96 overflow-y-auto"></div>' +
            '<a href="/notifications.php" class="block border-t border-stone-100 px-4 py-2.5 text-center text-xs font-bold text-accent hover:bg-cream">See all</a>';
        document.body.appendChild(panel);

        panel.querySelector('#higoffe-notif-close').addEventListener('click', close);
        document.addEventListener('click', function (e) {
            if (panel.classList.contains('hidden')) return;
            if (panel.contains(e.target) || e.target.closest('[data-tour="notif-bell"]')) return;
            close();
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    }

    function renderList(items) {
        const list = panel.querySelector('#higoffe-notif-list');
        if (!items.length) {
            list.innerHTML = '<p class="px-4 py-6 text-center text-xs text-stone-400">No notifications yet.</p>';
            return;
        }
        list.innerHTML = items.map(function (n) {
            const tag = n.link ? 'a' : 'div';
            const href = n.link ? ' href="' + escapeHtml(n.link) + '"' : '';
            return '<' + tag + href + ' class="block border-b border-stone-50 px-4 py-3 text-left ' + (n.link ? 'hover:bg-cream' : '') + ' ' + (n.is_read ? '' : 'bg-amber-50/40') + '">' +
                '<p class="text-[13px] text-stone-800">' + escapeHtml(n.body) + '</p>' +
                '<p class="mt-0.5 text-[11px] text-stone-400">' + timeAgo(n.created_at) + '</p>' +
                '</' + tag + '>';
        }).join('');
    }

    function open() {
        if (!panel) buildPanel();
        panel.classList.remove('hidden');
        document.querySelectorAll('[data-tour="notif-bell"] span.absolute').forEach(function (badge) { badge.remove(); });
        if (!loaded) {
            panel.querySelector('#higoffe-notif-list').innerHTML = '<p class="px-4 py-6 text-center text-xs text-stone-400">Loading…</p>';
            fetch('/notifications_feed.php')
                .then(function (r) { return r.json(); })
                .then(function (data) { loaded = true; renderList(data.notifications || []); })
                .catch(function () { panel.querySelector('#higoffe-notif-list').innerHTML = '<p class="px-4 py-6 text-center text-xs text-red-500">Could not load notifications.</p>'; });
        }
    }

    function close() {
        if (panel) panel.classList.add('hidden');
    }

    function wire() {
        document.querySelectorAll('[data-tour="notif-bell"]').forEach(function (bell) {
            if (bell.dataset.drawerBound) return;
            bell.dataset.drawerBound = '1';
            bell.addEventListener('click', function (e) {
                e.preventDefault();
                if (panel && !panel.classList.contains('hidden')) { close(); return; }
                open();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', wire);
    document.addEventListener('turbo:load', wire);
})();
