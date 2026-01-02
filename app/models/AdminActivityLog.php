<?php
require_once dirname(__DIR__) . '/models/Database.php';

class AdminActivityLog {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Logs an admin activity.
     * @param int $admin_id
     * @param string $action
     * @param string $details
     * @param string $ip_address
     * @return bool True on success, false on failure.
     */
    public function logActivity($admin_id, $action, $details, $ip_address) {
        $sql = "INSERT INTO admin_activity_log (admin_id, action, details, ip_address) VALUES (:admin_id, :action, :details, :ip_address)";
        $stmt = $this->db->prepare($sql);
        try {
            return $stmt->execute([
                'admin_id' => $admin_id,
                'action' => $action,
                'details' => $details,
                'ip_address' => $ip_address
            ]);
        } catch (\PDOException $e) {
            error_log("Activity log failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets activity logs for a specific admin or all admins.
     * @param int|null $admin_id
     * @param int $limit
     * @return array Array of activity logs.
     */
    public function getLogs($admin_id = null, $limit = 50) {
        $sql = "SELECT a.*, u.username FROM admin_activity_log a JOIN users u ON a.admin_id = u.id";
        if ($admin_id) {
            $sql .= " WHERE a.admin_id = :admin_id";
        }
        $sql .= " ORDER BY a.created_at DESC LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $params = ['limit' => $limit];
        if ($admin_id) {
            $params['admin_id'] = $admin_id;
        }
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>
