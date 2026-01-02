<?php
require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/models/Media.php';

// Ensure user is authenticated
AuthController::requireAuth();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['audio'])) {
    $mediaModel = new Media();

    $file = $_FILES['audio'];
    $media_id = $mediaModel->uploadFile($user_id, $file);

    if ($media_id) {
        echo json_encode(['success' => true, 'media_id' => $media_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload audio file']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>
