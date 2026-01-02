<?php
require_once dirname(__DIR__) . '/models/Database.php';

class Chat {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Saves a new chat message.
     * @param int $user_id
     * @param string $message
     * @param string $message_type
     * @param int|null $media_id
     * @param int|null $call_duration
     * @return int|false The ID of the new message or false on failure.
     */
    public function saveMessage($user_id, $message, $message_type = 'text', $media_id = null, $call_duration = null) {
        $sql = "INSERT INTO chat_messages (user_id, message, message_type, media_id, call_duration) VALUES (:user_id, :message, :message_type, :media_id, :call_duration)";
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
            error_log("Chat message saving failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves all chat messages, optionally starting from a specific ID.
     * @param int $last_id
     * @return array
     */
    public function getMessages($last_id = 0) {
        $sql = "SELECT cm.*, u.username, u.role, m.file_path, m.file_type FROM chat_messages cm JOIN users u ON cm.user_id = u.id LEFT JOIN media m ON cm.media_id = m.id WHERE cm.id > :last_id ORDER BY cm.sent_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['last_id' => $last_id]);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a single chat message by ID.
     * @param int $id
     * @return array|false
     */
    public function getMessageById($id) {
        $sql = "SELECT cm.*, u.username, u.role, m.file_path, m.file_type FROM chat_messages cm JOIN users u ON cm.user_id = u.id LEFT JOIN media m ON cm.media_id = m.id WHERE cm.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Deletes a chat message by ID.
     * @param int $message_id
     * @return bool True on success, false on failure.
     */
    public function deleteMessage($message_id) {
        $sql = "DELETE FROM chat_messages WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(['id' => $message_id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Chat message deletion failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes all chat messages.
     * @return bool True on success, false on failure.
     */
    public function deleteAllMessages() {
        $sql = "DELETE FROM chat_messages";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute();
            return true;
        } catch (\PDOException $e) {
            error_log("Delete all chat messages failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sends an admin notification message to a specific user.
     * @param string $message
     * @param int $user_id
     * @return int|false The ID of the new message or false on failure.
     */
    public function sendAdminMessage($message, $user_id) {
        // Use a special user_id for admin messages (e.g., 0 or a dedicated admin user)
        $admin_user_id = 0; // 0 indicates system/admin message
        return $this->saveMessage($admin_user_id, $message, 'text');
    }
}
?>
