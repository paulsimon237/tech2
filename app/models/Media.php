<?php
require_once dirname(__DIR__) . '/models/Database.php';

class Media {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Uploads a file and stores its metadata in the database.
     * @param int $user_id
     * @param array $file The $_FILES array entry for the file.
     * @return int|false The ID of the new media entry or false on failure.
     */
    public function uploadFile($user_id, $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log("File upload error: " . $file['error']);
            return false;
        }

        // 1. Validate file size
        if ($file['size'] > MAX_FILE_SIZE) {
            error_log("File size exceeds limit: " . $file['size']);
            return false;
        }

        // 2. Determine file type and extension
        $mime_type = mime_content_type($file['tmp_name']);
        $file_type = '';
        if (str_starts_with($mime_type, 'image/')) {
            $file_type = 'image';
        } elseif (str_starts_with($mime_type, 'video/')) {
            $file_type = 'video';
            // NOTE: Video duration check requires external tools (like ffmpeg) which are not available in the sandbox.
            // We will rely on file size limit for now, and note this limitation.
        } elseif (str_starts_with($mime_type, 'audio/')) {
            $file_type = 'audio';
        } else {
            error_log("Unsupported file type: " . $mime_type);
            return false;
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_file_name = uniqid('upload_', true) . '.' . $extension;
        $destination = UPLOAD_DIR . $new_file_name;
        $relative_path = 'uploads/' . $new_file_name;

        // 3. Move the uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            error_log("Failed to move uploaded file to: " . $destination);
            return false;
        }

        // 4. Store metadata in database
        $sql = "INSERT INTO media (user_id, file_path, file_type, file_size) VALUES (:user_id, :file_path, :file_type, :file_size)";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute([
                'user_id' => $user_id,
                'file_path' => $relative_path,
                'file_type' => $file_type,
                'file_size' => $file['size']
            ]);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            // If DB insert fails, attempt to delete the file
            unlink($destination);
            error_log("Media DB insert failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Links media to a content item (Problem, Product, or Tutorial).
     * @param int $media_id
     * @param int $content_id
     * @param string $content_type 'problem', 'product', or 'tutorial'
     * @return bool
     */
    public function linkMediaToContent($media_id, $content_id, $content_type) {
        $table = $content_type . '_media';
        $column = $content_type . '_id';

        $sql = "INSERT INTO $table ($column, media_id) VALUES (:content_id, :media_id)";
        $stmt = $this->db->prepare($sql);
        try {
            return $stmt->execute([
                'content_id' => $content_id,
                'media_id' => $media_id
            ]);
        } catch (\PDOException $e) {
            error_log("Failed to link media to content: " . $e->getMessage());
            return false;
        }
    }
}
?>
