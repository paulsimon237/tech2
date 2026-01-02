<?php
require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['idToken']) || !isset($input['role_type'])) {
        throw new Exception('Missing required parameters');
    }

    $idToken = $input['idToken'];
    $role_type = $input['role_type'];

    // Log the received idToken and roleType for debugging
    error_log('Received idToken: ' . substr($idToken, 0, 50) . '...'); // Log partial token for security
    error_log('Received roleType: ' . $role_type);

    $authController = new AuthController();
    $error = $authController->firebaseLogin($idToken, $role_type);

    // Log session data for debugging (session is started in config.php)
    error_log('Session data after login: ' . print_r($_SESSION, true));

    if ($error) {
        error_log('Firebase login failed: ' . $error);
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => $error]);
    } else {
        error_log('Firebase login successful');
        // Determine redirect based on actual user role from session, not the selected role_type
        $redirectUrl = in_array($_SESSION['role'], ['admin', 'super_admin']) ? 'admin_dashboard.php' : 'user_dashboard.php';
        error_log('Redirecting to: ' . $redirectUrl . ' based on actual role: ' . $_SESSION['role']);
        // Ensure session is written
        session_write_close();
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'redirect' => $redirectUrl
        ]);
    }
} catch (Exception $e) {
    error_log('Error in firebase_auth.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
