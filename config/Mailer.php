<?php
// Minimal native SMTP client — no external library, no Composer (per
// CODING_STANDARDS.md). Supports STARTTLS (port 587) and implicit TLS
// (port 465) with AUTH LOGIN, which covers Gmail SMTP and most
// transactional SMTP providers (e.g. Mailtrap for local testing).
//
// Credential resolution, in priority order:
//   1. settings table (admin/settings.php) — lets an admin change SMTP
//      config from the UI with no redeploy.
//   2. SMTP_* environment variables (Render/production).
//   3. config/smtp_credentials.php (local development).
// Pass the app's PDO connection in so step 1 can run; omit it (or let the
// settings table be empty) and this falls back to steps 2-3 exactly like
// before.
class Mailer {
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $from_email;
    private string $from_name;
    private string $encryption; // 'tls' or 'ssl'

    public function __construct(?PDO $pdo = null) {
        $host = $port = $username = $password = $from_email = $from_name = $encryption = null;

        if ($pdo) {
            require_once __DIR__ . '/../models/Settings.php';
            $settings = new Settings($pdo);
            $host       = $settings->get('smtp_host');
            $port       = $settings->get('smtp_port');
            $username   = $settings->get('smtp_username');
            $password   = $settings->get('smtp_password');
            $from_email = $settings->get('smtp_from_email');
            $from_name  = $settings->get('smtp_from_name');
            $encryption = $settings->get('smtp_encryption');
        }

        if (!$host || !$port || !$username || !$password || !$from_email) {
            $host       = $host ?: getenv('SMTP_HOST');
            $port       = $port ?: getenv('SMTP_PORT');
            $username   = $username ?: getenv('SMTP_USERNAME');
            $password   = $password ?: getenv('SMTP_PASSWORD');
            $from_email = $from_email ?: getenv('SMTP_FROM_EMAIL');
            $from_name  = $from_name ?: getenv('SMTP_FROM_NAME');
            $encryption = $encryption ?: getenv('SMTP_ENCRYPTION');
        }

        if (!$host || !$port || !$username || !$password || !$from_email) {
            $credentials_file = __DIR__ . '/smtp_credentials.php';
            if (file_exists($credentials_file)) {
                require_once $credentials_file;
                $host       = $host ?: (defined('SMTP_HOST') ? SMTP_HOST : null);
                $port       = $port ?: (defined('SMTP_PORT') ? SMTP_PORT : null);
                $username   = $username ?: (defined('SMTP_USERNAME') ? SMTP_USERNAME : null);
                $password   = $password ?: (defined('SMTP_PASSWORD') ? SMTP_PASSWORD : null);
                $from_email = $from_email ?: (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : null);
                $from_name  = $from_name ?: (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : null);
                $encryption = $encryption ?: (defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : null);
            }
        }

        $this->host       = (string) $host;
        $this->port       = (int) $port;
        $this->username   = (string) $username;
        $this->password   = (string) $password;
        $this->from_email = (string) $from_email;
        $this->from_name  = (string) ($from_name ?: 'Higoffe');
        $this->encryption = (string) ($encryption ?: 'tls');
    }

    public function send(string $to_email, string $subject, string $html_body): bool {
        if (!$this->host || !$this->port || !$this->username || !$this->password || !$this->from_email) {
            error_log('Mailer: SMTP not configured, skipping send to ' . $to_email);
            return false;
        }

        $socket = $this->openSocket();
        if (!$socket) {
            return false;
        }

        try {
            $this->readResponse($socket, 220);
            $this->command($socket, "EHLO higoffe.local", 250);

            if ($this->encryption === 'tls') {
                $this->command($socket, "STARTTLS", 220);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    error_log('Mailer: STARTTLS upgrade failed');
                    return false;
                }
                $this->command($socket, "EHLO higoffe.local", 250);
            }

            $this->command($socket, "AUTH LOGIN", 334);
            $this->command($socket, base64_encode($this->username), 334);
            $this->command($socket, base64_encode($this->password), 235);

            $this->command($socket, "MAIL FROM:<{$this->from_email}>", 250);
            $this->command($socket, "RCPT TO:<{$to_email}>", 250);
            $this->command($socket, "DATA", 354);

            $headers = implode("\r\n", [
                "From: {$this->from_name} <{$this->from_email}>",
                "To: <{$to_email}>",
                "Subject: {$subject}",
                "MIME-Version: 1.0",
                "Content-Type: text/html; charset=UTF-8",
            ]);
            // Dot-stuff any line that starts with a literal "." per RFC 5321,
            // so the SMTP server doesn't mistake it for the end-of-data marker.
            $escaped_body = preg_replace('/^\./m', '..', $html_body);
            $this->command($socket, $headers . "\r\n\r\n" . $escaped_body . "\r\n.", 250);

            $this->command($socket, "QUIT", 221);
            return true;
        } catch (Exception $e) {
            error_log('Mailer: ' . $e->getMessage());
            return false;
        } finally {
            fclose($socket);
        }
    }

    private function openSocket() {
        $target = $this->encryption === 'ssl'
            ? "ssl://{$this->host}:{$this->port}"
            : "tcp://{$this->host}:{$this->port}";
        $socket = @stream_socket_client($target, $errno, $errstr, 15);
        if (!$socket) {
            error_log("Mailer: could not connect to {$target} — {$errstr}");
            return null;
        }
        return $socket;
    }

    private function command($socket, string $line, int $expected_code): string {
        fwrite($socket, $line . "\r\n");
        return $this->readResponse($socket, $expected_code);
    }

    private function readResponse($socket, int $expected_code): string {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            // The last line of a multi-line SMTP response has a space
            // (not a dash) as its 4th character.
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if ($code !== $expected_code) {
            throw new Exception("SMTP error: expected {$expected_code}, got: " . trim($response));
        }
        return $response;
    }
}
