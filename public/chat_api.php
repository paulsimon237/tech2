<?php
require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/models/Chat.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$chatModel = new Chat();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch messages
    $last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    $messages = $chatModel->getMessages($last_id);

    // Format the messages for the frontend, excluding signaling messages
    $formatted_messages = [];
    foreach ($messages as $msg) {
        if ($msg['message_type'] === 'call') {
            continue; // Skip signaling messages
        }
        $formatted_messages[] = [
            'id' => $msg['id'],
            'username' => htmlspecialchars($msg['username']),
            'message' => htmlspecialchars($msg['message']),
            'message_type' => $msg['message_type'],
            'media_path' => $msg['file_path'] ? BASE_URL . $msg['file_path'] : null,
            'file_type' => $msg['file_type'],
            'call_duration' => $msg['call_duration'],
            'sent_at' => $msg['sent_at'],
            'is_admin' => in_array($msg['role'], [ROLE_ADMIN, ROLE_SUPER_ADMIN]),
            'is_mine' => $msg['user_id'] == $user_id
        ];
    }

    echo json_encode(['success' => true, 'messages' => $formatted_messages, 'current_user_role' => $_SESSION['role']]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Send message
    $data = json_decode(file_get_contents('php://input'), true);
    $message_text = trim($data['message'] ?? '');
    $message_type = $data['message_type'] ?? 'text';
    $media_id = $data['media_id'] ?? null;
    $call_duration = $data['call_duration'] ?? null;

    if ($message_type === 'text' && empty($message_text)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message cannot be empty']);
        exit;
    }

    $new_id = $chatModel->saveMessage($user_id, $message_text, $message_type, $media_id, $call_duration);

    if ($new_id) {
        echo json_encode(['success' => true, 'id' => $new_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save message']);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Delete all messages (admin and super admin only)
    if (!in_array($_SESSION['role'], [ROLE_ADMIN, ROLE_SUPER_ADMIN])) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: Insufficient permissions']);
        exit;
    }

    if ($chatModel->deleteAllMessages()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete messages']);
    }

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}
?>
