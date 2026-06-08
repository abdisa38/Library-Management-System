<?php
/**
 * Notifications - Student
 * Library Management System
 */

require_once '../config/config.php';
requireRole('student');

$pageTitle = 'Notifications';
$currentUser = getCurrentUser();

// Mark notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read']) && validateCSRFToken($_POST['csrf_token'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['notification_id'], $currentUser['id']]);
        setSuccessMessage('Notification marked as read');
        redirect(SITE_URL . '/student/notifications.php');
    } catch (PDOException $e) {
        error_log("Mark read error: " . $e->getMessage());
        setErrorMessage('Error updating notification');
    }
}

// Mark all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read']) && validateCSRFToken($_POST['csrf_token'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE user_id = ? AND status = 'unread'");
        $stmt->execute([$currentUser['id']]);
        setSuccessMessage('All notifications marked as read');
        redirect(SITE_URL . '/student/notifications.php');
    } catch (PDOException $e) {
        error_log("Mark all read error: " . $e->getMessage());
        setErrorMessage('Error updating notifications');
    }
}

// Fetch notifications
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$currentUser['id']]);
    $notifications = $stmt->fetchAll();
    
    // Count unread
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND status = 'unread'");
    $stmt->execute([$currentUser['id']]);
    $unreadCount = $stmt->fetch()['total'];
} catch (PDOException $e) {
    error_log("Notifications fetch error: " . $e->getMessage());
    setErrorMessage("Error loading notifications");
}

include '../includes/header.php';
include '../includes/sidebar_student.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Notifications</h1>
            <?php if ($unreadCount > 0): ?>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="mark_all_read" value="1">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-check-double"></i> Mark All as Read
                </button>
            </form>
            <?php endif; ?>
        </div>
        
        <?php if ($success = getSuccessMessage()): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $success; ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($error = getErrorMessage()): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>All Notifications</h3>
                <?php if ($unreadCount > 0): ?>
                <span class="badge badge-danger"><?php echo $unreadCount; ?> Unread</span>
                <?php endif; ?>
            </div>
            
            <?php if (empty($notifications)): ?>
            <div style="padding: 3rem; text-align: center;">
                <i class="fas fa-bell-slash" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                <h3>No Notifications</h3>
                <p style="color: var(--text-secondary);">You don't have any notifications yet</p>
            </div>
            <?php else: ?>
            <div style="padding: 0;">
                <?php foreach ($notifications as $notification): ?>
                <div style="padding: 1rem; border-bottom: 1px solid #eee; background: <?php echo $notification['status'] == 'unread' ? '#f8f9ff' : 'white'; ?>; display: flex; gap: 1rem; align-items: start;">
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <?php if ($notification['status'] == 'unread'): ?>
                            <span style="width: 8px; height: 8px; background: #4f46e5; border-radius: 50%;"></span>
                            <?php endif; ?>
                            <span style="color: var(--text-secondary); font-size: 0.875rem;">
                                <i class="fas fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                            </span>
                        </div>
                        <p style="margin: 0; color: var(--text-primary);"><?php echo htmlspecialchars($notification['message']); ?></p>
                    </div>
                    <?php if ($notification['status'] == 'unread'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                        <input type="hidden" name="mark_read" value="1">
                        <button type="submit" class="btn btn-sm btn-secondary">
                            <i class="fas fa-check"></i> Mark as Read
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
