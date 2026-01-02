<?php
// Session Test Script for Debugging
require_once 'app/config.php';

echo "<h1>Session Test</h1>";
echo "<p>Session Status: " . (session_status() == PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Save Path: " . session_save_path() . "</p>";

if (isset($_SESSION['user_id'])) {
    echo "<h2>Current Session Data:</h2>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
} else {
    echo "<p>No user session found</p>";
}

// Test database connection
try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    echo "<p>Database connection: SUCCESS</p>";
} catch (Exception $e) {
    echo "<p>Database connection: FAILED - " . $e->getMessage() . "</p>";
}

// Check logs directory
$logsDir = __DIR__ . '/logs';
if (is_dir($logsDir)) {
    echo "<p>Logs directory exists: YES</p>";
    $logFiles = glob($logsDir . '/*.log');
    echo "<p>Log files found: " . count($logFiles) . "</p>";
    foreach ($logFiles as $logFile) {
        echo "<p>- " . basename($logFile) . " (" . filesize($logFile) . " bytes)</p>";
    }
} else {
    echo "<p>Logs directory exists: NO</p>";
}
?>
