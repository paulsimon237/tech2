<?php
require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/models/Problem.php';
require_once dirname(__DIR__) . '/app/models/Product.php';
require_once dirname(__DIR__) . '/app/models/Tutorial.php';

// Handle Firebase redirect result before auth check
if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in, checking for Firebase auth initiation');
    // Check if we need to initiate Google auth
    if (isset($_GET['init_google_auth'])) {
        error_log('Initiating Google auth from user_dashboard.php');
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initiating Google Authentication...</title>
    <!-- Firebase SDK -->
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/9.22.0/firebase-app.js";
        import { getAuth, GoogleAuthProvider, signInWithRedirect, signInWithPopup, getRedirectResult, signOut } from "https://www.gstatic.com/firebasejs/9.22.0/firebase-auth.js";

        // Firebase configuration
        const firebaseConfig = {
            apiKey: "AIzaSyD_8AtkQDfW_QaDNco2CJqMUTxbC5HbStU",
            authDomain: "studio-3044054056-b37d9.firebaseapp.com",
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

        // Listen for auth state changes
        window.firebaseAuth.onAuthStateChanged((user) => {
            console.log("Auth state changed:", user ? user.email : "No user");
        });

        // Initiate Google sign-in redirect (more reliable than popup)
        console.log("Initiating Google sign-in...");
        const roleType = "user";
        sessionStorage.setItem("firebase_login_role", roleType);

        console.log("Calling signInWithRedirect...");
        window.signInWithRedirect(window.firebaseAuth, window.firebaseProvider)
            .then(async (result) => {
                console.log("Popup auth successful:", result.user);
                if (result && result.user) {
                    console.log("User authenticated:", result.user.email);
                    const user = result.user;
                    const idToken = await user.getIdToken();
                    console.log("Sending token to server...");

                    // Get stored role type
                    const roleType = sessionStorage.getItem("firebase_login_role") || "user";

                    // Send token to server for verification and login
                    try {
                        const response = await fetch("firebase_auth.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            credentials: "include",
                            body: JSON.stringify({
                                idToken: idToken,
                                role_type: roleType
                            })
                        });

                        const data = await response.json();
                        console.log("Server response:", data);

                        if (data.success) {
                            sessionStorage.removeItem("firebase_login_role");
                            window.location.href = "user_dashboard.php";
                        } else {
                            alert("Firebase login failed: " + data.error);
                            sessionStorage.removeItem("firebase_login_role");
                            window.location.href = "login.php";
                        }
                    } catch (error) {
                        console.error("Server request failed:", error);
                        alert("Login failed: Unable to connect to server");
                        sessionStorage.removeItem("firebase_login_role");
                        window.location.href = "login.php";
                    }
                }
            })
            .catch((error) => {
                console.error("Popup auth error:", error);

                // Handle different types of authentication errors
                if (error.code === \'auth/popup-closed-by-user\') {
                    alert("Authentication cancelled. Please try again.");
                } else if (error.code === \'auth/popup-blocked\') {
                    alert("Popup was blocked by your browser. Please allow popups and try again.");
                } else if (error.code === \'auth/cancelled-popup-request\') {
                    alert("Authentication was cancelled. Please try again.");
                } else {
                    alert("Authentication failed: " + error.message);
                }

                sessionStorage.removeItem("firebase_login_role");
                window.location.href = "login.php";
            });
    </script>
</head>
<body>
    <p>Initiating Google authentication...</p>
</body>
</html>';
        exit;
    } else {
        // Check for redirect result
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing...</title>
    <link rel="preconnect" href="https://www.gstatic.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://www.google.com">
    <link rel="preconnect" href="https://accounts.google.com">
    <link rel="preconnect" href="https://oauth.googleusercontent.com">
    <!-- Firebase SDK -->
    <script type="module">
        // JavaScript code - PHP linter should ignore this section
        import { initializeApp } from "https://www.gstatic.com/firebasejs/9.22.0/firebase-app.js";
        import { getAuth, GoogleAuthProvider, signInWithRedirect, signInWithPopup, getRedirectResult, signOut } from "https://www.gstatic.com/firebasejs/9.22.0/firebase-auth.js";

        // Firebase configuration
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

        // Add additional scopes for Google sign-in
        provider.addScope(\'email\');
        provider.addScope(\'profile\');

        window.firebaseAuth = auth;
        window.firebaseProvider = provider;
        window.signInWithRedirect = signInWithRedirect;
        window.signInWithPopup = signInWithPopup;
        window.getRedirectResult = getRedirectResult;
        window.signOut = signOut;

        // Listen for auth state changes
        window.firebaseAuth.onAuthStateChanged((user) => {
            console.log("Auth state changed:", user ? user.email : "No user");
        });

        // Check if user is returning from Google redirect
        console.log("Checking redirect result...");
        window.getRedirectResult(window.firebaseAuth).then(async (result) => {
            console.log("Redirect result:", result);
            if (result && result.user) {
                console.log("User authenticated:", result.user.email);
                const user = result.user;
                const idToken = await user.getIdToken();
                console.log("Sending token to server...");

                // Get stored role type
                const roleType = sessionStorage.getItem("firebase_login_role") || "user";

                // Send token to server for verification and login
                try {
                    const response = await fetch("firebase_auth.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        credentials: "include",
                        body: JSON.stringify({
                            idToken: idToken,
                            role_type: roleType
                        })
                    });

                    const data = await response.json();

                    // Log the server response for debugging
                    console.log("Server response:", data);

                    if (data.success) {
                        // Clear stored role
                        sessionStorage.removeItem("firebase_login_role");
                        // Redirect to user dashboard (current page)
                        window.location.href = "user_dashboard.php";
                    } else {
                        alert("Firebase login failed: " + data.error);
                        // Clear stored role on error
                        sessionStorage.removeItem("firebase_login_role");
                        // Redirect to login on failure
                        window.location.href = "login.php";
                    }
                } catch (error) {
                    console.error("Server request failed:", error);
                    alert("Login failed: Unable to connect to server");
                    // Clear stored role on error
                    sessionStorage.removeItem("firebase_login_role");
                    // Redirect to login on failure
                    window.location.href = "login.php";
                }
            } else {
                console.log("No redirect result, redirecting to login");
                // No auth result, redirect to login
                window.location.href = "login.php";
            }
        }).catch((error) => {
            console.error("Redirect result error:", error);
            // Clear stored role on error
            sessionStorage.removeItem("firebase_login_role");
            // Redirect to login on error
            window.location.href = "login.php";
        });
    </script>
</head>
<body>
    <p>Processing Google authentication...</p>
</body>
</html>';
        exit;
    }
}

