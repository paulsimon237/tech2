<?php
require_once dirname(__DIR__) . '/models/Database.php';

class AdminChat {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Saves a new admin chat message.
     * @param int $user_id
     * @param string $message
     * @param string $message_type
     * @param int|null $media_id
     * @param int|null $call_duration
     * @return int|false The ID of the new message or false on failure.
     */
    public function saveMessage($user_id, $message, $message_type = 'text', $media_id = null, $call_duration = null) {
        $sql = "INSERT INTO admin_chat_messages (user_id, message, message_type, media_id, call_duration) VALUES (:user_id, :message, :message_type, :media_id, :call_duration)";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute([
                'user_id' => $user_id,
                'message' => $message,
                'message_type' => $message_type,
                'media_id' => $media_id,
                'call_duration' => $call_duration
            ]);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Admin chat message saving failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves all admin chat messages, optionally starting from a specific ID.
     * @param int $last_id
     * @return array
     */
    public function getMessages($last_id = 0) {
        $sql = "SELECT acm.*, u.username, u.role, m.file_path, m.file_type FROM admin_chat_messages acm JOIN users u ON acm.user_id = u.id LEFT JOIN media m ON acm.media_id = m.id WHERE acm.id > :last_id ORDER BY acm.sent_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['last_id' => $last_id]);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a single admin chat message by ID.
     * @param int $id
     * @return array|false
     */
    public function getMessageById($id) {
        $sql = "SELECT acm.*, u.username, u.role, m.file_path, m.file_type FROM admin_chat_messages acm JOIN users u ON acm.user_id = u.id LEFT JOIN media m ON acm.media_id = m.id WHERE acm.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Deletes an admin chat message by ID.
     * @param int $message_id
     * @return bool True on success, false on failure.
     */
    public function deleteMessage($message_id) {
        $sql = "DELETE FROM admin_chat_messages WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(['id' => $message_id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Admin chat message deletion failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes all admin chat messages.
     * @return bool True on success, false on failure.
     */
    public function deleteAllMessages() {
        $sql = "DELETE FROM admin_chat_messages";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute();
            return true;
        } catch (\PDOException $e) {
            error_log("Delete all admin chat messages failed: " . $e->getMessage());
            return false;
        }
    }
}
?>
