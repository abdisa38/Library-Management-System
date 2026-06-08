<?php
$currentUser = getCurrentUser();
$userInitials = strtoupper(substr($currentUser['full_name'], 0, 2));
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <i class="fas fa-book-reader"></i>
            <span>LMS Admin</span>
        </div>
    </div>
    
    <div class="sidebar-user">
        <div class="user-info">
            <div class="user-avatar"><?php echo $userInitials; ?></div>
            <div class="user-details">
                <h4><?php echo htmlspecialchars($currentUser['full_name']); ?></h4>
                <span><?php echo str_replace('_', ' ', $currentUser['role']); ?></span>
            </div>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <div class="menu-section">
            <div class="menu-title">Main Menu</div>
            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/users.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>Manage Users</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/librarians.php" class="menu-item">
                <i class="fas fa-user-tie"></i>
                <span>Manage Librarians</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/students.php" class="menu-item">
                <i class="fas fa-user-graduate"></i>
                <span>Manage Students</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/books.php" class="menu-item">
                <i class="fas fa-book"></i>
                <span>Manage Books</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="menu-item">
                <i class="fas fa-tags"></i>
                <span>Categories</span>
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-title">Reports</div>
            <a href="<?php echo SITE_URL; ?>/admin/borrow-report.php" class="menu-item">
                <i class="fas fa-file-alt"></i>
                <span>Borrow Reports</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/return-report.php" class="menu-item">
                <i class="fas fa-undo"></i>
                <span>Return Reports</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/fine-report.php" class="menu-item">
                <i class="fas fa-money-bill"></i>
                <span>Fine Reports</span>
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-title">Settings</div>
            <a href="<?php echo SITE_URL; ?>/admin/settings.php" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>System Settings</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/notifications.php" class="menu-item">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
                <?php
                $unread = getUnreadNotificationCount($currentUser['id']);
                if ($unread > 0):
                ?>
                <span class="badge badge-danger" style="margin-left: auto;"><?php echo $unread; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/profile.php" class="menu-item">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</aside>
