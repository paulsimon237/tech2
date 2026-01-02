<?php
require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/models/User.php';
require_once dirname(__DIR__) . '/app/models/Problem.php';
require_once dirname(__DIR__) . '/app/models/Product.php';
require_once dirname(__DIR__) . '/app/models/Tutorial.php';
require_once dirname(__DIR__) . '/app/models/AdminActivityLog.php';
require_once dirname(__DIR__) . '/app/models/SystemSettings.php';
require_once dirname(__DIR__) . '/app/models/Chat.php';

// Ensure only admins and superadmins are allowed
$authController = new AuthController();
$authController->requireAuth([ROLE_ADMIN, ROLE_SUPER_ADMIN]);

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Unknown';
$role = $_SESSION['role'];

$userModel = new User();

// Check if user role has changed and update session accordingly
$currentUser = $userModel->findById($user_id);
if ($currentUser && $currentUser['role'] === ROLE_USER) {
    // User has been demoted to regular user, update session and redirect to user dashboard
    $_SESSION['role'] = $currentUser['role'];
    header("Location: user_dashboard.php");
    exit;
}
$currentUser = $userModel->findById($user_id);

$problemModel = new Problem();
$productModel = new Product();
$tutorialModel = new Tutorial();
$adminActivityLog = new AdminActivityLog();
$systemSettings = new SystemSettings();

// Determine the current view
$view = isset($_GET['view']) ? $_GET['view'] : 'home';

