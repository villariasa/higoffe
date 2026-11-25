<?php
// Copy to upload_credentials.php (gitignored). UPLOAD_SIGNING_SECRET must
// be the EXACT same value as the higoffe-upload-worker Worker's
// UPLOAD_SIGNING_SECRET secret (set via `wrangler secret put
// UPLOAD_SIGNING_SECRET` in the higoffe-upload-worker project) — PHP signs
// upload tokens, the Worker verifies them with the same shared secret.
// Generate a long random value, e.g.: php -r "echo bin2hex(random_bytes(32));"

define('UPLOAD_WORKER_URL', 'https://higoffe-upload-worker.YOUR_SUBDOMAIN.workers.dev');
define('UPLOAD_SIGNING_SECRET', 'replace-with-a-long-random-string-matching-the-worker-secret');

// Host of the R2 public bucket (matches PUBLIC_BASE_URL in the Worker's
// wrangler.toml, without the scheme) — e.g. "pub-xxxxxxxx.r2.dev" or your
// custom domain. Every stored image URL is checked against this so a
// malicious client can't submit a javascript: URL or point at some other
// host through the image_url/avatar_url/etc. form fields.
define('UPLOAD_PUBLIC_HOST', 'pub-xxxxxxxx.r2.dev');
