<?php
/**
 * Notifications - Admin
 * Library Management System
 */

require_once '../config/config.php';
requireRole('super_admin');

$pageTitle = 'Notifications';
$currentUser = getCurrentUser();

// Handle sending notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification']) && validateCSRFToken($_POST['csrf_token'])) {
    try {
        $db = getDB();
        
        $recipientType = $_POST['recipient_type'];
        $message = $_POST['message'];
        
        if ($recipientType === 'all_librarians') {
            // Send to all librarians
            $stmt = $db->query("SELECT id FROM users WHERE role = 'librarian' AND status = 'active'");
            $librarians = $stmt->fetchAll();
            foreach ($librarians as $lib) {
                sendNotification($lib['id'], $message);
            }
            setSuccessMessage('Notification sent to all librarians');
        } elseif ($recipientType === 'all_students') {
            // Send to all students
            $stmt = $db->query("SELECT user_id FROM students WHERE user_id IS NOT NULL");
            $students = $stmt->fetchAll();
            foreach ($students as $stu) {
                sendNotification($stu['user_id'], $message);
            }
            setSuccessMessage('Notification sent to all students');
        } elseif ($recipientType === 'specific_user') {
            // Send to specific user
            $userId = $_POST['user_id'];
            sendNotification($userId, $message);
            setSuccessMessage('Notification sent successfully');
        }
        
        redirect(SITE_URL . '/admin/notifications.php');
    } catch (PDOException $e) {
        error_log("Send notification error: " . $e->getMessage());
        setErrorMessage('Error sending notification');
    }
}

// Mark notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read']) && validateCSRFToken($_POST['csrf_token'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['notification_id'], $currentUser['id']]);
        setSuccessMessage('Notification marked as read');
        redirect(SITE_URL . '/admin/notifications.php');
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
        redirect(SITE_URL . '/admin/notifications.php');
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
    
    // Get all users for dropdown
    $allUsers = $db->query("SELECT id, full_name, email, role FROM users WHERE status = 'active' ORDER BY role, full_name")->fetchAll();
} catch (PDOException $e) {
    error_log("Notifications fetch error: " . $e->getMessage());
    setErrorMessage("Error loading notifications");
}

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Notifications</h1>
            <button class="btn btn-primary" onclick="openSendModal()">
                <i class="fas fa-paper-plane"></i> Send Notification
            </button>
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
                <h3>My Notifications</h3>
                <div style="display: flex; gap: 0.5rem;">
                    <?php if ($unreadCount > 0): ?>
                    <span class="badge badge-danger"><?php echo $unreadCount; ?> Unread</span>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="mark_all_read" value="1">
                        <button type="submit" class="btn btn-sm btn-secondary">
                            <i class="fas fa-check-double"></i> Mark All as Read
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
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
                            <i class="fas fa-check"></i>
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

<!-- Send Notification Modal -->
<div id="sendModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Send Notification</h2>
            <span class="close" onclick="closeSendModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="send_notification" value="1">
            
            <div class="form-group">
                <label>Recipient Type *</label>
                <select name="recipient_type" id="recipient_type" class="form-control" required onchange="toggleUserSelect()">
                    <option value="">Select Recipient Type</option>
                    <option value="all_librarians">All Librarians</option>
                    <option value="all_students">All Students</option>
                    <option value="specific_user">Specific User</option>
                </select>
            </div>
            
            <div class="form-group" id="user_select_group" style="display: none;">
                <label>Select User *</label>
                <select name="user_id" id="user_id" class="form-control">
                    <option value="">Select User</option>
                    <?php foreach ($allUsers as $user): ?>
                    <option value="<?php echo $user['id']; ?>">
                        <?php echo htmlspecialchars($user['full_name']) . ' (' . ucfirst(str_replace('_', ' ', $user['role'])) . ') - ' . $user['email']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Message *</label>
                <textarea name="message" class="form-control" rows="4" required placeholder="Enter your notification message..."></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeSendModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openSendModal() {
    document.getElementById('sendModal').style.display = 'flex';
}

function closeSendModal() {
    document.getElementById('sendModal').style.display = 'none';
}

function toggleUserSelect() {
    const recipientType = document.getElementById('recipient_type').value;
    const userSelectGroup = document.getElementById('user_select_group');
    const userSelect = document.getElementById('user_id');
    
    if (recipientType === 'specific_user') {
        userSelectGroup.style.display = 'block';
        userSelect.required = true;
    } else {
        userSelectGroup.style.display = 'none';
        userSelect.required = false;
    }
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