// Handle form submissions and AJAX requests
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip_address = $_SERVER['REMOTE_ADDR'];



    if ($view === 'manage_users') {
        if (isset($_POST['action']) && isset($_POST['user_id'])) {
            $action = $_POST['action'];
            $target_user_id = (int)$_POST['user_id'];

            if ($action === 'suspend') {
                $targetUser = $userModel->findById($target_user_id);
                if (!$targetUser) {
                    $message = "<div class='alert alert-danger'>User not found.</div>";
                } elseif (!($role === ROLE_SUPER_ADMIN || ($role === ROLE_ADMIN && $targetUser['role'] === ROLE_USER))) {
                    $message = "<div class='alert alert-danger'>You do not have permission to suspend this user.</div>";
                } elseif ($userModel->suspend($target_user_id)) {
                    $message = "<div class='alert alert-success'>User suspended successfully.</div>";
                    $adminActivityLog->logActivity($user_id, 'suspend_user', "Suspended user ID: $target_user_id", $ip_address);
                } else {
                    $message = "<div class='alert alert-danger'>Failed to suspend user.</div>";
                }
            } elseif ($action === 'activate') {
                $targetUser = $userModel->findById($target_user_id);
                if (!$targetUser) {
                    $message = "<div class='alert alert-danger'>User not found.</div>";
                } elseif (!($role === ROLE_SUPER_ADMIN || ($role === ROLE_ADMIN && $targetUser['role'] === ROLE_USER))) {
                    $message = "<div class='alert alert-danger'>You do not have permission to activate this user.</div>";
                } elseif ($userModel->activate($target_user_id)) {
                    $message = "<div class='alert alert-success'>User activated successfully.</div>";
                    $adminActivityLog->logActivity($user_id, 'activate_user', "Activated user ID: $target_user_id", $ip_address);
                } else {
                    $message = "<div class='alert alert-danger'>Failed to activate user.</div>";
                }
            } elseif ($action === 'delete' && $role === ROLE_SUPER_ADMIN) {
                if ($userModel->delete($target_user_id)) {
                    $message = "<div class='alert alert-success'>User deleted successfully.</div>";
                    $adminActivityLog->logActivity($user_id, 'delete_user', "Deleted user ID: $target_user_id", $ip_address);
                } else {
                    $message = "<div class='alert alert-danger'>Failed to delete user.</div>";
                }
            }
        }
    } elseif ($view === 'share_problem') {
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);

        $problem_id = $problemModel->create($user_id, $title, $description, $category);

        if ($problem_id) {
            $media_uploaded = 0;
            if (isset($_FILES['media']) && is_array($_FILES['media']['name'])) {
                require_once dirname(__DIR__) . '/app/models/Media.php';
                $mediaModel = new Media();
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
            $adminActivityLog->logActivity($user_id, 'share_problem', "Shared problem: $title", $ip_address);
        } else {
            $message = "<div class='alert alert-danger'>Failed to share problem.</div>";
        }
    } elseif ($view === 'product_showcase') {
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $demo_video_path = filter_input(INPUT_POST, 'demo_video_path', FILTER_SANITIZE_URL);

        $product_id = $productModel->create($user_id, $name, $description, $demo_video_path);

        if ($product_id) {
            $media_uploaded = 0;
            if (isset($_FILES['media']) && is_array($_FILES['media']['name'])) {
                require_once dirname(__DIR__) . '/app/models/Media.php';
                $mediaModel = new Media();
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
            $adminActivityLog->logActivity($user_id, 'product_showcase', "Showcased product: $name", $ip_address);
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
                require_once dirname(__DIR__) . '/app/models/Media.php';
                $mediaModel = new Media();
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
            $adminActivityLog->logActivity($user_id, 'tutorial_upload', "Uploaded tutorial: $title", $ip_address);
        } else {
            $message = "<div class='alert alert-danger'>Failed to upload tutorial.</div>";
        }
    } elseif ($view === 'manage_content') {
        if (isset($_POST['action']) && isset($_POST['content_type']) && isset($_POST['content_id'])) {
            $action = $_POST['action'];
            $content_type = $_POST['content_type'];
            $content_id = (int)$_POST['content_id'];

            if ($action === 'delete') {
                $deleted = false;
                if ($content_type === 'problem') {
                    $deleted = $problemModel->delete($content_id);
                } elseif ($content_type === 'product') {
                    $deleted = $productModel->delete($content_id);
                } elseif ($content_type === 'tutorial') {
                    $deleted = $tutorialModel->delete($content_id);
                }

                if ($deleted) {
                    $message = "<div class='alert alert-success'>Content deleted successfully.</div>";
                    $adminActivityLog->logActivity($user_id, 'delete_content', "Deleted $content_type ID: $content_id", $ip_address);
                    // Refresh data
                    $all_problems = $problemModel->getAll();
                    $all_products = $productModel->getAll();
                    $all_tutorials = $tutorialModel->getAll();
                } else {
                    $message = "<div class='alert alert-danger'>Failed to delete content.</div>";
                }
            } elseif ($action === 'approve_tutorial') {
                if ($tutorialModel->approve($content_id, $user_id)) {
                    $message = "<div class='alert alert-success'>Tutorial approved successfully.</div>";
                    $adminActivityLog->logActivity($user_id, 'approve_tutorial', "Approved tutorial ID: $content_id", $ip_address);
                    // Refresh data
                    $all_tutorials = $tutorialModel->getAll();
                } else {
                    $message = "<div class='alert alert-danger'>Failed to approve tutorial.</div>";
                }
            } elseif ($action === 'reject_tutorial') {
                if ($tutorialModel->reject($content_id, $user_id)) {
                    $message = "<div class='alert alert-success'>Tutorial rejected successfully.</div>";
                    $adminActivityLog->logActivity($user_id, 'reject_tutorial', "Rejected tutorial ID: $content_id", $ip_address);
                    // Refresh data
                    $all_tutorials = $tutorialModel->getAll();
                } else {
                    $message = "<div class='alert alert-danger'>Failed to reject tutorial.</div>";
                }
            }
        }
    } elseif ($view === 'manage_admins' && $role === ROLE_SUPER_ADMIN) {
        if (isset($_POST['create_admin'])) {
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            if ($userModel->create($username, $email, $password, ROLE_ADMIN)) {
                $message = "<div class='alert alert-success'>Admin created successfully.</div>";
                $adminActivityLog->logActivity($user_id, 'create_admin', "Created admin: $username", $ip_address);
            } else {
                $message = "<div class='alert alert-danger'>Failed to create admin.</div>";
            }
        } elseif (isset($_POST['demote_from_admin'])) {
            $target_user_id = (int)$_POST['user_id'];
            if ($userModel->updateRole($target_user_id, ROLE_USER)) {
                $message = "<div class='alert alert-success'>Admin demoted to user successfully.</div>";
                $adminActivityLog->logActivity($user_id, 'demote_admin', "Demoted admin ID: $target_user_id", $ip_address);
            } else {
                $message = "<div class='alert alert-danger'>Failed to demote admin.</div>";
            }
        } elseif (isset($_POST['promote_to_admin'])) {
            $target_user_id = (int)$_POST['user_id'];
            if ($userModel->updateRole($target_user_id, ROLE_ADMIN)) {
                $message = "<div class='alert alert-success'>User promoted to admin successfully.</div>";
                $adminActivityLog->logActivity($user_id, 'promote_to_admin', "Promoted user ID: $target_user_id", $ip_address);
            } else {
                $message = "<div class='alert alert-danger'>Failed to promote user.</div>";
            }
        }
    } elseif ($view === 'edit_user' && $role === ROLE_SUPER_ADMIN) {
        if (isset($_POST['update_user'])) {
            $target_user_id = (int)$_POST['user_id'];
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            $profile_pic = $_FILES['profile_pic'];
            $update_data = ['username' => $username, 'email' => $email];
            if (!empty($password)) {
                $update_data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            if ($profile_pic['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/profile_pics/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $filename = uniqid() . '_' . basename($profile_pic['name']);
                $target_file = $upload_dir . $filename;
                if (move_uploaded_file($profile_pic['tmp_name'], $target_file)) {
                    $update_data['profile_pic'] = $target_file;
                }
            }
            if ($userModel->update($target_user_id, $update_data)) {
                $message = "<div class='alert alert-success'>User updated successfully.</div>";
                $adminActivityLog->logActivity($user_id, 'update_user', "Updated user ID: $target_user_id", $ip_address);
            } else {
                $message = "<div class='alert alert-danger'>Failed to update user.</div>";
            }
        }
    } elseif ($view === 'settings' && $role === ROLE_SUPER_ADMIN) {
        if (isset($_POST['update_profile'])) {
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            $profile_pic = $_FILES['profile_pic'];
            $update_data = ['username' => $username, 'email' => $email];
            if (!empty($password)) {
                $update_data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            if ($profile_pic['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/profile_pics/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $filename = uniqid() . '_' . basename($profile_pic['name']);
                $target_file = $upload_dir . $filename;
                if (move_uploaded_file($profile_pic['tmp_name'], $target_file)) {
                    $update_data['profile_pic'] = $target_file;
                }
            }
            if ($userModel->update($user_id, $update_data)) {
                $message = "<div class='alert alert-success'>Profile updated successfully.</div>";
                $adminActivityLog->logActivity($user_id, 'update_profile', "Updated own profile", $ip_address);
                $_SESSION['username'] = $username;
            } else {
                $message = "<div class='alert alert-danger'>Failed to update profile.</div>";
            }
        } elseif (isset($_POST['update_settings'])) {
            $site_name = filter_input(INPUT_POST, 'site_name', FILTER_SANITIZE_STRING);
            $site_description = filter_input(INPUT_POST, 'site_description', FILTER_SANITIZE_STRING);
            $admin_email = filter_input(INPUT_POST, 'admin_email', FILTER_SANITIZE_EMAIL);
            $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
            $settings_data = [
                'site_name' => $site_name,
                'site_description' => $site_description,
                'admin_email' => $admin_email,
                'maintenance_mode' => $maintenance_mode
            ];
            if ($maintenance_mode == 1) {
                $current_version = $systemSettings->get(SETTING_MAINTENANCE_VERSION) ?: 0;
                $new_version = $current_version + 1;
                $settings_data['maintenance_version'] = $new_version;
            }
            if ($systemSettings->updateSettings($settings_data)) {
                // If maintenance mode was intended to be enabled, verify it was set correctly
                if ($maintenance_mode == 1) {
                    $current_maintenance = $systemSettings->get(SETTING_MAINTENANCE_MODE);
                    if ($current_maintenance != '1') {
                        $message = "<div class='alert alert-danger'>System maintenance failed. Maintenance mode could not be enabled. Debug: expected '1', got '" . htmlspecialchars($current_maintenance) . "'</div>";
                    } else {
                        $message = "<div class='alert alert-success'>System settings updated successfully. Maintenance mode is now enabled.</div>";
                        $adminActivityLog->logActivity($user_id, 'update_settings', "Updated system settings - maintenance mode enabled", $ip_address);
                    }
                } else {
                    $message = "<div class='alert alert-success'>System settings updated successfully.</div>";
                    $adminActivityLog->logActivity($user_id, 'update_settings', "Updated system settings", $ip_address);
                }
            } else {
                $error_details = $systemSettings->getLastError();
                $message = "<div class='alert alert-danger'>Failed to update system settings. Please check database connection and table structure. Error: " . htmlspecialchars($error_details) . "</div>";
            }
        }
    }
}

// Fetch data for dashboard
$all_users = $userModel->getAll();
$total_users = count($all_users);
$active_users = count(array_filter($all_users, function($user) { return $user['is_active']; }));
$admin_count = $userModel->getAdminCount();

$all_problems = $problemModel->getAll();
$total_problems = count($all_problems);

$all_products = $productModel->getAll();
$total_products = count($all_products);

$all_tutorials = $tutorialModel->getAll();
$total_tutorials = count($all_tutorials);

$recent_activity = $adminActivityLog->getLogs(null, 10);

// Include the header and sidebar
include dirname(__DIR__) . '/app/views/includes/header.php';
include dirname(__DIR__) . '/app/views/includes/admin_sidebar.php';
?>

<div class="main-content">
    <div class="dashboard-header">
        <h1>Admin Dashboard</h1>
        <div class="user-info">
            <?php if ($currentUser['profile_pic']): ?>
                <img src="<?php echo htmlspecialchars($currentUser['profile_pic']); ?>" alt="Profile Picture" class="profile-pic">
            <?php else: ?>
                <div class="profile-pic-placeholder"><?php echo htmlspecialchars(substr($username, 0, 1)); ?></div>
            <?php endif; ?>
            <span>Welcome, <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($role); ?>)</span>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div id="popup-message" class="popup-message" style="display: none;"></div>
    <?php if ($message && strpos($message, 'Error') !== 0): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const popup = document.getElementById('popup-message');
                popup.textContent = <?php echo json_encode(strip_tags($message)); ?>;
                popup.style.display = 'block';

                // Hide after 3 seconds
                setTimeout(function() {
                    popup.classList.add('fade-out');
                    setTimeout(function() {
                        popup.style.display = 'none';
                        popup.classList.remove('fade-out');
                    }, 300);
                }, 3000);
            });
        </script>
    <?php elseif ($message && strpos($message, 'Error') === 0): ?>
        <div class="alert alert-danger"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($view === 'home'): ?>
        <div class="card">
            <h3>System Overview</h3>
            <div class="metric-grid">
                <div class="metric-card users">
                    <div class="metric-icon">👥</div>
                    <div class="metric-number"><?php echo $total_users; ?></div>
                    <div class="metric-label">Total Users</div>
                </div>
                <div class="metric-card active-users">
                    <div class="metric-icon">✅</div>
                    <div class="metric-number"><?php echo $active_users; ?></div>
                    <div class="metric-label">Active Users</div>
                </div>
                <div class="metric-card problems">
                    <div class="metric-icon">❓</div>
                    <div class="metric-number"><?php echo $total_problems; ?></div>
                    <div class="metric-label">Total Problems</div>
                </div>
                <div class="metric-card products">
                    <div class="metric-icon">📦</div>
                    <div class="metric-number"><?php echo $total_products; ?></div>
                    <div class="metric-label">Total Products</div>
                </div>
                <div class="metric-card tutorials">
                    <div class="metric-icon">📚</div>
                    <div class="metric-number"><?php echo $total_tutorials; ?></div>
                    <div class="metric-label">Total Tutorials</div>
                </div>
                <div class="metric-card admins">
                    <div class="metric-icon">🛡️</div>
                    <div class="metric-number"><?php echo $admin_count; ?></div>
                    <div class="metric-label">Admin Users</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Recent Admin Activity</h3>
            <div class="activity-list">
                <?php if (empty($recent_activity)): ?>
                    <p>No recent activity.</p>
                <?php else: ?>
                    <?php
                    function getActivityIconClass($action) {
                        if (strpos($action, 'user') !== false || strpos($action, 'admin') !== false) {
                            return 'user-action';
                        } elseif (strpos($action, 'delete') !== false) {
                            return 'delete-action';
                        } elseif (strpos($action, 'content') !== false || strpos($action, 'problem') !== false || strpos($action, 'product') !== false || strpos($action, 'tutorial') !== false) {
                            return 'content-action';
                        } else {
                            return 'settings-action';
                        }
                    }

                    function getActivityIcon($action) {
                        if (strpos($action, 'user') !== false || strpos($action, 'admin') !== false) {
                            return '👤';
                        } elseif (strpos($action, 'delete') !== false) {
                            return '🗑️';
                        } elseif (strpos($action, 'content') !== false || strpos($action, 'problem') !== false || strpos($action, 'product') !== false || strpos($action, 'tutorial') !== false) {
                            return '📄';
                        } else {
                            return '⚙️';
                        }
                    }
                    ?>
                    <?php foreach ($recent_activity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?php echo getActivityIconClass($activity['action']); ?>">
                                <?php echo getActivityIcon($activity['action']); ?>
                            </div>
                            <div class="activity-content">
                                <strong><?php echo htmlspecialchars($activity['username']); ?></strong>
                                <div class="activity-action"><?php echo htmlspecialchars(str_replace('_', ' ', $activity['action'])); ?></div>
                                <small><?php echo htmlspecialchars($activity['details']); ?></small>
                                <span class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($view === 'manage_users'): ?>
        <div class="card">
            <h3>Manage Users</h3>
            <input type="text" id="user-search" placeholder="Search users by username, email, or role..." style="margin-bottom: 10px; padding: 8px; width: 300px;">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="role-<?php echo htmlspecialchars($user['role']); ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                            <td><span class="status-<?php echo $user['is_active'] ? 'active' : 'suspended'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Suspended'; ?></span></td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <?php if ($user['is_active'] && ($role === ROLE_SUPER_ADMIN || ($role === ROLE_ADMIN && $user['role'] === ROLE_USER))): ?>
                                        <button type="submit" name="action" value="suspend" class="btn btn-warning btn-sm">Suspend</button>
                                    <?php elseif (!$user['is_active'] && ($role === ROLE_SUPER_ADMIN || ($role === ROLE_ADMIN && $user['role'] === ROLE_USER))): ?>
                                        <button type="submit" name="action" value="activate" class="btn btn-success btn-sm">Activate</button>
                                    <?php endif; ?>
                                    <?php if ($role === ROLE_SUPER_ADMIN && $user['id'] != $user_id): ?>
                                        <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                                    <?php endif; ?>
                                </form>
                                <?php if ($role === ROLE_SUPER_ADMIN): ?>
                                    <a href="admin_dashboard.php?view=edit_user&user_id=<?php echo $user['id']; ?>" class="btn btn-info btn-sm" style="margin-left: 5px;">Edit</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($view === 'manage_content'): ?>
        <div class="card">
            <h3>Manage Content</h3>
            <input type="text" id="content-search" placeholder="Search content by title, category, or user..." style="margin-bottom: 10px; padding: 8px; width: 300px;">
            <div class="content-tabs">
                <button class="tab-button active" onclick="showContentTab('problems')">Problems</button>
                <button class="tab-button" onclick="showContentTab('products')">Products</button>
                <button class="tab-button" onclick="showContentTab('tutorials')">Tutorials</button>
            </div>

            <div id="problems-tab" class="content-tab active">
                <h4>Problems</h4>
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_problems as $problem): ?>
                            <tr>
                                <td><?php echo $problem['id']; ?></td>
                                <td><?php echo htmlspecialchars($problem['username']); ?></td>
                                <td><?php echo htmlspecialchars($problem['title']); ?></td>
                                <td><?php echo htmlspecialchars($problem['category']); ?></td>
                                <td><?php echo $problem['status'] ?? 'Active'; ?></td>
                                <td><?php echo date('M j, Y', strtotime($problem['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="content_type" value="problem">
                                        <input type="hidden" name="content_id" value="<?php echo $problem['id']; ?>">
                                        <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this problem?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="products-tab" class="content-tab">
                <h4>Products</h4>
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td><?php echo htmlspecialchars($product['username']); ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . (strlen($product['description']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="content_type" value="product">
                                        <input type="hidden" name="content_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="tutorials-tab" class="content-tab">
                <h4>Tutorials</h4>
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_tutorials as $tutorial): ?>
                            <tr>
                                <td><?php echo $tutorial['id']; ?></td>
                                <td><?php echo htmlspecialchars($tutorial['username']); ?></td>
                                <td><?php echo htmlspecialchars($tutorial['title']); ?></td>
                                <td><?php echo htmlspecialchars($tutorial['category']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($tutorial['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="content_type" value="tutorial">
                                        <input type="hidden" name="content_id" value="<?php echo $tutorial['id']; ?>">
                                        <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this tutorial?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        function showContentTab(tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.content-tab');
            tabs.forEach(tab => tab.classList.remove('active'));

            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(button => button.classList.remove('active'));

            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');

            // Add active class to clicked button
            event.target.classList.add('active');
        }
        </script>

    <?php elseif ($view === 'chat_monitor'): ?>
        <div class="card">
            <h3>Chat Monitor</h3>
            <div class="chat-box" id="admin-chat-box">
                <!-- Messages will be loaded here -->
                <p class="text-center" style="color: var(--color-text-secondary);">Loading messages...</p>
            </div>
            <div class="chat-input-area">
                <textarea id="admin-message-input" placeholder="Send announcement to all users..." rows="2"></textarea>
                <button id="admin-send-button" class="btn btn-primary">Send Announcement</button>
                <button id="delete-all-messages" class="btn btn-danger" style="margin-left: 10px;">Delete All Messages</button>
            </div>
        </div>
        <script>
        // Admin chat monitoring functionality
        let lastMessageId = 0;
        const adminChatBox = document.getElementById('admin-chat-box');
        const adminMessageInput = document.getElementById('admin-message-input');
        const adminSendButton = document.getElementById('admin-send-button');

        function loadMessages() {
            fetch('chat_api.php?last_id=' + lastMessageId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            const messageDiv = document.createElement('div');
                            messageDiv.className = 'chat-message ' + (msg.is_admin ? 'admin-message' : 'user-message');
                            messageDiv.innerHTML = `
                                <strong>${msg.username}${msg.is_admin ? ' (Admin)' : ''}:</strong> ${msg.message}
                                <small>${new Date(msg.sent_at).toLocaleString()}</small>
                            `;
                            adminChatBox.appendChild(messageDiv);
                            lastMessageId = Math.max(lastMessageId, msg.id);
                        });
                        adminChatBox.scrollTop = adminChatBox.scrollHeight;
                    }
                })
                .catch(error => console.error('Error loading messages:', error));
        }

        function sendAnnouncement() {
            const message = adminMessageInput.value.trim();
            if (!message) return;

            fetch('chat_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: message })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    adminMessageInput.value = '';
                    loadMessages(); // Reload to show the new message
                } else {
                    alert('Failed to send announcement: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Error sending announcement');
            });
        }

        adminSendButton.addEventListener('click', sendAnnouncement);
        adminMessageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendAnnouncement();
            }
        });

        // Delete all messages functionality
        const deleteAllButton = document.getElementById('delete-all-messages');
        deleteAllButton.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete ALL messages? This action cannot be undone.')) {
                fetch('chat_api.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('All messages have been deleted.');
                        // Clear the chat box and reload messages
                        adminChatBox.innerHTML = '<p class="text-center" style="color: var(--color-text-secondary);">Loading messages...</p>';
                        lastMessageId = 0;
                        loadMessages();
                    } else {
                        alert('Failed to delete messages: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error deleting messages:', error);
                    alert('Error deleting messages');
                });
            }
        });

        // Load messages initially and then poll every 3 seconds
        loadMessages();
        setInterval(loadMessages, 3000);
        </script>

    <?php elseif ($view === 'admin_chat'): ?>
        <div class="card">
            <h3>Admin Chat</h3>
            <div class="chat-box" id="admin-only-chat-box">
                <!-- Messages will be loaded here -->
                <p class="text-center" style="color: var(--color-text-secondary);">Loading admin messages...</p>
            </div>
            <div class="chat-input-area">
                <textarea id="admin-only-message-input" placeholder="Send message to other admins..." rows="2"></textarea>
                <button id="admin-only-send-button" class="btn btn-primary">Send</button>
                <button id="delete-all-admin-messages" class="btn btn-danger" style="margin-left: 10px;">Delete All Messages</button>
            </div>
        </div>
        <script>
        // Admin-only chat functionality
        let adminLastMessageId = 0;
        const adminOnlyChatBox = document.getElementById('admin-only-chat-box');
        const adminOnlyMessageInput = document.getElementById('admin-only-message-input');
        const adminOnlySendButton = document.getElementById('admin-only-send-button');

        function loadAdminMessages() {
            fetch('admin_chat_api.php?last_id=' + adminLastMessageId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            const messageDiv = document.createElement('div');
                            messageDiv.className = 'chat-message ' + (msg.is_admin ? 'admin-message' : 'user-message');
                            messageDiv.innerHTML = `
                                <strong>${msg.username} (${msg.role}):</strong> ${msg.message}
                                <small>${new Date(msg.sent_at).toLocaleString()}</small>
                            `;
                            adminOnlyChatBox.appendChild(messageDiv);
                            adminLastMessageId = Math.max(adminLastMessageId, msg.id);
                        });
                        adminOnlyChatBox.scrollTop = adminOnlyChatBox.scrollHeight;
                    }
                })
                .catch(error => console.error('Error loading admin messages:', error));
        }

        function sendAdminMessage() {
            const message = adminOnlyMessageInput.value.trim();
            if (!message) return;

            fetch('admin_chat_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: message })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    adminOnlyMessageInput.value = '';
                    loadAdminMessages(); // Reload to show the new message
                } else {
                    alert('Failed to send message: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Error sending message');
            });
        }

        adminOnlySendButton.addEventListener('click', sendAdminMessage);
        adminOnlyMessageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendAdminMessage();
            }
        });

        // Delete all admin messages functionality
        const deleteAllAdminButton = document.getElementById('delete-all-admin-messages');
        deleteAllAdminButton.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete ALL admin messages? This action cannot be undone.')) {
                fetch('admin_chat_api.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('All admin messages have been deleted.');
                        // Clear the chat box and reload messages
                        adminOnlyChatBox.innerHTML = '<p class="text-center" style="color: var(--color-text-secondary);">Loading admin messages...</p>';
                        adminLastMessageId = 0;
                        loadAdminMessages();
                    } else {
                        alert('Failed to delete messages: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error deleting messages:', error);
                    alert('Error deleting messages');
                });
            }
        });

        // Load messages initially and then poll every 3 seconds
        loadAdminMessages();
        setInterval(loadAdminMessages, 3000);
        </script>

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

    <?php elseif ($view === 'manage_admins' && $role === ROLE_SUPER_ADMIN): ?>
        <div class="card">
            <h3>Manage Admins</h3>

            <!-- Create New Admin Form -->
            <h4>Create New Admin</h4>
            <form method="POST" action="admin_dashboard.php?view=manage_admins">
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
                <button type="submit" name="create_admin" class="btn btn-primary">Create Admin</button>
            </form>

            <!-- Current Admins -->
            <h4>Current Admins</h4>
            <p>Manage existing admin privileges.</p>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get users who are admins but not super admins
                    $current_admins = array_filter($all_users, function($user) {
                        return $user['role'] === ROLE_ADMIN;
                    });
                    foreach ($current_admins as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="role-<?php echo htmlspecialchars($user['role']); ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                            <td><span class="status-<?php echo $user['is_active'] ? 'active' : 'suspended'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Suspended'; ?></span></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="demote_from_admin" value="1" class="btn btn-warning btn-sm" onclick="return confirm('Are you sure you want to demote this admin to regular user?')">Demote to User</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Promote Existing Users to Admin -->
            <h4>Promote Users to Admin</h4>
            <p>Select from existing users to promote them to admin role.</p>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get users who are not admins or super admins
                    $promotable_users = array_filter($all_users, function($user) {
                        return $user['role'] !== ROLE_ADMIN && $user['role'] !== ROLE_SUPER_ADMIN;
                    });
                    foreach ($promotable_users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="role-<?php echo htmlspecialchars($user['role']); ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                            <td><span class="status-<?php echo $user['is_active'] ? 'active' : 'suspended'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Suspended'; ?></span></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="promote_to_admin" value="1" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to promote this user to admin?')">Promote to Admin</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($view === 'edit_user' && $role === ROLE_SUPER_ADMIN): ?>
        <?php
        $edit_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($edit_user_id <= 0) {
            echo "<div class='card'><p>Invalid user ID.</p></div>";
        } else {
            $edit_user = $userModel->findById($edit_user_id);
            if (!$edit_user) {
                echo "<div class='card'><p>User not found.</p></div>";
            } else {
        ?>
        <div class="card">
            <h3>Edit User: <?php echo htmlspecialchars($edit_user['username']); ?></h3>
            <form method="POST" action="admin_dashboard.php?view=edit_user&user_id=<?php echo $edit_user_id; ?>" enctype="multipart/form-data">
                <input type="hidden" name="user_id" value="<?php echo $edit_user_id; ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($edit_user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">New Password (leave blank to keep current)</label>
                    <input type="password" id="password" name="password">
                    <small class="form-text text-muted">Leave blank to keep the current password</small>
                </div>
                <div class="form-group">
                    <label for="profile_pic">Profile Picture</label>
                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
                    <small class="form-text text-muted">Leave empty to keep current profile picture</small>
                    <?php if ($edit_user['profile_pic']): ?>
                        <br><img src="<?php echo htmlspecialchars($edit_user['profile_pic']); ?>" alt="Current Profile Picture" style="max-width: 100px; max-height: 100px;">
                    <?php endif; ?>
                </div>
                <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                <a href="admin_dashboard.php?view=manage_users" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
        <?php
            }
        }
        ?>

    <?php elseif ($view === 'settings' && $role === ROLE_SUPER_ADMIN): ?>
        <div class="card">
            <h3>Update Your Profile</h3>
            <form method="POST" action="admin_dashboard.php?view=settings" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                <div class="form-group">
                    <label for="profile_username">Username</label>
                    <input type="text" id="profile_username" name="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="profile_email">Email</label>
                    <input type="email" id="profile_email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="profile_password">New Password (leave blank to keep current)</label>
                    <input type="password" id="profile_password" name="password">
                    <small class="form-text text-muted">Leave blank to keep the current password</small>
                </div>
                <div class="form-group">
                    <label for="profile_pic">Profile Picture</label>
                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
                    <small class="form-text text-muted">Leave empty to keep current profile picture</small>
                    <?php if ($currentUser['profile_pic']): ?>
                        <br><img src="<?php echo htmlspecialchars($currentUser['profile_pic']); ?>" alt="Current Profile Picture" style="max-width: 100px; max-height: 100px;">
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>

        <div class="card">
            <h3>System Settings</h3>
            <?php $settings = $systemSettings->getAll(); ?>
            <form method="POST" action="admin_dashboard.php?view=settings">
                <input type="hidden" name="update_settings" value="1">
                <div class="form-group">
                    <label for="site_name">Site Name</label>
                    <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="site_description">Site Description</label>
                    <textarea id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="admin_email">Admin Email</label>
                    <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($settings['admin_email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <div class="maintenance-toggle">
                        <label for="maintenance_mode">Maintenance Mode</label>
                        <label class="switch" for="maintenance_mode" id="maintenance_switch">
                            <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo ($settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?> style="display:none;">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <small class="form-text text-muted">Toggle to enable/disable maintenance mode immediately</small>
                </div>
                <button type="submit" class="btn btn-primary">Update Settings</button>
            </form>
        </div>

    <?php elseif ($view === 'share_problem'): ?>
        <div class="card">
            <h3>Share Problem</h3>
            <form method="POST" action="admin_dashboard.php?view=share_problem" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="problem_title">Problem Title</label>
                    <input type="text" id="problem_title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="problem_description">Problem Description</label>
                    <textarea id="problem_description" name="description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="problem_category">Category</label>
                    <select id="problem_category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="Technical">Technical</option>
                        <option value="Hardware">Hardware</option>
                        <option value="Software">Software</option>
                        <option value="Network">Network</option>
                        <option value="Security">Security</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="problem_media">Media Files (Images/Videos)</label>
                    <input type="file" id="problem_media" name="media[]" accept="image/*,video/*" multiple>
                    <small class="form-text text-muted">You can select multiple files</small>
                </div>
                <button type="submit" class="btn btn-primary">Share Problem</button>
            </form>
        </div>

    <?php elseif ($view === 'product_showcase'): ?>
        <div class="card">
            <h3>Product Showcase</h3>
            <form method="POST" action="admin_dashboard.php?view=product_showcase" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="product_name">Product Name</label>
                    <input type="text" id="product_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="product_description">Product Description</label>
                    <textarea id="product_description" name="description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="demo_video_path">Demo Video URL (Optional)</label>
                    <input type="url" id="demo_video_path" name="demo_video_path" placeholder="https://example.com/demo.mp4">
                    <small class="form-text text-muted">Leave blank if no demo video</small>
                </div>
                <div class="form-group">
                    <label for="product_media">Media Files (Images/Videos)</label>
                    <input type="file" id="product_media" name="media[]" accept="image/*,video/*" multiple>
                    <small class="form-text text-muted">You can select multiple files</small>
                </div>
                <button type="submit" class="btn btn-primary">Showcase Product</button>
            </form>
        </div>

    <?php elseif ($view === 'tutorial_upload'): ?>
        <div class="card">
            <h3>Upload Tutorial</h3>
            <form method="POST" action="admin_dashboard.php?view=tutorial_upload" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="tutorial_title">Tutorial Title</label>
                    <input type="text" id="tutorial_title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="tutorial_content">Tutorial Content</label>
                    <textarea id="tutorial_content" name="content" rows="6" required></textarea>
                </div>
                <div class="form-group">
                    <label for="tutorial_category">Category</label>
                    <select id="tutorial_category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="Programming">Programming</option>
                        <option value="Web Development">Web Development</option>
                        <option value="Mobile Development">Mobile Development</option>
                        <option value="Database">Database</option>
                        <option value="DevOps">DevOps</option>
                        <option value="Security">Security</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tutorial_media">Media Files (Images/Videos)</label>
                    <input type="file" id="tutorial_media" name="media[]" accept="image/*,video/*" multiple>
                    <small class="form-text text-muted">You can select multiple files</small>
                </div>
                <button type="submit" class="btn btn-primary">Upload Tutorial</button>
            </form>
        </div>

    <?php endif; ?>

</div>

<?php include dirname(__DIR__) . '/app/views/includes/footer.php'; ?>

<script>
// Session timeout functionality for admin and superadmin
(function() {
    const SESSION_TIMEOUT = 3 * 60 * 1000; // 3 minutes in milliseconds
    const WARNING_TIME = 1 * 60 * 1000; // Show warning 1 minute before timeout

    let timeoutId;
    let warningShown = false;

    function resetTimer() {
        clearTimeout(timeoutId);
        warningShown = false;
        timeoutId = setTimeout(showWarning, SESSION_TIMEOUT - WARNING_TIME);
    }

    function showWarning() {
        if (!warningShown) {
            warningShown = true;
            alert('Your session will expire in 1 minute due to inactivity. Please save your work.');
            timeoutId = setTimeout(logout, WARNING_TIME);
        }
    }

    function logout() {
        window.location.href = 'logout.php?message=Your session has expired due to inactivity.';
    }

    // Reset timer on user activity
    document.addEventListener('click', resetTimer);
    document.addEventListener('keypress', resetTimer);
    document.addEventListener('scroll', resetTimer);

    // Start the timer
    resetTimer();
})();

// Search functionality for manage users
document.addEventListener('DOMContentLoaded', function() {
    const userSearch = document.getElementById('user-search');
    if (userSearch) {
        userSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.querySelector('.user-table');
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const username = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                const role = row.cells[3].textContent.toLowerCase();

                if (username.includes(searchTerm) || email.includes(searchTerm) || role.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Search functionality for manage content
    const contentSearch = document.getElementById('content-search');
    if (contentSearch) {
        contentSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const activeTab = document.querySelector('.content-tab.active');
            const rows = activeTab.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const title = row.cells[2].textContent.toLowerCase();
                const category = row.cells[3].textContent.toLowerCase();
                const user = row.cells[1].textContent.toLowerCase();

                if (title.includes(searchTerm) || category.includes(searchTerm) || user.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>
