<?php
$currentUser = getCurrentUser();
$unreadNotifications = 0; // You can query from database

// Get unread notifications count
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'unread'");
    $stmt->execute([$currentUser['id']]);
    $result = $stmt->fetch();
    $unreadNotifications = $result['count'];
} catch (PDOException $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
}
?>

<nav class="navbar">
    <div class="navbar-content">
        <div class="navbar-left">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="navbar-search">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search books, students...">
            </div>
        </div>
        
        <div class="navbar-right">
            <button class="theme-toggle" id="themeToggle" title="Toggle Theme">
                <i class="fas fa-moon"></i>
            </button>
            
            <button class="notification-btn" title="Notifications">
                <i class="fas fa-bell"></i>
                <?php if ($unreadNotifications > 0): ?>
                <span class="notification-badge"><?php echo $unreadNotifications; ?></span>
                <?php endif; ?>
            </button>
            
            <div class="user-menu">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </div>
</nav>
