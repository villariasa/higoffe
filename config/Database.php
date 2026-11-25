<?php
// PDO connection class — Supabase PostgreSQL via the Transaction Pooler
// (see db_credentials.example.php for why the pooler, not the direct port).
class Database {
    public function connect(): PDO {
        $credentials_file = __DIR__ . '/db_credentials.php';
        if (!file_exists($credentials_file)) {
            die("Database Connection Error: config/db_credentials.php is missing. "
                . "Copy config/db_credentials.example.php to config/db_credentials.php "
                . "and fill in your Supabase pooler credentials.");
        }
        require_once $credentials_file;

        try {
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }
}