// Ensure only regular users are allowed
$authController = new AuthController();
$authController->requireAuth([ROLE_USER]);

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

$userModel = new User();

// Check if user role has changed and update session accordingly
$currentUser = $userModel->findById($user_id);
if ($currentUser && ($currentUser['role'] === ROLE_ADMIN || $currentUser['role'] === ROLE_SUPER_ADMIN)) {
    // User has been promoted to admin, update session and redirect to admin dashboard
    $_SESSION['role'] = $currentUser['role'];
    header("Location: admin_dashboard.php");
    exit;
}
$currentUser = $userModel->findById($user_id);

$problemModel = new Problem();
$productModel = new Product();
$tutorialModel = new Tutorial();

// Fetch user-specific data for the dashboard
$user_problems = $problemModel->getByUserId($user_id);
$user_products = $productModel->getByUserId($user_id);
$user_tutorials = $tutorialModel->getByUserId($user_id);

// Determine the current view
$view = isset($_GET['view']) ? $_GET['view'] : 'home';

// Handle form submissions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Include Media Model
    require_once dirname(__DIR__) . '/app/models/Media.php';
    $mediaModel = new Media();

    if ($view === 'share_problem') {
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);

        $problem_id = $problemModel->create($user_id, $title, $description, $category);

        if ($problem_id) {
            $media_uploaded = 0;
            if (isset($_FILES['media']) && is_array($_FILES['media']['name'])) {
                $file_count = count($_FILES['media']['name']);
                for ($i = 0; $i < $file_count; $i++) {
                    $file = [
                        'name' => $_FILES['media']['name'][$i],
                        'type' => $_FILES['media']['type'][$i],
                        'tmp_name' => $_FILES['media']['tmp_name'][$i],
                        'error' => $_FILES['media']['error'][$i],
                        'size' => $_FILES['media']['size'][$i]
                    ];
                    $media_id = $mediaModel->uploadFile($user_id, $file);
                    if ($media_id) {
                        $mediaModel->linkMediaToContent($media_id, $problem_id, 'problem');
                        $media_uploaded++;
                    }
                }
            }
            $message = "<div class='alert alert-success'>Problem shared successfully! $media_uploaded media file(s) uploaded.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Failed to share problem.</div>";
        }
    }
    // Add logic for product showcase and tutorial upload here
    elseif ($view === 'product_showcase') {
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $demo_video_path = filter_input(INPUT_POST, 'demo_video_path', FILTER_SANITIZE_URL); // Can be external link

        $product_id = $productModel->create($user_id, $name, $description, $demo_video_path);

        if ($product_id) {
            $media_uploaded = 0;
            if (isset($_FILES['media']) && is_array($_FILES['media']['name'])) {
                $file_count = count($_FILES['media']['name']);
                for ($i = 0; $i < $file_count; $i++) {
                    $file = [
                        'name' => $_FILES['media']['name'][$i],
                        'type' => $_FILES['media']['type'][$i],
                        'tmp_name' => $_FILES['media']['tmp_name'][$i],
                        'error' => $_FILES['media']['error'][$i],
                        'size' => $_FILES['media']['size'][$i]
                    ];
                    $media_id = $mediaModel->uploadFile($user_id, $file);
                    if ($media_id) {
                        $mediaModel->linkMediaToContent($media_id, $product_id, 'product');
                        $media_uploaded++;
                    }
                }
            }
            $message = "<div class='alert alert-success'>Product showcased successfully! $media_uploaded media file(s) uploaded.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Failed to showcase product.</div>";
        }
    } elseif ($view === 'tutorial_upload') {
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING);
        $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);

        $tutorial_id = $tutorialModel->create($user_id, $title, $content, $category);

        if ($tutorial_id) {
            $media_uploaded = 0;
            if (isset($_FILES['media']) && is_array($_FILES['media']['name'])) {
                $file_count = count($_FILES['media']['name']);
                for ($i = 0; $i < $file_count; $i++) {
                    $file = [
                        'name' => $_FILES['media']['name'][$i],
                        'type' => $_FILES['media']['type'][$i],
                        'tmp_name' => $_FILES['media']['tmp_name'][$i],
                        'error' => $_FILES['media']['error'][$i],
                        'size' => $_FILES['media']['size'][$i]
                    ];
                    $media_id = $mediaModel->uploadFile($user_id, $file);
                    if ($media_id) {
                        $mediaModel->linkMediaToContent($media_id, $tutorial_id, 'tutorial');
                        $media_uploaded++;
                    }
                }
            }
            $message = "<div class='alert alert-success'>Tutorial uploaded successfully! $media_uploaded media file(s) uploaded.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Failed to upload tutorial.</div>";
        }
    }
}

