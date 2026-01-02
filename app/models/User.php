<?php
require_once dirname(__DIR__) . '/models/Database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Finds a user by email.
     * @param string $email
     * @return array|false
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Finds a user by username.
     * @param string $username
     * @return array|false
     */
    public function findByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }

    /**
     * Finds a user by ID.
     * @param int $id
     * @return array|false
     */
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Finds a user by Firebase UID.
     * @param string $firebaseUid
     * @return array|false
     */
    public function findByFirebaseUid($firebaseUid) {
        $sql = "SELECT * FROM users WHERE firebase_uid = :firebase_uid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['firebase_uid' => $firebaseUid]);
        return $stmt->fetch();
    }

    /**
     * Registers a new user.
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $role
     * @param string|null $firebaseUid
     * @return int|false The ID of the new user or false on failure.
     */
    public function create($username, $email, $password, $role = ROLE_USER, $firebaseUid = null) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password, role, firebase_uid) VALUES (:username, :email, :password, :role, :firebase_uid)";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashed_password,
                'role' => $role,
                'firebase_uid' => $firebaseUid
            ]);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            // Log error, e.g., duplicate email
            error_log("User creation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Authenticates a user.
     * @param string $email
     * @param string $password
     * @return array|false User data on success, false on failure.
     */
    public function login($email, $password) {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    /**
     * Gets the count of current Admin users.
     * @return int
     */
    public function getAdminCount() {
        $sql = "SELECT COUNT(*) FROM users WHERE role = :role";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['role' => ROLE_ADMIN]);
        return $stmt->fetchColumn();
    }

    /**
     * Updates a user's profile.
     * @param int $userId
     * @param string $username
     * @param string $email
     * @param string $profilePicPath
     * @param string|null $password Optional new password (will be hashed if provided)
     * @return bool True on success, false on failure.
     */
    public function updateProfile($userId, $username, $email, $profilePicPath, $password = null) {
        // Check if username is taken by another user
        $existingUser = $this->findByUsername($username);
        if ($existingUser && $existingUser['id'] != $userId) {
            error_log("Profile update failed: Username already taken");
            return false;
        }

        // Check if email is taken by another user
        $existingUser = $this->findByEmail($email);
        if ($existingUser && $existingUser['id'] != $userId) {
            error_log("Profile update failed: Email already taken");
            return false;
        }

        $sql = "UPDATE users SET username = :username, email = :email";
        $params = [
            'username' => $username,
            'email' => $email,
            'id' => $userId
        ];

        if ($profilePicPath !== null) {
            $sql .= ", profile_pic = :profile_pic";
            $params['profile_pic'] = $profilePicPath;
        }

        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql .= ", password = :password";
            $params['password'] = $hashed_password;
        }

        $sql .= " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute($params);
            return true;
        } catch (\PDOException $e) {
            error_log("Profile update failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets all users.
     * @return array Array of all users.
     */
    public function getAll() {
        $sql = "SELECT id, username, email, role, is_active, created_at FROM users ORDER BY role DESC, created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Deletes a user by ID.
     * @param int $id
     * @return bool True on success, false on failure.
     */
    public function delete($id) {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("User deletion failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Suspends a user by setting is_active to FALSE.
     * @param int $id
     * @return bool True on success, false on failure.
     */
    public function suspend($id) {
        $sql = "UPDATE users SET is_active = FALSE WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("User suspension failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Activates a user by setting is_active to TRUE.
     * @param int $id
     * @return bool True on success, false on failure.
     */
    public function activate($id) {
        $sql = "UPDATE users SET is_active = TRUE WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("User activation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates a user's role.
     * @param int $id
     * @param string $role
     * @return bool True on success, false on failure.
     */
    public function updateRole($id, $role) {
        $sql = "UPDATE users SET role = :role WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(['role' => $role, 'id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("User role update failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates user data.
     * @param int $id
     * @param array $data
     * @return bool True on success, false on failure.
     */
    public function update($id, $data) {
        $setParts = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            $setParts[] = "$key = :$key";
            $params[$key] = $value;
        }

        $sql = "UPDATE users SET " . implode(', ', $setParts) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("User update failed: " . $e->getMessage());
            return false;
        }
    }
}
?>
