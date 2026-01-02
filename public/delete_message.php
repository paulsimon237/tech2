<?php
require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/models/Chat.php';

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['message_id'])) {
    $message_id = (int)$_GET['message_id'];

    $chatModel = new Chat();
    $message = $chatModel->getMessageById($message_id);

    if (!$message) {
        echo json_encode(['success' => false, 'error' => 'Message not found']);
        exit;
    }

    // Allow delete if admin/superadmin or message owner
    if ($role === ROLE_ADMIN || $role === ROLE_SUPER_ADMIN || $message['user_id'] == $user_id) {
        if ($chatModel->deleteMessage($message_id)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete message']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>
