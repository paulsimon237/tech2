<?php
require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/controllers/ProfileController.php';
require_once dirname(__DIR__) . '/app/models/User.php';

// Ensure only regular users are allowed
AuthController::requireAuth([ROLE_USER]);

$user_id = $_SESSION['user_id'];
$userModel = new User();
$profileController = new ProfileController();

// Fetch current user data
$currentUser = $userModel->findById($user_id);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $profilePic = $_FILES['profile_pic'] ?? null;

    if ($profileController->updateProfile($user_id, $username, $email, $profilePic)) {
        $message = "<div class='alert alert-success'>Profile updated successfully!</div>";
        // Update session username
        $_SESSION['username'] = $username;
        // Refresh current user data
        $currentUser = $userModel->findById($user_id);
    } else {
        $message = "<div class='alert alert-danger'>Failed to update profile.</div>";
    }
}

// Include the header and sidebar
include dirname(__DIR__) . '/app/views/includes/header.php';
include dirname(__DIR__) . '/app/views/includes/user_sidebar.php';
?>

<div class="main-content">
    <div class="dashboard-header">
        <h1>Update Profile</h1>
    </div>

    <?php echo $message; ?>

    <div class="card">
        <h3>Edit Your Profile Information</h3>
        <form action="user_dashboard_update_profile.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="profile_pic">Profile Picture:</label>
                <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
                <?php if ($currentUser['profile_pic']): ?>
                    <p>Current Profile Picture:</p>
                    <img src="<?php echo htmlspecialchars($currentUser['profile_pic']); ?>" alt="Current Profile Picture" style="max-width: 100px; max-height: 100px; border-radius: 50%;">
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>
</div>

<?php include dirname(__DIR__) . '/app/views/includes/footer.php'; ?>
