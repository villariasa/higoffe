// Shared geolocation helper. Customer coordinates live ONLY in
// sessionStorage — they are never written to the database; nearby
// queries receive them per-request as URL params.
//
// Pages that render nearby content (home, explore) auto-reload once with
// ?lat=&lng= appended when stored coords exist but the URL lacks them.
// The header location chip triggers the browser permission prompt.
(function () {
    const KEY_LAT = 'higoffe_lat';
    const KEY_LNG = 'higoffe_lng';

    function stored() {
        const lat = sessionStorage.getItem(KEY_LAT);
        const lng = sessionStorage.getItem(KEY_LNG);
        return lat && lng ? { lat: lat, lng: lng } : null;
    }

    function reloadWithCoords(coords) {
        const url = new URL(window.location.href);
        url.searchParams.set('lat', coords.lat);
        url.searchParams.set('lng', coords.lng);
        window.location.replace(url.toString());
    }

    function requestLocation() {
        if (!navigator.geolocation) {
            alert('Location is not supported by this browser.');
            return;
        }
        navigator.geolocation.getCurrentPosition(function (pos) {
            const coords = {
                lat: pos.coords.latitude.toFixed(6),
                lng: pos.coords.longitude.toFixed(6)
            };
            sessionStorage.setItem(KEY_LAT, coords.lat);
            sessionStorage.setItem(KEY_LNG, coords.lng);
            reloadWithCoords(coords);
        }, function () {
            alert('Location permission was denied. You can enable it from your browser settings.');
        }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const chipLabels = document.querySelectorAll('.location-chip-label');
        const coords = stored();
        const url = new URL(window.location.href);
        const urlHasCoords = url.searchParams.has('lat') && url.searchParams.has('lng');

        if (coords) {
            chipLabels.forEach(function (el) { el.textContent = 'Near you'; });
            // Auto-apply coords on pages that opted in via data-wants-location
            if (!urlHasCoords && document.body.dataset.wantsLocation === '1') {
                reloadWithCoords(coords);
            }
        }

        document.querySelectorAll('.location-chip').forEach(function (btn) {
            btn.addEventListener('click', requestLocation);
        });
    });

    // Exposed for the seller café-profile "use my location" button.
    window.higoffeGetLocation = function (onSuccess) {
        if (!navigator.geolocation) {
            alert('Location is not supported by this browser.');
            return;
        }
        navigator.geolocation.getCurrentPosition(function (pos) {
            onSuccess(pos.coords.latitude.toFixed(6), pos.coords.longitude.toFixed(6));
        }, function () {
            alert('Location permission was denied.');
        });
    };
})();
