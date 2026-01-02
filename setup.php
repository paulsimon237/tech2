<?php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/models/Database.php';
require_once __DIR__ . '/app/models/User.php';

// --- 1. Database Initialization ---
echo "<h2>1. Database Initialization</h2>";
echo "<p>Attempting to connect to the database defined in app/config.php...</p>";

try {
    // Connect without specifying DB_NAME to create it if it doesn't exist
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $conn = new PDO($dsn, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`");
    echo "<p style='color: green;'>Database '" . DB_NAME . "' created or already exists.</p>";

    // Reconnect with the database selected
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $conn = new PDO($dsn, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read and execute schema
    $sql = file_get_contents('database_schema.sql');
    $conn->exec($sql);
    echo "<p style='color: green;'>Database tables created successfully from database_schema.sql.</p>";

} catch (\PDOException $e) {
    echo "<p style='color: red;'>Database setup failed. Please ensure your MySQL server is running and the credentials in <code>app/config.php</code> are correct.</p>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    exit;
}

// --- 2. Super Admin Creation ---
echo "<h2>2. Super Admin Creation</h2>";
$userModel = new User();
$admin_email = 'superadmin@platform.com';
$admin_password = 'password123';

if ($userModel->findByEmail($admin_email)) {
    echo "<p style='color: orange;'>Super Admin user already exists. Skipping creation.</p>";
} else {
    $id = $userModel->create('SuperAdmin', $admin_email, $admin_password, ROLE_SUPER_ADMIN);
    if ($id) {
        echo "<p style='color: green;'>Super Admin created successfully!</p>";
        echo "<ul>";
        echo "<li>Username: <strong>SuperAdmin</strong></li>";
        echo "<li>Email: <strong>$admin_email</strong></li>";
        echo "<li>Password: <strong>$admin_password</strong></li>";
        echo "</ul>";
        echo "<p>Please change the password immediately after first login.</p>";
    } else {
        echo "<p style='color: red;'>Failed to create Super Admin user.</p>";
    }
}

echo "<h2>Setup Complete!</h2>";
echo "<p>You can now navigate to <a href='public/login.php'>public/login.php</a> to start using the application.</p>";
?>
