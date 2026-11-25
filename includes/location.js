// Shared geolocation helper. Customer coordinates live ONLY in
// sessionStorage — never written to the database; nearby queries receive
// them per-request as URL params.
//
// Turbo-aware: init runs on both DOMContentLoaded and turbo:load (Turbo
// swaps the <body>, so element listeners must re-bind each navigation).
// Element-level binds are guarded with a dataset flag so re-running init
// never double-binds.
(function () {
    const KEY_LAT = 'higoffe_lat';
    const KEY_LNG = 'higoffe_lng';
    const KEY_DISMISSED = 'higoffe_loc_dismissed';

    function stored() {
        const lat = sessionStorage.getItem(KEY_LAT);
        const lng = sessionStorage.getItem(KEY_LNG);
        return lat && lng ? { lat: lat, lng: lng } : null;
    }

    function visitWithCoords(coords) {
        const url = new URL(window.location.href);
        url.searchParams.set('lat', coords.lat);
        url.searchParams.set('lng', coords.lng);
        window.location.replace(url.toString());
    }

    function requestLocation() {
        if (!navigator.geolocation) {
            window.higoffeToast('Location is not supported by this browser.', 'error');
            return;
        }
        navigator.geolocation.getCurrentPosition(function (pos) {
            const coords = {
                lat: pos.coords.latitude.toFixed(6),
                lng: pos.coords.longitude.toFixed(6)
            };
            sessionStorage.setItem(KEY_LAT, coords.lat);
            sessionStorage.setItem(KEY_LNG, coords.lng);
            localStorage.removeItem(KEY_DISMISSED);
            visitWithCoords(coords);
        }, function () {
            window.higoffeToast('Location permission was denied — you can enable it in your browser settings.', 'error');
        }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 });
    }

    // One-time "allow location?" ask: a dismissible banner on pages that
    // want nearby content, shown until the user allows or dismisses it.
    function maybeShowAskBanner() {
        if (document.body.dataset.wantsLocation !== '1') return;
        if (stored() || localStorage.getItem(KEY_DISMISSED)) return;
        if (document.getElementById('location-ask-banner')) return;

        const main = document.querySelector('main');
        if (!main) return;

        const banner = document.createElement('div');
        banner.id = 'location-ask-banner';
        banner.className = 'mx-4 mt-3 flex items-center gap-3 rounded-2xl border border-accent/30 bg-white p-3 lg:mx-6';
        banner.innerHTML =
            '<div class="min-w-0 flex-1">' +
            '  <p class="text-[13px] font-bold text-stone-900">See cafés near you</p>' +
            '  <p class="text-[11px] text-stone-500">Allow location to sort cafés by distance. Your location is never stored.</p>' +
            '</div>' +
            '<button type="button" id="location-ask-allow" class="shrink-0 rounded-full bg-accent px-3.5 py-1.5 text-[11px] font-bold text-white">Allow</button>' +
            '<button type="button" id="location-ask-dismiss" class="shrink-0 px-1 text-[11px] font-semibold text-stone-400">Not now</button>';
        main.prepend(banner);

        document.getElementById('location-ask-allow').addEventListener('click', requestLocation);
        document.getElementById('location-ask-dismiss').addEventListener('click', function () {
            localStorage.setItem(KEY_DISMISSED, '1');
            banner.remove();
        });
    }

    function init() {
        const coords = stored();
        const url = new URL(window.location.href);
        const urlHasCoords = url.searchParams.has('lat') && url.searchParams.has('lng');

        document.querySelectorAll('.location-chip-label').forEach(function (el) {
            if (coords) el.textContent = 'Near you';
        });

        document.querySelectorAll('.location-chip').forEach(function (btn) {
            if (btn.dataset.locBound) return; // guard against double-bind
            btn.dataset.locBound = '1';
            btn.addEventListener('click', requestLocation);
        });

        // Auto-apply stored coords on pages that opted in.
        if (coords && !urlHasCoords && document.body.dataset.wantsLocation === '1') {
            visitWithCoords(coords);
            return;
        }

        maybeShowAskBanner();
    }

    document.addEventListener('DOMContentLoaded', init);
    document.addEventListener('turbo:load', init);

    // For the seller café-profile "use my location" button.
    window.higoffeGetLocation = function (onSuccess) {
        if (!navigator.geolocation) {
            window.higoffeToast('Location is not supported by this browser.', 'error');
            return;
        }
        navigator.geolocation.getCurrentPosition(function (pos) {
            onSuccess(pos.coords.latitude.toFixed(6), pos.coords.longitude.toFixed(6));
        }, function () {
            window.higoffeToast('Location permission was denied.', 'error');
        });
    };
})();
