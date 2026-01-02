<?php
require_once dirname(__DIR__) . '/models/Database.php';

class SystemSettings {
    private $db;
    private $lastError;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->lastError = null;
    }

    /**
     * Gets a setting value by key.
     * @param string $key
     * @return string|null The setting value or null if not found.
     */
    public function get($key) {
        $sql = "SELECT setting_value FROM system_settings WHERE setting_key = :key";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['key' => $key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : null;
    }

    /**
     * Sets a setting value by key.
     * @param string $key
     * @param string $value
     * @return bool True on success, false on failure.
     */
    public function set($key, $value) {
        $sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES (:key, :value)
                ON DUPLICATE KEY UPDATE setting_value = :value";
        $stmt = $this->db->prepare($sql);
        try {
            return $stmt->execute(['key' => $key, 'value' => $value]);
        } catch (\PDOException $e) {
            error_log("Setting update failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets all settings.
     * @return array Array of all settings.
     */
    public function getAll() {
        $sql = "SELECT setting_key, setting_value FROM system_settings ORDER BY setting_key";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * Updates multiple settings.
     * @param array $settings Array of key-value pairs to update.
     * @return bool True on success, false on failure.
     */
    public function updateSettings($settings) {
        try {
            $this->db->beginTransaction();
            foreach ($settings as $key => $value) {
                if (!$this->set($key, $value)) {
                    throw new \PDOException("Failed to update setting: $key");
                }
            }
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Settings update failed: " . $e->getMessage());
            // Store the error message for debugging
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Gets the last error message.
     * @return string|null The last error message or null if no error.
     */
    public function getLastError() {
        return isset($this->lastError) ? $this->lastError : null;
    }
}
?>
