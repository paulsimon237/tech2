<?php
// Database Credentials (PLACEHOLDERS - REPLACE WITH YOUR ACTUAL CREDENTIALS)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tech_support_db');

// Application Settings
define('APP_NAME', 'Multi-Role Tech Support & Community Platform');
define('BASE_URL', '/'); // Use relative path for simplicity in sandbox
define('ROOT_PATH', dirname(__DIR__) . '/');

// User Roles
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');

// Media Upload Settings
define('UPLOAD_DIR', ROOT_PATH . 'uploads/');
define('MAX_FILE_SIZE', 1024 * 1024 * 50); // 50MB max for all files
define('MAX_VIDEO_DURATION_SECONDS', 60); // 1 minute max for videos
define('MAX_ADMINS', 5);

// System Settings Keys
define('SETTING_MAX_ADMINS', 'max_admins');
define('SETTING_SYSTEM_BLOCKED', 'system_blocked');
define('SETTING_BLOCK_REASON', 'block_reason');
define('SETTING_MAINTENANCE_MODE', 'maintenance_mode');
define('SETTING_MAINTENANCE_VERSION', 'maintenance_version');

// Firebase Configuration
require_once __DIR__ . '/../vendor/autoload.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;

define('FIREBASE_CREDENTIALS_PATH', ROOT_PATH . 'firebase-service-account.json');
define('FIREBASE_PROJECT_ID', 'studio-3044054056-b37d9'); // Replace with your actual project ID

// Initialize Firebase
try {
    $factory = (new Factory)
        ->withServiceAccount(FIREBASE_CREDENTIALS_PATH)
        ->withProjectId(FIREBASE_PROJECT_ID);

    $auth = $factory->createAuth();
} catch (Exception $e) {
    // Log error but don't fail completely - traditional auth will still work
    error_log('Firebase initialization failed: ' . $e->getMessage());
    $auth = null;
}

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Maintenance mode check - logout all users except super_admin when enabled
if (isset($_SESSION['user_id'])) {
    require_once 'models/SystemSettings.php';
    $settingsModel = new SystemSettings();
    $maintenance_mode = $settingsModel->get(SETTING_MAINTENANCE_MODE);
    $maintenance_version = $settingsModel->get(SETTING_MAINTENANCE_VERSION);

    if ($maintenance_mode == '1') {
        $last_version = $_SESSION['maintenance_version'] ?? 0;
        if ($maintenance_version > $last_version && $_SESSION['role'] !== ROLE_SUPER_ADMIN) {
            // Maintenance mode was just enabled, logout non-super-admin users
            session_destroy();
            header('Location: login.php?maintenance=1');
            exit;
        }
        $_SESSION['maintenance_version'] = $maintenance_version;
    }
}
?>
