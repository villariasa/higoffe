<?php
// Copy to config/paymongo_credentials.php for local dev (never commit the real file).
// In production (Render), set PAYMONGO_SECRET_KEY and PAYMONGO_WEBHOOK_SECRET as env vars.
define('PAYMONGO_SECRET_KEY', 'sk_test_your_secret_key_here');
define('PAYMONGO_WEBHOOK_SECRET', 'whsec_your_webhook_secret_here');
