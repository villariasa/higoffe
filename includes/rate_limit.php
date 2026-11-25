<?php
// Sliding-window rate limiter, backed by the existing PostgreSQL DB.
// No Redis, no extra infra — fits the no-external-dependencies coding standard.
//
// Usage:
//   require_once __DIR__ . '/rate_limit.php';
//   rate_limit_check('login', $_SERVER['REMOTE_ADDR'], 10, 60);
//   // → allows 10 attempts per 60-second window per IP; aborts with 429 if exceeded.
//
// bucket_key: a namespaced string — e.g. "login:1.2.3.4" or "resend:1.2.3.4"
// max_hits:   max attempts allowed in the window
// window_secs: sliding window length in seconds

function rate_limit_check(
    string $action,
    string $ip,
    int    $max_hits   = 10,
    int    $window_secs = 60
): void {
    // Sanitise IP — truncate to /24 for IPv4, /64 for IPv6, so a single
    // attacker rotating across a /24 is still caught.
    $key = $action . ':' . _rl_normalise_ip($ip);

    // Lazy-load the PDO from the global registry (every PHP page already
    // creates one before including this file; pass it via global to avoid
    // a second connection).
    global $pdo;
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        // If no PDO is in scope (should never happen in production), skip
        // silently rather than breaking the page.
        return;
    }

    $window_start = date('Y-m-d H:i:s', intdiv(time(), $window_secs) * $window_secs);

    // Wrap everything in a try/catch — if the migration hasn't been run yet
    // the table won't exist and we must NOT crash the page; fail open instead.
    try {
        // Upsert the counter for the current window.
        $pdo->prepare(
            "INSERT INTO rate_limit_buckets (bucket_key, window_start, hit_count)
             VALUES (:k, :ws, 1)
             ON CONFLICT (bucket_key, window_start)
             DO UPDATE SET hit_count = rate_limit_buckets.hit_count + 1"
        )->execute([':k' => $key, ':ws' => $window_start]);

        // Read back the current count.
        $st = $pdo->prepare(
            "SELECT hit_count FROM rate_limit_buckets
             WHERE bucket_key = :k AND window_start = :ws"
        );
        $st->execute([':k' => $key, ':ws' => $window_start]);
        $count = (int) $st->fetchColumn();

        // Prune stale windows — safe interpolation (integer from our own const).
        $pdo->exec(
            "DELETE FROM rate_limit_buckets
             WHERE window_start < NOW() - INTERVAL '" . ($window_secs * 2) . " seconds'"
        );
    } catch (PDOException $e) {
        // Table missing or DB error — log it but let the request through.
        error_log('rate_limit_check: ' . $e->getMessage());
        return;
    }

    if ($count > $max_hits) {
        http_response_code(429);
        // Show a user-friendly page instead of a bare 429 for browser requests.
        $retry = $window_secs - (time() % $window_secs);
        // If the page has already started rendering (e.g., header included),
        // just die with a plain message; otherwise render a minimal HTML page.
        if (headers_sent()) {
            die('<p style="font-family:sans-serif;padding:2rem">Too many attempts. Please wait ' . $retry . ' seconds and try again.</p>');
        }
        ?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Too many requests — Higoffe</title>
<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#faf9f7}
.box{text-align:center;padding:2.5rem;background:#fff;border-radius:1.25rem;max-width:360px;box-shadow:0 2px 16px rgba(0,0,0,.07)}
h1{font-size:1.3rem;color:#1c1917;margin:0 0 .5rem}p{color:#78716c;font-size:.9rem;margin:0 0 1.5rem}
a{display:block;padding:.75rem 1.5rem;background:#c0392b;color:#fff;border-radius:.75rem;text-decoration:none;font-weight:700;font-size:.875rem}</style>
</head>
<body><div class="box">
<h1>Too many attempts</h1>
<p>You've made too many requests. Please wait <?= $retry ?> seconds and try again.</p>
<a href="javascript:history.back()">Go back</a>
</div></body></html>
<?php
        exit;
    }
}

function _rl_normalise_ip(string $ip): string {
    // IPv6 → keep first 4 groups (simulate /64)
    if (str_contains($ip, ':')) {
        $parts = explode(':', $ip);
        return implode(':', array_slice($parts, 0, 4));
    }
    // IPv4 → keep first 3 octets (simulate /24)
    $parts = explode('.', $ip);
    if (count($parts) === 4) {
        return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
    }
    return $ip;
}
