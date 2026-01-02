<?php
require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/models/AdminChat.php';

$authController = new AuthController();
$authController->requireAuth([ROLE_ADMIN, ROLE_SUPER_ADMIN]);

$adminChatModel = new AdminChat();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get messages
    $last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    $messages = $adminChatModel->getMessages($last_id);

    // Add is_admin flag for display purposes
    foreach ($messages as &$msg) {
        $msg['is_admin'] = ($msg['role'] === ROLE_ADMIN || $msg['role'] === ROLE_SUPER_ADMIN);
    }

    echo json_encode(['success' => true, 'messages' => $messages]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Send message
    $input = json_decode(file_get_contents('php://input'), true);
    $message_text = trim($input['message'] ?? '');

    if (empty($message_text)) {
        echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
        exit;
    }

    $new_id = $adminChatModel->saveMessage($user_id, $message_text);

    if ($new_id) {
        echo json_encode(['success' => true, 'message_id' => $new_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send message']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Delete all messages (admin only)
    if ($adminChatModel->deleteAllMessages()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete messages']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>
