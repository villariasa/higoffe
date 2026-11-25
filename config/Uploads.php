<?php
// Generates short-lived, HMAC-signed upload authorization consumed by
// higoffe-upload-worker (a Cloudflare Worker bound to an R2 bucket). The
// PHP app never touches R2 directly and never stores any R2/AWS
// credentials — it only shares one signing secret with the Worker, which
// verifies the HMAC + expiry before accepting an upload. Credential
// resolution mirrors config/Database.php and config/Mailer.php: env vars
// first, config/upload_credentials.php second.
class Uploads {
    private static function resolve(string $env_name, string $constant_name): string {
        $value = getenv($env_name);
        if (!$value) {
            $credentials_file = __DIR__ . '/upload_credentials.php';
            if (file_exists($credentials_file)) {
                require_once $credentials_file;
                $value = defined($constant_name) ? constant($constant_name) : null;
            }
        }
        return (string) $value;
    }

    public static function workerUrl(): string {
        return self::resolve('UPLOAD_WORKER_URL', 'UPLOAD_WORKER_URL');
    }

    // Host of the R2 public bucket (e.g. "pub-xxxx.r2.dev" or a custom
    // domain) — the ONLY place a legitimate image_url/avatar_url/etc.
    // should ever point to, since every upload goes through our own
    // Worker. Optional: if unset, isAllowedMediaUrl() still blocks
    // dangerous schemes but won't pin the host.
    public static function publicHost(): string {
        $raw = trim(self::resolve('UPLOAD_PUBLIC_HOST', 'UPLOAD_PUBLIC_HOST'));
        if ($raw === '') {
            return '';
        }
        if (str_contains($raw, '://')) {
            return (string) (parse_url($raw, PHP_URL_HOST) ?? '');
        }
        return rtrim(explode('/', $raw, 2)[0], '/');
    }

    // Every endpoint that stores a client-submitted URL into a DB image
    // field (image_url, logo_url, cover_url, avatar_url, proof_url, chat
    // image_url) MUST pass it through this before saving — the hidden
    // field is filled by our own JS after a real upload, but nothing
    // stops a malicious client from POSTing the form directly with an
    // arbitrary string, including a `javascript:` URL that would later
    // execute when rendered inside an <a href>.
    public static function isAllowedMediaUrl(string $url): bool {
        if ($url === '') {
            return true; // empty is fine — "no image" is a valid value everywhere this is used
        }
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return false;
        }
        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }
        $allowed_hosts = array_filter([
            self::publicHost(),
            (string) (parse_url(self::workerUrl(), PHP_URL_HOST) ?? ''),
        ]);
        if (!empty($allowed_hosts)) {
            foreach ($allowed_hosts as $host) {
                if (strcasecmp($parts['host'], $host) === 0) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    private static function secret(): string {
        return self::resolve('UPLOAD_SIGNING_SECRET', 'UPLOAD_SIGNING_SECRET');
    }

    // One signed, time-limited (5 min) authorization for a single upload.
    // $scope organizes files inside the bucket (e.g. "product", "post",
    // "cafe", "payment_proof") — it is NOT the security boundary by
    // itself, the HMAC is; scope just becomes the storage folder.
    public static function issueToken(int $user_id, string $scope): array {
        $expiry = time() + 300;
        $token = hash_hmac('sha256', "{$expiry}:{$user_id}:{$scope}", self::secret());
        return [
            "token"      => $token,
            "expiry"     => $expiry,
            "user_id"    => $user_id,
            "scope"      => $scope,
            "worker_url" => self::workerUrl(),
        ];
    }
}
