<?php
require_once dirname(__DIR__) . '/models/Database.php';

class Tutorial {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Creates a new tutorial entry.
     * @param int $user_id
     * @param string $title
     * @param string $content
     * @param string $category
     * @return int|false The ID of the new tutorial or false on failure.
     */
    public function create($user_id, $title, $content, $category) {
        $sql = "INSERT INTO tutorials (user_id, title, content, category) VALUES (:user_id, :title, :content, :category)";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute([
                'user_id' => $user_id,
                'title' => $title,
                'content' => $content,
                'category' => $category
            ]);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Tutorial creation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves all tutorials with user details.
     * @return array
     */
    public function getAll() {
        $sql = "SELECT t.*, u.username FROM tutorials t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves all approved tutorials with user details.
     * @return array
     */
    public function getApproved() {
        $sql = "SELECT t.*, u.username FROM tutorials t JOIN users u ON t.user_id = u.id WHERE t.status = 'approved' ORDER BY t.created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves pending tutorials with user details.
     * @return array
     */
    public function getPending() {
        $sql = "SELECT t.*, u.username FROM tutorials t JOIN users u ON t.user_id = u.id WHERE t.status = 'pending' ORDER BY t.created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a tutorial by ID with user details.
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        $sql = "SELECT t.*, u.username FROM tutorials t JOIN users u ON t.user_id = u.id WHERE t.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Retrieves tutorials by user ID.
     * @param int $user_id
     * @return array
     */
    public function getByUserId($user_id) {
        $sql = "SELECT t.*, u.username FROM tutorials t JOIN users u ON t.user_id = u.id WHERE t.user_id = :user_id ORDER BY t.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll();
    }

    /**
     * Approves a tutorial.
     * @param int $tutorial_id
     * @param int $approved_by
     * @return bool
     */
    public function approve($tutorial_id, $approved_by) {
        $sql = "UPDATE tutorials SET status = 'approved', approved_by = :approved_by, approved_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(['id' => $tutorial_id, 'approved_by' => $approved_by]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Tutorial approval failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rejects a tutorial by setting status to 'rejected'.
     * @param int $tutorial_id
     * @param int $approved_by
     * @return bool
     */
    public function reject($tutorial_id, $approved_by) {
        $sql = "UPDATE tutorials SET status = 'rejected', approved_by = :approved_by, approved_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(['id' => $tutorial_id, 'approved_by' => $approved_by]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Tutorial rejection failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a tutorial by ID.
     * @param int $id
     * @return bool True on success, false on failure.
     */
    public function delete($id) {
        $sql = "DELETE FROM tutorials WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Tutorial deletion failed: " . $e->getMessage());
            return false;
        }
    }
}
?>
