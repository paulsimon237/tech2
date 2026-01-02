<?php
require_once dirname(__DIR__) . '/app/config.php';
// Check if user is already logged in, if so, redirect to dashboard immediately
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Loading</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Basic styling to match the dark theme of 7.png */
        body {
            background-color: #121212; /* Dark background */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #ffffff;
            font-family: sans-serif;
        }
        .splash-content {
            text-align: center;
        }
        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #007bff; /* Blue color for spinner */
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="splash-content">
        <img src="../tech.png" alt="Splash Screen" style="max-width: 100%; max-height: 100vh;">
    </div>

    <script>
        // Redirect to login page after 7 seconds
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 7000); // 7000 milliseconds = 7 seconds
    </script>
</body>
</html>
