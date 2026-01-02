<?php
require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/models/User.php';
require_once dirname(__DIR__) . '/app/models/SystemSettings.php';

$message = '';
$userModel = new User();
$settingsModel = new SystemSettings();

// Check if system is in maintenance mode
$maintenance_mode = $settingsModel->get(SETTING_MAINTENANCE_MODE);
if ($maintenance_mode == '1') {
    $message = "The system is currently under maintenance. Registration is not available at this time.";
}

// Prevent registration during maintenance mode
if ($maintenance_mode == '1') {
    $show_form = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_STRING);

    if ($password !== $confirm_password) {
        $message = "Error: Passwords do not match.";
    } elseif ($userModel->findByEmail($email)) {
        $message = "Error: An account with this email already exists.";
    } else {
        // Default role is 'user'
        $new_user_id = $userModel->create($username, $email, $password, ROLE_USER);
        if ($new_user_id) {
            $message = "Success: Registration complete. You can now <a href='login.php'>login</a>.";
        } else {
            $message = "Error: Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Register</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Reusing login styles for consistency */
        body {
            background-color: #121212;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #ffffff;
            font-family: sans-serif;
        }
        .register-container {
            background-color: #1e1e1e;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
        }
        .register-container h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #007bff;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #333;
            border-radius: 4px;
            background-color: #2a2a2a;
            color: #ffffff;
            box-sizing: border-box;
        }
        .btn-register {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .btn-register:hover {
            background-color: #0056b3;
        }
        .message {
            text-align: center;
            margin-bottom: 15px;
            color: #4CAF50; /* Green for success */
        }
        .message.error {
            color: #ff4d4d; /* Red for error */
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Register for <?php echo APP_NAME; ?></h2>

        <?php if ($message): ?>
            <p class="message <?php echo strpos($message, 'Error') === 0 ? 'error' : ''; ?>"><?php echo $message; ?></p>
        <?php endif; ?>

        <?php if ($maintenance_mode != '1'): ?>
        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn-register">Register</button>
        </form>
        <p style="text-align: center; margin-top: 20px;">
            Already have an account? <a href="login.php" style="color: #007bff; text-decoration: none;">Login here</a>
        </p>
        <?php endif; ?>
    </div>
</body>
</html>
