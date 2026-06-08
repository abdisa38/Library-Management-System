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
