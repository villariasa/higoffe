<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Uploads.php';
require_once __DIR__ . '/../models/Cafe.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/image_upload_field.php';
require_role('seller');

$pdo = (new Database())->connect();
$cafeModel = new Cafe($pdo);
$cafe = $cafeModel->getByOwner((int) $_SESSION['user_id']);

$error = '';
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cafe_name   = (string) trim($_POST['cafe_name'] ?? '');
    $description = (string) trim($_POST['description'] ?? '');
    $address     = (string) trim($_POST['address'] ?? '');
    $logo_url    = (string) trim($_POST['logo_url'] ?? '');
    $cover_url   = (string) trim($_POST['cover_url'] ?? '');
    $latitude    = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float) $_POST['latitude'] : null;
    $longitude   = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;

    if ($cafe_name === '') {
        $error = 'Café name is required.';
    } elseif (!Uploads::isAllowedMediaUrl($logo_url) || !Uploads::isAllowedMediaUrl($cover_url)) {
        $error = 'Invalid image URL.';
    } elseif ($cafe) {
        $cafeModel->update((int) $cafe['cafe_id'], $cafe_name, $description, $address, $logo_url, $cover_url, $latitude, $longitude);
        $cafe = $cafeModel->getByOwner((int) $_SESSION['user_id']);
        $saved = true;
    } else {
        $cafe_id = $cafeModel->create((int) $_SESSION['user_id'], $cafe_name, $description, $address, $latitude, $longitude);
        $cafe = $cafeModel->getById((int) $cafe_id);
        $saved = true;
    }
}

$upload_auth = Uploads::issueToken((int) $_SESSION['user_id'], 'cafe');

