// Browser push notifications for events that happen outside the page
// you're currently on (new inquiry, new order, order status change,
// likes/comments/reviews) — this is what makes a seller NOT have to sit
// on one tab or refresh to find out something happened.
(function () {
    const KEY_LAST_ID = 'higoffe_notif_last_id_' + (window.HIGOFFE_USER_ID || 0);
    const KEY_ASKED = 'higoffe_notif_asked_' + (window.HIGOFFE_USER_ID || 0);
    const POLL_MS = 12000;
    let pollTimer = null;

    function supported() {
        return 'Notification' in window;
    }

    function lastId() {
        return parseInt(localStorage.getItem(KEY_LAST_ID), 10) || 0;
    }

    function fire(n) {
        try {
            const browserNotif = new Notification('Higoffe', {
                body: n.body,
                icon: '/assets/images/favicon-32x32.png',
                tag: 'higoffe-notif-' + n.notification_id
            });
            browserNotif.onclick = function () {
                window.focus();
                if (n.link) window.location.href = n.link;
                browserNotif.close();
            };
        } catch (err) { /* some browsers throw if the tab isn't focused/visible — safe to ignore */ }
    }

    async function poll() {
        if (!document.body.contains(document.body)) { return; } // no-op guard, body always present
        try {
            const res = await fetch('/poll_notifications.php?after_id=' + lastId());
            const data = await res.json();
            (data.notifications || []).forEach(fire);
            if (data.latest_id) localStorage.setItem(KEY_LAST_ID, data.latest_id);
        } catch (err) { /* network hiccup — next interval retries */ }
    }

    function startPolling() {
        if (pollTimer) return;
        poll();
        pollTimer = setInterval(poll, POLL_MS);
    }

    function showAskBanner() {
        if (sessionStorage.getItem(KEY_ASKED)) return;
        if (document.getElementById('push-ask-banner')) return;
        const main = document.querySelector('main') || document.querySelector('.min-w-0');
        if (!main) return;

        const banner = document.createElement('div');
        banner.id = 'push-ask-banner';
        banner.className = 'mx-4 my-3 flex items-center justify-between gap-3 rounded-2xl border border-accent/40 bg-white p-3.5 shadow-sm lg:mx-8';
        banner.innerHTML =
            '<div class="flex items-center gap-3 min-w-0">' +
            '  <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-accent/10 text-accent">' +
            '    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>' +
            '  </div>' +
            '  <div class="min-w-0 flex-1">' +
            '    <p class="text-xs font-bold text-stone-900">Turn on notifications</p>' +
            '    <p class="text-[11px] text-stone-500">Get notified the instant a new inquiry, order, or reply comes in.</p>' +
            '  </div>' +
            '</div>' +
            '<div class="flex items-center gap-1.5 shrink-0">' +
            '  <button type="button" id="push-ask-allow" class="rounded-full bg-accent px-3.5 py-1.5 text-xs font-bold text-white shadow hover:bg-accent/90 active:scale-95 transition-all">Allow</button>' +
            '  <button type="button" id="push-ask-dismiss" class="rounded-full px-2 text-xs font-medium text-stone-400 hover:text-stone-600">Not now</button>' +
            '</div>';
        main.prepend(banner);

        document.getElementById('push-ask-allow').addEventListener('click', function () {
            // Notification.requestPermission() must be called from a real
            // user gesture (this click) or Chrome/Firefox silently refuse
            // to show the native prompt at all.
            Notification.requestPermission().then(function (perm) {
                banner.remove();
                if (perm === 'granted') {
                    if (window.higoffeToast) window.higoffeToast('Notifications enabled.', 'success');
                    startPolling();
                } else if (window.higoffeToast) {
                    window.higoffeToast('Notifications blocked — you can re-enable them in your browser\'s site settings.', 'warning');
                }
            });
        });
        document.getElementById('push-ask-dismiss').addEventListener('click', function () {
            sessionStorage.setItem(KEY_ASKED, '1');
            banner.remove();
        });
    }

    function init() {
        if (!window.HIGOFFE_USER_ID || !supported()) return;

        if (Notification.permission === 'granted') {
            startPolling();
        } else if (Notification.permission === 'default') {
            // Don't compete with the guided tour's spotlight positioning.
            if (window.HigoffeTour && !window.HigoffeTour.isDone('shell')) return;
            showAskBanner();
        }
        // permission === 'denied' — nothing to do, browser won't re-prompt.
    }

    document.addEventListener('DOMContentLoaded', init);
    document.addEventListener('turbo:load', init);
})();