// Include the header and sidebar
include dirname(__DIR__) . '/app/views/includes/header.php';
include dirname(__DIR__) . '/app/views/includes/user_sidebar.php';
?>

<div class="main-content">
    <div class="dashboard-header">
        <h1>User Dashboard</h1>
        <div class="user-info">
            <?php if ($currentUser['profile_pic']): ?>
                <img src="<?php echo htmlspecialchars($currentUser['profile_pic']); ?>" alt="Profile Picture" class="profile-pic">
            <?php else: ?>
                <div class="profile-pic-placeholder"><?php echo htmlspecialchars(substr($username, 0, 1)); ?></div>
            <?php endif; ?>
            <span>Welcome, <?php echo htmlspecialchars($username); ?><?php if ($role !== ROLE_USER) echo " ($role)"; ?></span>
            <a href="user_dashboard_update_profile.php">Update Profile</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <?php echo $message; ?>

    <?php if ($view === 'home'): ?>
        <div class="card">
            <h3>Your Activity Summary</h3>
            <div class="metric-grid">
                <div class="metric-card problems">
                    <div class="metric-icon">❓</div>
                    <div class="metric-number"><?php echo count($user_problems); ?></div>
                    <div class="metric-label">Total Problems Shared</div>
                </div>
                <div class="metric-card products">
                    <div class="metric-icon">📦</div>
                    <div class="metric-number"><?php echo count($user_products); ?></div>
                    <div class="metric-label">Total Products Showcased</div>
                </div>
                <div class="metric-card tutorials">
                    <div class="metric-icon">📚</div>
                    <div class="metric-number"><?php echo count($user_tutorials); ?></div>
                    <div class="metric-label">Total Tutorials Uploaded</div>
                </div>
            </div>
        </div>
        <!-- Placeholder for a simple activity feed -->
        <div class="card">
            <h3>Latest Activity</h3>
            <p>This section will show your latest posts and chat messages.</p>
        </div>

    <?php elseif ($view === 'share_problem'): ?>
        <div class="card">
            <h3>Share a Tech Problem</h3>
            <form method="POST" action="user_dashboard.php?view=share_problem" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Problem Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="description">Detailed Description</label>
                    <textarea id="description" name="description" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="Software">Software</option>
                        <option value="Hardware">Hardware</option>
                        <option value="Networking">Networking</option>
                        <option value="Mobile systems">Mobile systems</option>
                        <option value="Laptop/Desktop systems">Laptop/Desktop systems</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="media">Upload Media (Images/Videos - Max 1 min)</label>
                    <input type="file" id="media" name="media[]" multiple>
                    <small class="form-text text-muted">Media upload functionality will be fully implemented in a later phase.</small>
                </div>
                <button type="submit" class="btn btn-primary">Share Problem</button>
            </form>
        </div>

    <?php elseif ($view === 'product_showcase'): ?>
        <div class="card">
            <h3>Product Showcase</h3>
            <form method="POST" action="user_dashboard.php?view=product_showcase" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="description">Product Description</label>
                    <textarea id="description" name="description" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <label for="demo_video_path">Demo Video Link (Optional)</label>
                    <input type="text" id="demo_video_path" name="demo_video_path" placeholder="e.g., YouTube link or file path">
                </div>
                <div class="form-group">
                    <label for="media">Upload Images/Videos</label>
                    <input type="file" id="media" name="media[]" multiple>
                </div>
                <button type="submit" class="btn btn-primary">Showcase Product</button>
            </form>
            <!-- Display user's products -->
        </div>

    <?php elseif ($view === 'tutorial_upload'): ?>
        <div class="card">
            <h3>Upload Tutorial</h3>
            <form method="POST" action="user_dashboard.php?view=tutorial_upload" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Tutorial Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="content">Tutorial Content (Markdown/HTML supported)</label>
                    <textarea id="content" name="content" rows="10" required></textarea>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="Software">Software</option>
                        <option value="Hardware">Hardware</option>
                        <option value="Networking">Networking</option>
                        <option value="Mobile systems">Mobile systems</option>
                        <option value="Laptop/Desktop systems">Laptop/Desktop systems</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="media">Upload Images/Videos</label>
                    <input type="file" id="media" name="media[]" multiple>
                </div>
                <button type="submit" class="btn btn-primary">Upload Tutorial</button>
            </form>
            <!-- Display user's tutorials -->
        </div>

    <?php elseif ($view === 'chat'): ?>
        <div class="card">
            <div class="chat-header">
                <h3>Central Chat Platform</h3>
                <div class="chat-buttons" id="chat-buttons-container">
                    <button id="voice-call-button"><span class="button-icon">📞</span>Voice Call</button>
                    <button id="video-call-button"><span class="button-icon">📹</span>Video Call</button>
                    <button id="voicemail-button"><span class="button-icon">🎤</span>Record Voicemail</button>
                </div>
            </div>
            <div class="chat-box" id="chat-box">
                <!-- Messages will be loaded here by chat.js -->
                <p class="text-center" style="color: var(--color-text-secondary);">Loading messages...</p>
            </div>
            <div id="call-notification" class="call-notification" style="display: none;"></div>
            <div id="voicemail-preview" style="display: none; margin-bottom: 10px; border: 1px solid #ccc; padding: 10px; background-color: #f9f9f9;">
                <p>Preview your voicemail:</p>
                <audio id="voicemail-audio" controls></audio>
                <br>
                <button id="send-voicemail" class="btn btn-success">Send</button>
                <button id="cancel-voicemail" class="btn btn-danger">Cancel</button>
            </div>
            <div class="chat-input-area">
                <textarea id="message-input" placeholder="Type your message..." rows="2"></textarea>
                <button id="send-button" class="btn btn-primary">Send</button>
            </div>
        </div>
        <script src="assets/js/webrtc.js"></script>
        <script src="assets/js/chat.js"></script>

    <?php endif; ?>

</div>

<?php include dirname(__DIR__) . '/app/views/includes/footer.php'; ?>
