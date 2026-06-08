<?php
/**
 * Notifications - Librarian
 * Library Management System
 */

require_once '../config/config.php';
requireRole('librarian');

$pageTitle = 'Notifications';
$currentUser = getCurrentUser();

// Handle sending notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message']) && validateCSRFToken($_POST['csrf_token'])) {
    try {
        $db = getDB();
        
        $recipientType = $_POST['recipient_type'];
        $message = "Message from Librarian ({$currentUser['full_name']}): " . $_POST['message'];
        
        if ($recipientType === 'all_students') {
            // Send to all students
            $stmt = $db->query("SELECT user_id FROM students WHERE user_id IS NOT NULL");
            $students = $stmt->fetchAll();
            foreach ($students as $stu) {
                sendNotification($stu['user_id'], $message);
            }
            setSuccessMessage('Message sent to all students');
        } elseif ($recipientType === 'specific_student') {
            // Send to specific student
            $studentId = $_POST['student_id'];
            $stmt = $db->prepare("SELECT user_id FROM students WHERE id = ?");
            $stmt->execute([$studentId]);
            $student = $stmt->fetch();
            if ($student && $student['user_id']) {
                sendNotification($student['user_id'], $message);
                setSuccessMessage('Message sent successfully');
            } else {
                setErrorMessage('Student user account not found');
            }
        } elseif ($recipientType === 'admin') {
            // Send to admin
            $stmt = $db->query("SELECT id FROM users WHERE role = 'super_admin' AND status = 'active'");
            $admins = $stmt->fetchAll();
            foreach ($admins as $admin) {
                sendNotification($admin['id'], $message);
            }
            setSuccessMessage('Message sent to admin');
        }
        
        redirect(SITE_URL . '/librarian/notifications.php');
    } catch (PDOException $e) {
        error_log("Send message error: " . $e->getMessage());
        setErrorMessage('Error sending message');
    }
}

// Mark notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read']) && validateCSRFToken($_POST['csrf_token'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['notification_id'], $currentUser['id']]);
        setSuccessMessage('Notification marked as read');
        redirect(SITE_URL . '/librarian/notifications.php');
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
        redirect(SITE_URL . '/librarian/notifications.php');
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
    
    // Get all students for dropdown
    $allStudents = $db->query("SELECT id, student_id, full_name, email FROM students ORDER BY full_name")->fetchAll();
} catch (PDOException $e) {
    error_log("Notifications fetch error: " . $e->getMessage());
    setErrorMessage("Error loading notifications");
}

include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Notifications</h1>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-sm btn-primary" onclick="openSendModal()">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
                <?php if ($unreadCount > 0): ?>
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

<!-- Send Message Modal -->
<div id="sendModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Send Message</h2>
            <span class="close" onclick="closeSendModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="send_message" value="1">
            
            <div class="form-group">
                <label>Recipient Type *</label>
                <select name="recipient_type" id="recipient_type" class="form-control" required onchange="toggleStudentSelect()">
                    <option value="">Select Recipient Type</option>
                    <option value="all_students">All Students</option>
                    <option value="specific_student">Specific Student</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <div class="form-group" id="student_select_group" style="display: none;">
                <label>Select Student *</label>
                <select name="student_id" id="student_id" class="form-control">
                    <option value="">Select Student</option>
                    <?php foreach ($allStudents as $student): ?>
                    <option value="<?php echo $student['id']; ?>">
                        <?php echo htmlspecialchars($student['student_id']) . ' - ' . htmlspecialchars($student['full_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Message *</label>
                <textarea name="message" class="form-control" rows="4" required placeholder="Type your message here..."></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeSendModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Message
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

function toggleStudentSelect() {
    const recipientType = document.getElementById('recipient_type').value;
    const studentSelectGroup = document.getElementById('student_select_group');
    const studentSelect = document.getElementById('student_id');
    
    if (recipientType === 'specific_student') {
        studentSelectGroup.style.display = 'block';
        studentSelect.required = true;
    } else {
        studentSelectGroup.style.display = 'none';
        studentSelect.required = false;
    }
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
