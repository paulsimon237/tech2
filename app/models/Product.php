<?php
require_once dirname(__DIR__) . '/models/Database.php';

class Product {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Creates a new product entry.
     * @param int $user_id
     * @param string $name
     * @param string $description
     * @param string|null $demo_video_path
     * @return int|false The ID of the new product or false on failure.
     */
    public function create($user_id, $name, $description, $demo_video_path = null) {
        $sql = "INSERT INTO products (user_id, name, description, demo_video_path) VALUES (:user_id, :name, :description, :demo_video_path)";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute([
                'user_id' => $user_id,
                'name' => $name,
                'description' => $description,
                'demo_video_path' => $demo_video_path
            ]);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Product creation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves all products with user details.
     * @return array
     */
    public function getAll() {
        $sql = "SELECT p.*, u.username FROM products p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves products by user ID.
     * @param int $user_id
     * @return array
     */
    public function getByUserId($user_id) {
        $sql = "SELECT p.*, u.username FROM products p JOIN users u ON p.user_id = u.id WHERE p.user_id = :user_id ORDER BY p.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a product by ID.
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        $sql = "SELECT p.*, u.username FROM products p JOIN users u ON p.user_id = u.id WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Deletes a product by ID.
     * @param int $id
     * @return bool True on success, false on failure.
     */
    public function delete($id) {
        $sql = "DELETE FROM products WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Product deletion failed: " . $e->getMessage());
            return false;
        }
    }
}
?>
