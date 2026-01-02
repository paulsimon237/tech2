<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/SystemSettings.php';

class AuthController {
    private $userModel;
    private $settingsModel;
    private $firebaseAuth;

    public function __construct() {
        $this->userModel = new User();
        $this->settingsModel = new SystemSettings();
        global $auth;
        $this->firebaseAuth = $auth;
    }

    // User login method
    public function login($email, $password, $role_type) {
        $user = $this->userModel->findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            // Allow flexible role selection: if user has admin/super_admin role, they can login as admin
            // If user has user role, they must select user
            if ($user['role'] === 'user' && $role_type !== 'user') {
                return ['status' => 'error', 'message' => 'Invalid role selected for this account'];
            }
            if (in_array($user['role'], ['admin', 'super_admin']) && !in_array($role_type, ['admin', 'super_admin'])) {
                return ['status' => 'error', 'message' => 'Invalid role selected for this account'];
            }

            // Check if user is active
            if (!$user['is_active']) {
                return ['status' => 'error', 'message' => 'Account is suspended'];
            }

            // Generate token using Firebase Auth
            try {
                $customToken = $this->firebaseAuth->createCustomToken((string)$user['id']); // Ensure ID is a string
                if (!empty($customToken)) {
                    if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                    }
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role']; // Ensure the role is retrieved from the database
                    return ['status' => 'success', 'token' => $customToken];
                } else {
                    error_log('Firebase returned an empty token for user ID: ' . $user['id']);
                }
            } catch (Exception $e) {
                error_log('Token generation failed: ' . $e->getMessage());
                return ['status' => 'error', 'message' => 'Token generation failed: ' . $e->getMessage()];
            }
        }
        return ['status' => 'error', 'message' => 'Invalid credentials'];
    }

    // User logout method
    public function logout() {
        // Invalidate session or token
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        return ['status' => 'success', 'message' => 'Logged out successfully'];
    }

    // Verify token method
    public function verifyToken($token) {
        try {
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($token);
            return ['status' => 'success', 'uid' => $verifiedIdToken->getPayload()['sub']];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Token verification failed: ' . $e->getMessage()];
        }
    }

    // Register new user
    public function register($email, $password, $name) {
        // Update to use the correct method from the User model
        if ($this->userModel->findByEmail($email)) {
            return ['status' => 'error', 'message' => 'Email already exists'];
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $this->userModel->create($name, $email, $hashedPassword);
        return ['status' => 'success', 'message' => 'User registered successfully'];
    }

    // Require authentication and role
    public function requireAuth($allowedRoles) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], (array)$allowedRoles)) {
            header('Location: login.php');
            exit;
        }
    }

    // Firebase login method
    public function firebaseLogin($idToken, $roleType) {
        try {
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($idToken);
            $uid = $verifiedIdToken->getClaim('sub');
            $email = $verifiedIdToken->getClaim('email');
            $name = $verifiedIdToken->getClaim('name') ?? $email;

            // Find user by Firebase UID
            $user = $this->userModel->findByFirebaseUid($uid);

            if (!$user) {
                // User doesn't exist, create them with the selected role
                // Google authentication acts as both login and registration
                $username = $this->generateUniqueUsername($email);

                // Use the role selected during login for new Google users
                // This allows Google auth to serve as registration with role selection
                $userId = $this->userModel->create($username, $email, '', $roleType, $uid);
                if (!$userId) {
                    return 'Failed to create user account';
                }

                $user = $this->userModel->findById($userId);
                if (!$user) {
                    return 'Failed to retrieve created user';
                }
            } else {
                // Existing user - Google auth is only for users
                if ($user['role'] !== 'user') {
                    return 'Google authentication is only available for regular users';
                }
            }

            if (!$user['is_active']) {
                return 'Account is suspended';
            }

            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            return null; // No error
        } catch (Exception $e) {
            error_log('Firebase token verification failed: ' . $e->getMessage());
            return 'Token verification failed';
        }
    }

    // Helper method to generate unique username from email
    private function generateUniqueUsername($email) {
        $baseUsername = explode('@', $email)[0];
        $username = $baseUsername;
        $counter = 1;

        while ($this->userModel->findByUsername($username)) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }
}

?>
