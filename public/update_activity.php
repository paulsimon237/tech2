out should be 3 minutes<?php
require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';

// Ensure only Admin or Super Admin are allowed
AuthController::requireAuth([ROLE_ADMIN, ROLE_SUPER_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['action']) && $data['action'] === 'update_activity') {
        // Update the last activity time
        $_SESSION['last_activity'] = time();

        // Return success response
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

// If we get here, something went wrong
header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'Invalid request']);
exit;
?>
