<?php
require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/models/Database.php';
require_once dirname(__DIR__) . '/app/models/User.php';

echo "<h2>Database Setup</h2>";

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

    // Check if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->rowCount() == 0) {
        // Read and execute schema
        $sql = file_get_contents(dirname(__DIR__) . '/database_schema.sql');
        $conn->exec($sql);
        echo "<p style='color: green;'>Database tables created successfully from database_schema.sql.</p>";
    } else {
        echo "<p style='color: green;'>Database tables already exist.</p>";
    }
} catch (\PDOException $e) {
    echo "<p style='color: red;'>Database setup failed. Please ensure your MySQL server is running and the credentials in <code>app/config.php</code> are correct.</p>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>Super Admin Creation</h2>";

try {
    $userModel = new User();
    $admin_email = 'superadmin@platform.com';
    $admin_password = 'password123';

    if ($userModel->findByEmail($admin_email)) {
        echo "<p style='color: orange;'>Super Admin user already exists.</p>";
    } else {
        $id = $userModel->create('SuperAdmin', $admin_email, $admin_password, ROLE_SUPER_ADMIN);
        if ($id) {
            echo "<p style='color: green;'>Super Admin created successfully!</p>";
            echo "<ul>";
            echo "<li>Username: <strong>SuperAdmin</strong></li>";
            echo "<li>Email: <strong>$admin_email</strong></li>";
            echo "<li>Password: <strong>$admin_password</strong></li>";
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>Failed to create Super Admin user.</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Make sure the database is set up first by running setup.php</p>";
}
?>
