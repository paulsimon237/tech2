<?php
require_once dirname(__DIR__) . '/app/config.php';

// Define the SITE_NAME constant
define('SITE_NAME', 'Multi-Role Tech Support Platform');

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on user role
    if ($_SESSION['role'] === ROLE_SUPER_ADMIN || $_SESSION['role'] === ROLE_ADMIN) {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: user_dashboard.php");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Tech Support Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="assets/css/splash.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="splash-container">
        <div class="splash-content">
            <div class="logo-section">
                <div class="logo">
                    <span class="logo-icon">🛠️</span>
                    <h1><?php echo SITE_NAME; ?></h1>
                </div>
                <p class="tagline">Your Complete Tech Support Solution</p>
            </div>

            <div class="features-section">
                <div class="feature-card">
                    <div class="feature-icon">💬</div>
                    <h3>Real-time Chat</h3>
                    <p>Get instant help from our support team through our integrated chat system</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📚</div>
                    <h3>Knowledge Base</h3>
                    <p>Access comprehensive tutorials and guides for common tech issues</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3>Problem Tracking</h3>
                    <p>Submit and track your technical issues with our advanced ticketing system</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📞</div>
                    <h3>Voice & Video</h3>
                    <p>Connect with support through voice calls and video conferencing</p>
                </div>
            </div>

            <div class="cta-section">
                <a href="login.php" class="btn btn-primary">Get Started</a>
                <a href="register.php" class="btn btn-secondary">Create Account</a>
            </div>

            <div class="stats-section">
                <div class="stat">
                    <div class="stat-number">1000+</div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat">
                    <div class="stat-number">5000+</div>
                    <div class="stat-label">Issues Resolved</div>
                </div>
                <div class="stat">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Support Available</div>
                </div>
            </div>
        </div>

        <div class="background-elements">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
            <div class="floating-shape shape-4"></div>
        </div>
    </div>

    <script>
        // Add some interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            const shapes = document.querySelectorAll('.floating-shape');

            shapes.forEach((shape, index) => {
                shape.style.animationDelay = `${index * 0.5}s`;
            });

            // Add hover effects to feature cards
            const featureCards = document.querySelectorAll('.feature-card');
            featureCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>
