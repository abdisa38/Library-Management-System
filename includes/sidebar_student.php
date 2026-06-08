<?php
$currentUser = getCurrentUser();
$userInitials = strtoupper(substr($currentUser['full_name'], 0, 2));
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <i class="fas fa-book-reader"></i>
            <span>LMS Student</span>
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
            <a href="<?php echo SITE_URL; ?>/student/dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/student/available-books.php" class="menu-item">
                <i class="fas fa-book"></i>
                <span>Available Books</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/student/borrowed-books.php" class="menu-item">
                <i class="fas fa-book-reader"></i>
                <span>My Borrowed Books</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/student/history.php" class="menu-item">
                <i class="fas fa-history"></i>
                <span>Borrow History</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/student/notifications.php" class="menu-item">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-title">Account</div>
            <a href="<?php echo SITE_URL; ?>/student/profile.php" class="menu-item">
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
