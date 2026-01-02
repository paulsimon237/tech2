
<?php
require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/models/SystemSettings.php';

$error = '';
$authController = new AuthController();
$settingsModel = new SystemSettings();

// Check if system is blocked
$system_blocked = $settingsModel->get(SETTING_SYSTEM_BLOCKED);
$block_reason = $settingsModel->get(SETTING_BLOCK_REASON);

if ($system_blocked === 'yes') {
    $error = "System is currently blocked. " . ($block_reason ? "Reason: " . $block_reason : "Please contact administrator.");
}

// Check if system is in maintenance mode
$maintenance_mode = $settingsModel->get(SETTING_MAINTENANCE_MODE);
$show_form = true;
if ($maintenance_mode == '1') {
    // Allow only super_admin to log in during maintenance
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== ROLE_SUPER_ADMIN) {
        $error = "The system is currently under maintenance. Only super administrators can access the system at this time.";
        $show_form = false;
    }
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === ROLE_USER) {
        header('Location: user_dashboard.php');
    } else {
        header('Location: admin_dashboard.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $role_type = filter_input(INPUT_POST, 'role_type', FILTER_SANITIZE_STRING);

    if ($email && $password && $role_type) {
        $loginResult = $authController->login($email, $password, $role_type);
        if ($loginResult['status'] === 'success') {
            // Redirect based on role - ensure super_admin goes to admin dashboard
            if ($_SESSION['role'] === ROLE_USER) {
                header('Location: user_dashboard.php');
            } elseif ($_SESSION['role'] === ROLE_ADMIN || $_SESSION['role'] === ROLE_SUPER_ADMIN) {
                header('Location: admin_dashboard.php');
            } else {
                // Fallback for any other role
                header('Location: user_dashboard.php');
            }
            exit;
        } else {
            $error = $loginResult['message'];
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Login</title>
    <link rel="preconnect" href="https://www.gstatic.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://www.google.com">
    <link rel="preconnect" href="https://accounts.google.com">
    <link rel="preconnect" href="https://oauth.googleusercontent.com">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Firebase SDK -->
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/9.22.0/firebase-app.js';
        import { getAuth, GoogleAuthProvider, signInWithRedirect, signInWithPopup, getRedirectResult, signOut } from 'https://www.gstatic.com/firebasejs/9.22.0/firebase-auth.js';

        // Firebase configuration - Replace with your actual Firebase config from Firebase Console
        // IMPORTANT: In Firebase Console > Authentication > Sign-in method > Google > Authorized redirect URIs,
        // add the URL of this user_dashboard.php page (e.g., http://localhost/tech_support_platform/public/user_dashboard.php)
        const firebaseConfig = {
            apiKey: "AIzaSyD_8AtkQDfW_QaDNco2CJqMUTxbC5HbStU",
            authDomain: null,
            projectId: "studio-3044054056-b37d9",
            storageBucket: "studio-3044054056-b37d9.firebasestorage.app",
            messagingSenderId: "215339299532",
            appId: "1:215339299532:web:aab8bcc7ce99cb8a580869"
        };

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const provider = new GoogleAuthProvider();

        window.firebaseAuth = auth;
        window.firebaseProvider = provider;
        window.signInWithRedirect = signInWithRedirect;
        window.signInWithPopup = signInWithPopup;
        window.getRedirectResult = getRedirectResult;
        window.signOut = signOut;

        // Listen for auth state changes to ensure state is updated
        window.firebaseAuth.onAuthStateChanged((user) => {
            console.log('Auth state changed:', user ? user.email : 'No user');
        });
    </script>
    <style>
        /* Your existing dark theme styling */
        body { background-color: #121212; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; color: #ffffff; font-family: sans-serif; }
        .login-container { background-color: #1e1e1e; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); width: 100%; max-width: 400px; }
        .login-container h2 { text-align: center; margin-bottom: 30px; color: #007bff; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        .form-group input[type="email"], .form-group input[type="password"] { width: 100%; padding: 10px; border: 1px solid #333; border-radius: 4px; background-color: #2a2a2a; color: #ffffff; box-sizing: border-box; }
        .role-selection { display: flex; justify-content: space-around; margin-bottom: 20px; }
        .role-selection label { cursor: pointer; padding: 10px 20px; border: 2px solid #333; border-radius: 4px; transition: all 0.3s ease; }
        .role-selection input[type="radio"] { display: none; }
        .role-selection input[type="radio"]:checked + label { background-color: #007bff; border-color: #007bff; color: #ffffff; }
        .btn-login, .btn-google { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: background-color 0.3s ease; margin-top: 10px; }
        .btn-login:hover, .btn-google:hover { background-color: #0056b3; }
        .btn-google { background-color: #4285f4; margin-top: 10px; }
        .btn-google:hover { background-color: #3367d6; }
        .error-message { color: #ff4d4d; text-align: center; margin-bottom: 15px; }
        .divider { text-align: center; margin: 20px 0; position: relative; }
        .divider::before { content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 1px; background: #333; }
        .divider span { background: #1e1e1e; padding: 0 10px; color: #666; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login to <?php echo APP_NAME; ?></h2>

        <?php if ($error): ?>
            <p class="error-message">
                <?php echo is_array($error) ? json_encode($error) : $error; ?>
            </p>
        <?php endif; ?>

        <?php if ($show_form): ?>
        <form method="POST" action="">
            <!-- Traditional Email/Password Form -->
            <div class="form-group">
                <label for="traditional_email">Email</label>
                <input type="email" id="traditional_email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <label for="traditional_password">Password</label>
                <input type="password" id="traditional_password" name="password" placeholder="Password" required>
            </div>

            <div class="role-selection">
                <input type="radio" id="login-user" name="role_type" value="user" checked>
                <label for="login-user">User Login</label>

                <input type="radio" id="login-admin" name="role_type" value="admin">
                <label for="login-admin">Admin Login</label>
            </div>

            <button type="submit" class="btn-login">Login with Email & Password</button>
        </form>

        <div class="divider">
            <span>OR</span>
        </div>

        <!-- Firebase Authentication Section -->
        <button type="button" id="firebase-login-btn" class="btn-google">
            <svg width="18" height="18" viewBox="0 0 24 24" style="margin-right: 10px; vertical-align: middle;">
                <path fill="#ffffff" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#ffffff" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#ffffff" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#ffffff" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Continue with Google
        </button>

        <p style="text-align: center; margin-top: 20px;">
            Don't have an account? <a href="register.php" style="color: #007bff; text-decoration: none;">Register here</a>
        </p>
        <?php endif; ?>
    </div>

    <!-- Login functionality note -->
    <script>
        console.log('Login system initialized - Traditional and Firebase authentication available');

        // Firebase login functionality
        document.addEventListener('DOMContentLoaded', () => {
            console.log('DOM loaded, initializing Firebase login...');
            const googleLoginBtn = document.getElementById('firebase-login-btn');
            if (googleLoginBtn) {
                googleLoginBtn.disabled = false; // Ensure the button is enabled
                googleLoginBtn.style.cursor = 'pointer'; // Add visual feedback
                console.log('Google login button found and enabled');

                googleLoginBtn.addEventListener('click', async function() {
                    console.log('Google login button clicked');
                    // Google auth is only for users
                    const roleType = 'user';

                    try {
                        // Store role type for redirect handling
                        sessionStorage.setItem('firebase_login_role', roleType);
                        console.log('Stored role type, redirecting to user_dashboard.php');

                        // Redirect to user_dashboard.php to initiate Google sign-in from there
                        // This ensures Firebase redirects back to user_dashboard.php after auth
                        window.location.href = 'user_dashboard.php?init_google_auth=1';
                    } catch (error) {
                        console.error('Redirect error:', error);
                        alert('Failed to initiate Google authentication. Please try again.');
                        // Clear stored role on error
                        sessionStorage.removeItem('firebase_login_role');
                    }
                });
            } else {
                console.error('Google login button not found.');
            }
        });
    </script>
</body>
</html>
