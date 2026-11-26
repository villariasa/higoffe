// Shared geolocation helper. Customer coordinates live in sessionStorage
// (and localStorage cache) — never written to the database; nearby queries
// receive them per-request as URL params.
//
// Turbo-aware: init runs on both DOMContentLoaded and turbo:load.
(function () {
    const KEY_LAT = 'higoffe_lat';
    const KEY_LNG = 'higoffe_lng';
    const KEY_CITY = 'higoffe_city';
    const KEY_DISMISSED = 'higoffe_loc_dismissed';

    // Reverse-geocodes once via OpenStreetMap's free Nominatim so the
    // location chip can show a real city name instead of a vague "Near
    // you" label — no API key, fits the no-paid-service coding standards.
    function resolveCityLabel(lat, lng) {
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&zoom=10&lat=' + lat + '&lon=' + lng)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                const addr = data && data.address ? data.address : {};
                const city = addr.city || addr.town || addr.municipality || addr.county || null;
                if (!city) return;
                sessionStorage.setItem(KEY_CITY, city);
                localStorage.setItem(KEY_CITY, city);
                document.querySelectorAll('.location-chip-label').forEach(function (el) {
                    el.textContent = city;
                });
            })
            .catch(function () { /* chip keeps its current label */ });
    }

    // The location banner inserts a new node at the top of <main>, which
    // reflows everything below it — if that happens while Driver.js has
    // just measured a spotlight position (guided tour), the highlighted
    // hole ends up misaligned with its target ("cuts out"/vanishes). Defer
    // the banner until any pending tour for this user has finished.
    function tourIsPending() {
        try {
            if (!window.HIGOFFE_USER_ID) return false;
            return localStorage.getItem('higoffe_tour_' + window.HIGOFFE_USER_ID + '_shell') !== '1';
        } catch (e) {
            return false;
        }
    }

    function stored() {
        const lat = sessionStorage.getItem(KEY_LAT) || localStorage.getItem(KEY_LAT);
        const lng = sessionStorage.getItem(KEY_LNG) || localStorage.getItem(KEY_LNG);
        if (lat && lng) {
            sessionStorage.setItem(KEY_LAT, lat);
            sessionStorage.setItem(KEY_LNG, lng);
            return { lat: lat, lng: lng };
        }
        return null;
    }

    function isPageWantingLocation() {
        return (document.body && document.body.dataset.wantsLocation === '1') ||
               document.querySelector('[data-wants-location="1"]') !== null ||
               window.location.pathname.includes('/feed/') ||
               window.location.pathname.includes('/cafes/discover') ||
               window.location.pathname.includes('/cafes/view');
    }

    function visitWithCoords(coords) {
        const url = new URL(window.location.href);
        if (url.searchParams.get('lat') === coords.lat && url.searchParams.get('lng') === coords.lng) {
            return;
        }
        url.searchParams.set('lat', coords.lat);
        url.searchParams.set('lng', coords.lng);
        if (window.Turbo && typeof window.Turbo.visit === 'function') {
            window.Turbo.visit(url.toString(), { action: 'replace' });
        } else {
            window.location.replace(url.toString());
        }
    }

    function requestLocation() {
        if (window.isSecureContext === false && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
            const msg = 'Browser location requires HTTPS (works automatically on Render).';
            if (typeof window.higoffeToast === 'function') window.higoffeToast(msg, 'error');
            else alert(msg);
            return;
        }

        if (!navigator.geolocation) {
            if (typeof window.higoffeToast === 'function') {
                window.higoffeToast('Location is not supported by this browser.', 'error');
            } else {
                alert('Location is not supported by this browser.');
            }
            return;
        }

        const allowBtn = document.getElementById('location-ask-allow');
        if (allowBtn) {
            allowBtn.textContent = 'Locating...';
            allowBtn.disabled = true;
        }

        navigator.geolocation.getCurrentPosition(function (pos) {
            const coords = {
                lat: pos.coords.latitude.toFixed(6),
                lng: pos.coords.longitude.toFixed(6)
            };
            sessionStorage.setItem(KEY_LAT, coords.lat);
            sessionStorage.setItem(KEY_LNG, coords.lng);
            localStorage.setItem(KEY_LAT, coords.lat);
            localStorage.setItem(KEY_LNG, coords.lng);
            sessionStorage.removeItem(KEY_DISMISSED);

            document.querySelectorAll('.location-chip-label').forEach(function (el) {
                el.textContent = 'Near you';
            });
            resolveCityLabel(coords.lat, coords.lng);

            document.getElementById('location-ask-banner')?.remove();
            document.getElementById('location-chip-tooltip')?.remove();

            if (typeof window.higoffeToast === 'function') {
                window.higoffeToast('Location applied! Showing cafés near you.', 'success');
            }

            visitWithCoords(coords);
        }, function (err) {
            console.warn('Geolocation error:', err);
            sessionStorage.setItem(KEY_DISMISSED, '1');
            document.getElementById('location-ask-banner')?.remove();
            document.getElementById('location-chip-tooltip')?.remove();

            const msg = err.code === 1
                ? 'Location permission was denied. You can enable it in your browser address bar.'
                : 'Unable to retrieve your location.';
            if (typeof window.higoffeToast === 'function') {
                window.higoffeToast(msg, 'error');
            }
        }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 });
    }

    // Prominent "See cafés near you" ask banner (inspired by Foodpanda / GrabFood)
    function maybeShowAskBanner() {
        if (!isPageWantingLocation()) return;
        if (tourIsPending()) return;
        if (stored() || sessionStorage.getItem(KEY_DISMISSED)) return;
        if (document.getElementById('location-ask-banner')) return;

        const main = document.querySelector('main') || document.querySelector('.min-w-0');
        if (!main) return;

        const banner = document.createElement('div');
        banner.id = 'location-ask-banner';
        banner.className = 'mx-4 my-3 flex items-center justify-between gap-3 rounded-2xl border border-accent/40 bg-white p-3.5 shadow-sm lg:mx-8';
        banner.innerHTML =
            '<div class="flex items-center gap-3 min-w-0">' +
            '  <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-accent/10 text-accent">' +
            '    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>' +
            '  </div>' +
            '  <div class="min-w-0 flex-1">' +
            '    <p class="text-xs font-bold text-stone-900">See cafés near you</p>' +
            '    <p class="text-[11px] text-stone-500">Allow location to sort cafés and menu items by real distance.</p>' +
            '  </div>' +
            '</div>' +
            '<div class="flex items-center gap-1.5 shrink-0">' +
            '  <button type="button" id="location-ask-allow" class="rounded-full bg-accent px-3.5 py-1.5 text-xs font-bold text-white shadow hover:bg-accent/90 active:scale-95 transition-all">Allow</button>' +
            '  <button type="button" id="location-ask-dismiss" class="rounded-full px-2 text-xs font-medium text-stone-400 hover:text-stone-600">Not now</button>' +
            '</div>';

        main.prepend(banner);

        document.getElementById('location-ask-allow')?.addEventListener('click', requestLocation);
        document.getElementById('location-ask-dismiss')?.addEventListener('click', function () {
            sessionStorage.setItem(KEY_DISMISSED, '1');
            banner.remove();
        });
    }

    // Floating Foodpanda-style helper tooltip on the location button
    function maybeShowTooltip() {
        if (tourIsPending()) return;
        if (stored() || sessionStorage.getItem(KEY_DISMISSED)) return;
        if (document.getElementById('location-chip-tooltip')) return;

        const chip = document.querySelector('.location-chip');
        if (!chip || !chip.parentElement) return;

        const parent = chip.parentElement;
        parent.classList.add('relative');

        const tip = document.createElement('div');
        tip.id = 'location-chip-tooltip';
        tip.className = 'absolute top-full mt-2 left-0 z-50 w-60 rounded-xl bg-stone-900 p-2.5 text-white shadow-xl pointer-events-auto cursor-pointer animate-fade-in';
        tip.innerHTML =
            '<div class="absolute -top-1 left-4 h-2 w-2 rotate-45 bg-stone-900"></div>' +
            '<p class="text-[11px] font-bold flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-accent animate-pulse"></span>Viewing default location</p>' +
            '<p class="text-[10px] text-stone-300 mt-0.5">Click to find coffee shops near your real location.</p>';

        tip.addEventListener('click', function (e) {
            e.stopPropagation();
            tip.remove();
            requestLocation();
        });

        parent.appendChild(tip);
    }

    function init() {
        const coords = stored();
        const url = new URL(window.location.href);
        const urlHasCoords = url.searchParams.has('lat') && url.searchParams.has('lng');

        // Also capture coords from URL query if user opened a link with lat/lng
        if (!coords && urlHasCoords) {
            const fromUrl = { lat: url.searchParams.get('lat'), lng: url.searchParams.get('lng') };
            sessionStorage.setItem(KEY_LAT, fromUrl.lat);
            sessionStorage.setItem(KEY_LNG, fromUrl.lng);
            localStorage.setItem(KEY_LAT, fromUrl.lat);
            localStorage.setItem(KEY_LNG, fromUrl.lng);
        }

        const cachedCity = sessionStorage.getItem(KEY_CITY) || localStorage.getItem(KEY_CITY);
        document.querySelectorAll('.location-chip-label').forEach(function (el) {
            if (stored()) el.textContent = cachedCity || 'Near you';
        });
        if (coords && !cachedCity) resolveCityLabel(coords.lat, coords.lng);

        document.querySelectorAll('.location-chip').forEach(function (btn) {
            if (btn.dataset.locBound) return;
            btn.dataset.locBound = '1';
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                requestLocation();
            });
        });

        // Auto-apply stored coords if page wants location but URL lacks params
        if (coords && !urlHasCoords && isPageWantingLocation()) {
            visitWithCoords(coords);
            return;
        }

        // If permission was already granted by browser, auto-apply silently
        if (!coords && navigator.permissions && navigator.permissions.query) {
            navigator.permissions.query({ name: 'geolocation' }).then(function (result) {
                if (result.state === 'granted') {
                    navigator.geolocation.getCurrentPosition(function (pos) {
                        const newCoords = {
                            lat: pos.coords.latitude.toFixed(6),
                            lng: pos.coords.longitude.toFixed(6)
                        };
                        sessionStorage.setItem(KEY_LAT, newCoords.lat);
                        sessionStorage.setItem(KEY_LNG, newCoords.lng);
                        localStorage.setItem(KEY_LAT, newCoords.lat);
                        localStorage.setItem(KEY_LNG, newCoords.lng);
                        if (!urlHasCoords && isPageWantingLocation()) {
                            visitWithCoords(newCoords);
                        }
                    });
                } else if (result.state === 'prompt') {
                    maybeShowAskBanner();
                    maybeShowTooltip();
                }
            }).catch(function () {
                maybeShowAskBanner();
                maybeShowTooltip();
            });
        } else if (!coords) {
            maybeShowAskBanner();
            maybeShowTooltip();
        }
    }

    document.addEventListener('DOMContentLoaded', init);
    document.addEventListener('turbo:load', init);

    // For the seller café-profile "use my location" button.
    window.higoffeGetLocation = function (onSuccess) {
        if (!navigator.geolocation) {
            if (typeof window.higoffeToast === 'function') {
                window.higoffeToast('Location is not supported by this browser.', 'error');
            } else {
                alert('Location is not supported by this browser.');
            }
            return;
        }
        navigator.geolocation.getCurrentPosition(function (pos) {
            onSuccess(pos.coords.latitude.toFixed(6), pos.coords.longitude.toFixed(6));
        }, function () {
            if (typeof window.higoffeToast === 'function') {
                window.higoffeToast('Location permission was denied.', 'error');
            } else {
                alert('Location permission was denied.');
            }
        });
    };
})();
