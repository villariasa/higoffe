<?php
// Simple key/value settings store — backs admin/settings.php (SMTP config
// editable from the admin UI, no redeploy needed).
class Settings {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function get(string $key, ?string $default = null): ?string {
        $sql = "SELECT setting_value FROM settings WHERE setting_key = :key";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["key" => $key]);
        $value = $stmt->fetchColumn();
        return $value !== false && $value !== null ? $value : $default;
    }

    public function set(string $key, string $value): void {
        $sql = "INSERT INTO settings (setting_key, setting_value, updated_at)
                VALUES (:key, :value, NOW())
                ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value, updated_at = NOW()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["key" => $key, "value" => $value]);
    }

    // Returns every stored setting as $key => $value, for prefilling a
    // settings form.
    public function getAll(): array {
        $sql = "SELECT setting_key, setting_value FROM settings";
        $stmt = $this->pdo->query($sql);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
        return $result;
    }
}
