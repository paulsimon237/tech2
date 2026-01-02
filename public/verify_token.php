<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../app/config.php';
require_once '../app/models/User.php';
require_once '../app/models/SystemSettings.php';

session_start();

use Kreait\Firebase\Factory;

// Initialize Firebase Auth
$factory = (new Factory)->withServiceAccount('../firebase-service-account.json');
$auth = $factory->createAuth();

$idToken = $_POST['idToken'] ?? '';
$role_type = $_POST['role_type'] ?? 'user';

if (!$idToken) {
    echo json_encode(['success' => false, 'message' => 'No token received']);
    exit;
}

try {
    $verifiedIdToken = $auth->verifyIdToken($idToken);
    $uid = $verifiedIdToken->getClaim('sub');
    $email = $verifiedIdToken->getClaim('email');
    $name = $verifiedIdToken->getClaim('name') ?? '';

    // Check if user exists in database
    $userModel = new User();
    $user = $userModel->findByEmail($email);

    if (!$user) {
        // Create user if doesn't exist (for Firebase users)
        $username = explode('@', $email)[0]; // Use email prefix as username
        $userId = $userModel->create($username, $email, bin2hex(random_bytes(16)), $role_type); // Random password since Firebase handles auth
        if (!$userId) {
            throw new Exception('Failed to create user account');
        }
        $user = $userModel->findById($userId);
    }

    // Check if user is active
    if (!$user['is_active']) {
        throw new Exception('Your account has been suspended.');
    }

    // Check system maintenance mode
    $settingsModel = new SystemSettings();
    $maintenance_mode = $settingsModel->get(SETTING_MAINTENANCE_MODE);
    if ($maintenance_mode == '1' && $user['role'] !== ROLE_SUPER_ADMIN) {
        throw new Exception('The system is currently under maintenance. Only super administrators can access the system at this time.');
    }

    // Check role match
    if ($role_type === 'admin' && !in_array($user['role'], [ROLE_ADMIN, ROLE_SUPER_ADMIN])) {
        throw new Exception('Invalid role selection for this account.');
    } elseif ($role_type === 'user' && $user['role'] !== ROLE_USER) {
        throw new Exception('Invalid role selection for this account.');
    }

    // Set session variables (matching AuthController format)
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();
    $_SESSION['maintenance_version'] = $settingsModel->get(SETTING_MAINTENANCE_VERSION) ?: 0;

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => ($user['role'] === ROLE_USER) ? 'user_dashboard.php' : 'admin_dashboard.php'
    ]);

} catch (Exception $e) {
    error_log('Error in verify_token.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
