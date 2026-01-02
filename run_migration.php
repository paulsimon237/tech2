<?php
require_once 'app/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if firebase_uid column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'firebase_uid'");
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        // Add the firebase_uid column
        $pdo->exec("ALTER TABLE users ADD COLUMN firebase_uid VARCHAR(255) UNIQUE DEFAULT NULL AFTER password");
        echo "Migration completed: Added firebase_uid column to users table.\n";
    } else {
        echo "Migration skipped: firebase_uid column already exists.\n";
    }

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
