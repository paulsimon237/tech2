<?php
require_once dirname(__DIR__) . '/models/Database.php';

class Problem {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Creates a new problem entry.
     * @param int $user_id
     * @param string $title
     * @param string $description
     * @param string $category
     * @return int|false The ID of the new problem or false on failure.
     */
    public function create($user_id, $title, $description, $category) {
        $sql = "INSERT INTO problems (user_id, title, description, category) VALUES (:user_id, :title, :description, :category)";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute([
                'user_id' => $user_id,
                'title' => $title,
                'description' => $description,
                'category' => $category
            ]);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Problem creation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves all problems with user details.
     * @return array
     */
    public function getAll() {
        $sql = "SELECT p.*, u.username FROM problems p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves problems by user ID.
     * @param int $user_id
     * @return array
     */
    public function getByUserId($user_id) {
        $sql = "SELECT p.*, u.username FROM problems p JOIN users u ON p.user_id = u.id WHERE p.user_id = :user_id ORDER BY p.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a problem by ID.
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        $sql = "SELECT p.*, u.username FROM problems p JOIN users u ON p.user_id = u.id WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Deletes a problem by ID.
     * @param int $id
     * @return bool True on success, false on failure.
     */
    public function delete($id) {
        $sql = "DELETE FROM problems WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Problem deletion failed: " . $e->getMessage());
            return false;
        }
    }
}
?>
