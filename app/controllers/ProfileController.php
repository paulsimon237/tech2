<?php
require_once dirname(__DIR__) . '/models/User.php';

class ProfileController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function updateProfile($userId, $username, $email, $profilePic, $password = null) {
        error_log("ProfileController::updateProfile called - UserID: $userId, Username: $username, Email: $email, Password: " . (!empty($password) ? 'provided' : 'empty'));

        // Handle file upload for profile picture
        $profilePicPath = null;
        if ($profilePic && $profilePic['error'] === UPLOAD_ERR_OK) {
            error_log("Profile picture upload detected");
            // Validate file
            $validationResult = $this->validateProfilePicture($profilePic);
            if ($validationResult !== true) {
                error_log("Profile picture validation failed: " . $validationResult);
                return ['success' => false, 'message' => $validationResult];
            }

            $uploadDir = dirname(__DIR__) . '/../uploads/';
            error_log("Upload directory: $uploadDir");
            $fileName = $this->generateSecureFileName($profilePic['name']);
            $targetPath = $uploadDir . $fileName;
            error_log("Target path: $targetPath");

            if (move_uploaded_file($profilePic['tmp_name'], $targetPath)) {
                // Store relative path from public directory
                $profilePicPath = '../uploads/' . $fileName;
                error_log("File uploaded successfully, path: $profilePicPath");
            } else {
                error_log("Failed to move uploaded file to: " . $targetPath);
                return ['success' => false, 'message' => 'Failed to upload profile picture.'];
            }
        } elseif ($profilePic && $profilePic['error'] !== UPLOAD_ERR_NO_FILE) {
            // Handle upload errors
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            ];
            $errorMessage = $uploadErrors[$profilePic['error']] ?? 'Unknown upload error.';
            return ['success' => false, 'message' => 'Profile picture upload failed: ' . $errorMessage];
        }

        // Update user details
        error_log("Calling userModel->updateProfile with profilePicPath: " . ($profilePicPath ?? 'null'));
        $result = $this->userModel->updateProfile($userId, $username, $email, $profilePicPath, $password);
        if (is_array($result)) {
            return $result; // Error from userModel
        }
        error_log("User model updateProfile result: " . ($result ? 'success' : 'failure'));
        return $result ? ['success' => true] : ['success' => false, 'message' => 'Failed to update profile.'];
    }

    /**
     * Validates profile picture upload
     * @param array $file The uploaded file array
     * @return string|bool True if valid, error message if invalid
     */
    private function validateProfilePicture($file) {
        // Check file size (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            return "File size exceeds maximum limit of 5MB";
        }

        // Check if file is actually an image
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            return "Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed";
        }

        // Additional check: verify file extension matches MIME type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $allowedExtensions)) {
            return "Invalid file extension";
        }

        // Check for malicious content (basic check)
        if ($fileType === 'image/jpeg' || $fileType === 'image/png') {
            $imageInfo = getimagesize($file['tmp_name']);
            if (!$imageInfo) {
                return "File is not a valid image";
            }
        }

        return true;
    }

    /**
     * Generates a secure filename for uploaded files
     * @param string $originalName Original filename
     * @return string Secure filename
     */
    private function generateSecureFileName($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        return "profile_{$timestamp}_{$random}.{$extension}";
    }
}
?>