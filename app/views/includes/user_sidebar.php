<div class="sidebar">
    <h2>User Panel</h2>
    <ul>
        <li><a href="user_dashboard.php?view=home" class="<?php echo $view === 'home' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="user_dashboard.php?view=share_problem" class="<?php echo $view === 'share_problem' ? 'active' : ''; ?>"><i class="fas fa-exclamation-triangle"></i> Share Problem</a></li>
        <li><a href="user_dashboard.php?view=product_showcase" class="<?php echo $view === 'product_showcase' ? 'active' : ''; ?>"><i class="fas fa-box"></i> Product Showcase</a></li>
        <li><a href="user_dashboard.php?view=tutorial_upload" class="<?php echo $view === 'tutorial_upload' ? 'active' : ''; ?>"><i class="fas fa-upload"></i> Upload Tutorial</a></li>
        <li><a href="user_dashboard.php?view=chat" class="<?php echo $view === 'chat' ? 'active' : ''; ?>"><i class="fas fa-comments"></i> Central Chat</a></li>
    </ul>
</div>
