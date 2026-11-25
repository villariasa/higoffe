<?php
// Copy this file to db_credentials.php (same folder) and fill in your real
// Supabase values. db_credentials.php is gitignored — never commit real
// credentials, only this example template.
//
// IMPORTANT: use the Transaction Pooler connection details (Supabase
// dashboard -> Project Settings -> Database -> Connection pooling),
// NOT the direct connection. The direct connection has a low connection
// cap and a handful of concurrent chat-polling tabs will exhaust it.
// Pooler host looks like "aws-0-xx-xxxx-1.pooler.supabase.com", port 6543.

define('DB_HOST', 'aws-0-xx-xxxx-1.pooler.supabase.com');
define('DB_PORT', '6543');
define('DB_NAME', 'postgres');
define('DB_USER', 'postgres.YOUR_PROJECT_REF');
define('DB_PASSWORD', 'YOUR_PASSWORD');
