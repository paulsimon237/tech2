<nav class="sidebar">
    <ul>
        <li><a href="admin_dashboard.php?view=home" class="<?php echo $view === 'home' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="admin_dashboard.php?view=manage_users" class="<?php echo $view === 'manage_users' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Manage Users</a></li>
        <?php if ($role === ROLE_SUPER_ADMIN): ?>
        <li><a href="admin_dashboard.php?view=manage_admins" class="<?php echo $view === 'manage_admins' ? 'active' : ''; ?>"><i class="fas fa-user-shield"></i> Manage Admins</a></li>
        <li><a href="admin_dashboard.php?view=settings" class="<?php echo $view === 'settings' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a></li>
        <?php endif; ?>
        <li><a href="admin_dashboard.php?view=manage_content" class="<?php echo $view === 'manage_content' ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> Manage Content</a></li>
        <li><a href="admin_dashboard.php?view=share_problem" class="<?php echo $view === 'share_problem' ? 'active' : ''; ?>"><i class="fas fa-exclamation-triangle"></i> Share Problem</a></li>
        <li><a href="admin_dashboard.php?view=product_showcase" class="<?php echo $view === 'product_showcase' ? 'active' : ''; ?>"><i class="fas fa-box"></i> Product Showcase</a></li>
        <li><a href="admin_dashboard.php?view=tutorial_upload" class="<?php echo $view === 'tutorial_upload' ? 'active' : ''; ?>"><i class="fas fa-book"></i> Upload Tutorial</a></li>
        <li><a href="admin_dashboard.php?view=chat_monitor" class="<?php echo $view === 'chat_monitor' ? 'active' : ''; ?>"><i class="fas fa-comments"></i> Chat Monitor</a></li>
        <li><a href="admin_dashboard.php?view=admin_chat" class="<?php echo $view === 'admin_chat' ? 'active' : ''; ?>"><i class="fas fa-user-shield"></i> Admin Chat</a></li>
        <li><a href="admin_dashboard.php?view=chat" class="<?php echo $view === 'chat' ? 'active' : ''; ?>"><i class="fas fa-video"></i> Chat & Calls</a></li>
    </ul>
</nav>
