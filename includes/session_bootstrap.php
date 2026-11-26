<?php
// Long-lived, sliding-expiration session. A frequently-active user is
// never logged out; a device that's been untouched for 30 days is. PHP's
// default session (short gc_maxlifetime, cookie dies when the browser
// fully closes) reads as "randomly logged out" on a phone, where closing
// the app happens constantly — this fixes that without ever trusting a
// stale session past the idle window.
define('HIGOFFE_SESSION_IDLE_SECONDS', 30 * 24 * 60 * 60); // 30 days

function higoffe_session_start(): void {
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    ini_set('session.gc_maxlifetime', (string) HIGOFFE_SESSION_IDLE_SECONDS);
    session_set_cookie_params([
        'lifetime' => HIGOFFE_SESSION_IDLE_SECONDS,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (!empty($_SESSION['user_id']) && isset($_SESSION['last_seen_at'])
        && (time() - (int) $_SESSION['last_seen_at']) > HIGOFFE_SESSION_IDLE_SECONDS) {
        session_unset();
        session_destroy();
        session_start(); // fresh, logged-out session for the rest of this request
        return;
    }

    $_SESSION['last_seen_at'] = time();

    // Sliding expiration — re-issue the cookie with a fresh expiry on every
    // active request, so an account used daily (or more) never hits the
    // idle window above.
    if (ini_get('session.use_cookies') && !headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires'  => time() + HIGOFFE_SESSION_IDLE_SECONDS,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
    }
}
