# Higoffe — "Nearby Cafés" Location Feature (PLANNED, NOT YET IMPLEMENTED)

Status: deferred by decision. `includes/location.js` already exists as
prepared groundwork but is NOT included by any page yet. Nothing else
below is built.

## Goal
Let a customer allow browser location access and see cafés sorted by
real distance ("Cafés near you", "1.2 km" badges, a Directions button).

## 1. Data — café coordinates
- Migration `005_cafe_location.sql`:
  ```sql
  ALTER TABLE cafes ADD COLUMN IF NOT EXISTS latitude  NUMERIC(9,6);
  ALTER TABLE cafes ADD COLUMN IF NOT EXISTS longitude NUMERIC(9,6);
  ```
  Nullable — cafés without a pin simply don't appear in "nearby".
- Seller side (`cafes/profile.php`): a "Use my current location" button
  calls `window.higoffeGetLocation()` (already in location.js) to fill
  hidden lat/lng inputs; manual lat/lng fields as fallback (paste from
  Google Maps). No map-picker widget — needs a maps SDK/key, not worth it.

## 2. Customer location capture
- `includes/location.js` (already written): permission prompt via
  `navigator.geolocation`, coords cached in sessionStorage only, appended
  per-request as `?lat=..&lng=..` on pages with
  `<body data-wants-location="1">`. Header chip (`.location-chip`)
  triggers the prompt; `.location-chip-label` text becomes "Near you".
- PRIVACY: customer coords are never stored in the DB — session-only,
  per-request. Only café (business) coords are stored.
- CONSTRAINT: Geolocation API requires HTTPS (localhost exempt). Works on
  Render automatically; fails silently on plain-HTTP LAN testing. Desktop
  accuracy is coarse (IP-based).

## 3. Distance query — plain SQL Haversine (no PostGIS)
`Cafe::getNearby($lat, $lng, $limit)`:
```sql
SELECT c.*, (6371 * acos(
    cos(radians(:lat)) * cos(radians(c.latitude)) *
    cos(radians(c.longitude) - radians(:lng)) +
    sin(radians(:lat)) * sin(radians(c.latitude))
)) AS distance_km
FROM cafes c
WHERE c.latitude IS NOT NULL
ORDER BY distance_km ASC
LIMIT :limit;
```

## 4. UI integration
- Header location chip becomes live (class `location-chip`): "Set
  location" → permission prompt → "Near you".
- Home (`feed/index.php`): "Popular cafés" becomes "Cafés near you" with
  distance badges when `$_GET['lat']/['lng']` present; falls back to the
  current list otherwise. Add `data-wants-location="1"` to body + include
  location.js.
- Explore (`cafes/discover.php`): "Nearby" sort chip; distance on cards.
- Café profile (`cafes/view.php`): distance + "Directions" button →
  `https://www.google.com/maps/dir/?api=1&destination=LAT,LNG`
  (free universal link, no API key, opens the native maps app).
- Components: `render_cafe_card_h` / `render_cafe_card_list` show a
  `distance_km` badge when the row has one.

## 5. Deliberately out of scope
- Reverse geocoding (coords → "Banilad, Cebu") — needs external API.
- Live map embeds — directions deep-link covers the need.
- Background location / geofencing.

## Files to touch when implemented
`migrations/005_cafe_location.sql`, `schema.sql`, `models/Cafe.php`,
`cafes/profile.php`, `feed/index.php`, `cafes/discover.php`,
`cafes/view.php`, `includes/header.php` (chip), `includes/components.php`
(distance badge). `includes/location.js` is ready as-is.
