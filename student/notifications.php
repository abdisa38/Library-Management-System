<?php
require_once '../config/config.php';
requireRole('student');
$pageTitle = 'Notifications';
$currentUser = getCurrentUser();

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$currentUser['id']]);
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    $notifications = [];
}

include '../includes/header.php';
include '../includes/sidebar_student.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Notifications</h1>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">My Notifications</h3>
            </div>
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell"></i>
                    <h3>No Notifications</h3>
                    <p>You don't have any notifications yet.</p>
                </div>
                <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                <div class="alert alert-<?php echo $notif['status'] == 'unread' ? 'info' : 'secondary'; ?>" style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <i class="fas fa-info-circle"></i>
                            <span><?php echo htmlspecialchars($notif['message']); ?></span>
                        </div>
                        <small style="color: var(--text-muted);"><?php echo formatDate($notif['created_at'], 'M d, Y h:i A'); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