$page_title = $cafe ? 'Edit your café' : 'Set up your café';
$back_url = '/queue/index.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="px-4 py-4">
    <?php if ($saved): ?>
        <script>document.addEventListener('DOMContentLoaded', function () { window.higoffeToast('Café profile saved.', 'success'); });</script>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <script>document.addEventListener('DOMContentLoaded', function () { window.higoffeToast(<?= json_encode($error, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, 'error'); });</script>
    <?php endif; ?>

    <form method="post" class="space-y-3">
        <div class="space-y-3 rounded-2xl bg-white p-4">
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Café name</label>
                <input type="text" name="cafe_name" required value="<?= htmlspecialchars($cafe['cafe_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Description</label>
                <textarea name="description" rows="3" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none"><?= htmlspecialchars($cafe['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($cafe['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-stone-500">Pin your café's location</label>
                <p class="mb-2 text-[10px] text-stone-400">Type the city or area your café is in — we'll look it up and drop a pin automatically, so your location data stays accurate for Nearby Cafés.</p>

                <div class="flex gap-2">
                    <input type="text" id="input-city" placeholder="e.g. Lahug, Cebu City" class="w-full rounded-xl border border-stone-200 bg-cream p-2.5 text-sm focus:border-coffee focus:outline-none">
                    <button type="button" id="btn-geocode" class="shrink-0 rounded-xl bg-coffee px-3.5 text-xs font-bold text-white active:opacity-80">Find</button>
                </div>
                <button type="button" id="btn-use-location" class="mt-2 flex items-center gap-1 text-[11px] font-bold text-accent hover:underline">
                    <?php icon('map-pin-solid', 'w-3 h-3'); ?> Or use my current location
                </button>

                <div id="map-preview-wrap" class="mt-2.5 overflow-hidden rounded-xl border border-stone-200 <?= ($cafe['latitude'] ?? null) !== null ? '' : 'hidden' ?>">
                    <iframe id="map-preview" class="h-44 w-full" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <p id="map-preview-label" class="mt-1 text-[11px] font-semibold text-stone-600"><?= htmlspecialchars($cafe['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>

                <input type="hidden" name="latitude" id="input-lat" value="<?= htmlspecialchars($cafe['latitude'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="longitude" id="input-lng" value="<?= htmlspecialchars($cafe['longitude'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <?php render_image_upload_field($upload_auth, 'logo_url', 'Logo', $cafe['logo_url'] ?? '', 4); ?>
            <?php render_image_upload_field($upload_auth, 'cover_url', 'Cover photo', $cafe['cover_url'] ?? '', 4); ?>
        </div>
        <button type="submit" class="w-full rounded-xl bg-accent py-3 text-sm font-bold text-white active:opacity-80">Save</button>
    </form>

    <?php if ($cafe): ?>
        <p class="mt-3 text-center text-sm">
            <a href="/cafes/view.php?id=<?= (int) $cafe['cafe_id'] ?>" class="font-semibold text-accent">View your public café page &rarr;</a>
        </p>
    <?php endif; ?>
</div>
<script src="/includes/image_upload.js"></script>
<script src="/includes/location.js"></script>
<script>
// Drops a pin + fills the hidden lat/lng inputs from a resolved place.
// Uses OpenStreetMap's free Nominatim (geocoding) and the free embeddable
// OSM iframe (map pin) — no API key, no paid service, matches the
// no-Composer/no-SDK coding standards.
function showMapPin(lat, lng, label) {
    document.getElementById('input-lat').value = lat;
    document.getElementById('input-lng').value = lng;
    const delta = 0.01;
    const bbox = [lng - delta, lat - delta, lng + delta, lat + delta].join(',');
    document.getElementById('map-preview').src =
        'https://www.openstreetmap.org/export/embed.html?bbox=' + encodeURIComponent(bbox) +
        '&layer=mapnik&marker=' + lat + ',' + lng;
    document.getElementById('map-preview-wrap').classList.remove('hidden');
    if (label) document.getElementById('map-preview-label').textContent = label;
}

document.getElementById('btn-geocode')?.addEventListener('click', function () {
    const btn = this;
    const city = document.getElementById('input-city').value.trim();
    if (!city) {
        window.higoffeToast('Type a city or area first.', 'warning');
        return;
    }
    const original = btn.textContent;
    btn.textContent = 'Finding...';
    btn.disabled = true;

    fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=ph&q=' + encodeURIComponent(city))
        .then(function (res) { return res.json(); })
        .then(function (results) {
            btn.textContent = original;
            btn.disabled = false;
            if (!results || !results.length) {
                window.higoffeToast('No matching location found — try a different area name.', 'warning');
                return;
            }
            const r = results[0];
            showMapPin(parseFloat(r.lat), parseFloat(r.lon), r.display_name);
            const addressInput = document.querySelector('input[name="address"]');
            if (addressInput && !addressInput.value) addressInput.value = r.display_name;
            window.higoffeToast('Location pinned. Check the map and save.', 'success');
        })
        .catch(function () {
            btn.textContent = original;
            btn.disabled = false;
            window.higoffeToast('Could not reach the location service — try again.', 'error');
        });
});

document.getElementById('btn-use-location')?.addEventListener('click', function () {
    const btn = this;
    const originalText = btn.innerHTML;
    btn.textContent = 'Detecting...';
    window.higoffeGetLocation(function (lat, lng) {
        btn.innerHTML = originalText;
        showMapPin(parseFloat(lat), parseFloat(lng), null);
        window.higoffeToast('Current location pinned.', 'success');
    });
});
</script>
<script>
if (window.HigoffeTour && window.HigoffeTour.isDone('shell')) {
    window.HigoffeTour.run('cafe_profile', [
        { selector: '#input-city', title: 'Pin your location', description: 'Type your city or area — no need to look up coordinates yourself.' },
        { selector: '#btn-geocode', title: 'Find it', description: 'We look it up and drop a pin automatically, so your data stays accurate.' },
        { selector: '#map-preview-wrap', title: 'Confirm on the map', description: "Check the pin landed in the right spot before saving." },
        { selector: '.image-upload-field', title: 'Logo & cover photo', description: 'Add photos so customers recognize your café in the feed and on maps.' }
    ]);
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
