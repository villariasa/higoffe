<?php
// PDO connection class — Supabase PostgreSQL via the Transaction Pooler
// Supports environment variables (for Render/production) and config/db_credentials.php (for local development).
class Database {
    public function connect(): PDO {
        $host = getenv('DB_HOST');
        $port = getenv('DB_PORT');
        $name = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');

        // Fallback to local config file if environment variables are not set
        if (!$host || !$port || !$name || !$user || !$password) {
            $credentials_file = __DIR__ . '/db_credentials.php';
            if (file_exists($credentials_file)) {
                require_once $credentials_file;
                $host = defined('DB_HOST') ? DB_HOST : $host;
                $port = defined('DB_PORT') ? DB_PORT : $port;
                $name = defined('DB_NAME') ? DB_NAME : $name;
                $user = defined('DB_USER') ? DB_USER : $user;
                $password = defined('DB_PASSWORD') ? DB_PASSWORD : $password;
            }
        }

        if (!$host || !$port || !$name || !$user || !$password) {
            die("Database Connection Error: Missing database credentials. "
                . "Please set DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD environment variables "
                . "or configure config/db_credentials.php.");
        }

        try {
            $dsn = "pgsql:host=" . $host . ";port=" . $port . ";dbname=" . $name;
            $pdo = new PDO($dsn, $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }
}
