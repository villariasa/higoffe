<?php
// PayMongo Checkout Sessions + Webhook helper.
// Resolves PAYMONGO_SECRET_KEY and PAYMONGO_WEBHOOK_SECRET from environment
// variables first, falling back to config/paymongo_credentials.php for local dev.
class PayMongo {
    private static function resolve(string $env_name, string $constant_name): string {
        $value = getenv($env_name);
        if (!$value) {
            $credentials_file = __DIR__ . '/paymongo_credentials.php';
            if (file_exists($credentials_file)) {
                require_once $credentials_file;
                $value = defined($constant_name) ? constant($constant_name) : null;
            }
        }
        return trim((string) $value);
    }

    public static function secretKey(): string {
        return self::resolve('PAYMONGO_SECRET_KEY', 'PAYMONGO_SECRET_KEY');
    }

    public static function webhookSecret(): string {
        return self::resolve('PAYMONGO_WEBHOOK_SECRET', 'PAYMONGO_WEBHOOK_SECRET');
    }

    public static function isConfigured(): bool {
        return self::secretKey() !== '';
    }

    public static function baseUrl(): string {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            ? 'https'
            : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "{$proto}://{$host}";
    }

    public static function createCheckoutSession(array $order, array $items, string $returnUrl, string $cancelUrl): array {
        $secret = self::secretKey();
        if ($secret === '') {
            return ['ok' => false, 'error' => 'PayMongo secret key is not configured.'];
        }

        $lineItems = [];
        foreach ($items as $item) {
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $lineTotalCentavos = (int) round(((float) ($item['line_total'] ?? 0)) * 100);
            $unitCentavos = max(100, (int) round($lineTotalCentavos / $qty));
            $name = (string) ($item['product_name'] ?? 'Item');
            if (!empty($item['size_label'])) {
                $name .= ' (' . $item['size_label'] . ')';
            }
            $lineItems[] = [
                'currency' => 'PHP',
                'amount' => $unitCentavos,
                'name' => $name,
                'quantity' => $qty,
            ];
        }

        if (empty($lineItems)) {
            $lineItems[] = [
                'currency' => 'PHP',
                'amount' => max(100, (int) round(((float) $order['total_amount']) * 100)),
                'name' => 'Higoffe Order #' . (int) $order['order_id'],
                'quantity' => 1,
            ];
        }

        $additional = (int) round(((float) ($order['additional_charges'] ?? 0)) * 100);
        if ($additional > 0) {
            $lineItems[] = [
                'currency' => 'PHP',
                'amount' => $additional,
                'name' => 'Additional charges',
                'quantity' => 1,
            ];
        }

        $payload = [
            'data' => [
                'attributes' => [
                    'send_email_receipt' => false,
                    'show_description' => true,
                    'show_line_items' => true,
                    'description' => 'Higoffe Order #' . (int) $order['order_id'] . ' — ' . ($order['cafe_name'] ?? 'Café'),
                    'line_items' => $lineItems,
                    'payment_method_types' => ['gcash', 'paymaya', 'qrph', 'card'],
                    'reference_number' => 'HIGOFFE-' . (int) $order['order_id'],
                    'metadata' => [
                        'order_id' => (string) (int) $order['order_id'],
                    ],
                    'success_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                ],
            ],
        ];

        return self::request('POST', 'https://api.paymongo.com/v1/checkout_sessions', $payload);
    }

    public static function getCheckoutSession(string $sessionId): array {
        return self::request('GET', 'https://api.paymongo.com/v1/checkout_sessions/' . rawurlencode($sessionId));
    }

    public static function verifyWebhookSignature(string $rawBody, string $sigHeader): bool {
        $secret = self::webhookSecret();
        if ($secret === '' || $sigHeader === '') {
            return false;
        }
        $parts = [];
        foreach (explode(',', $sigHeader) as $segment) {
            $kv = explode('=', trim($segment), 2);
            if (count($kv) === 2) {
                $parts[$kv[0]] = $kv[1];
            }
        }
        $timestamp = $parts['t'] ?? '';
        $signature = $parts['te'] ?? ($parts['li'] ?? '');
        if ($timestamp === '' || $signature === '') {
            return false;
        }
        $expected = hash_hmac('sha256', "{$timestamp}.{$rawBody}", $secret);
        return hash_equals($expected, $signature);
    }

    private static function request(string $method, string $url, ?array $payload = null): array {
        $secret = self::secretKey();
        $ch = curl_init($url);
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($secret . ':'),
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($body === false || $curlErr !== '') {
            return ['ok' => false, 'error' => $curlErr ?: 'Failed to connect to PayMongo.'];
        }

        $decoded = json_decode($body, true);
        if ($httpCode < 200 || $httpCode >= 300 || !is_array($decoded)) {
            $msg = $decoded['errors'][0]['detail'] ?? ('PayMongo HTTP ' . $httpCode);
            return ['ok' => false, 'error' => $msg];
        }

        return ['ok' => true, 'data' => $decoded['data'] ?? []];
    }
}
