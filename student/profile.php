<?php
/**
 * Profile - Student
 * Library Management System
 */

require_once '../config/config.php';
requireRole('student');

$pageTitle = 'My Profile';
$currentUser = getCurrentUser();

// Get student record
try {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->execute([$currentUser['id']]);
    $student = $stmt->fetch();
    
    if (!$student) {
        setErrorMessage("Student record not found");
        redirect(SITE_URL . '/auth/logout.php');
    }
} catch (PDOException $e) {
    error_log("Student fetch error: " . $e->getMessage());
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'])) {
    try {
        $db = getDB();
        
        // Verify current password
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$currentUser['id']]);
        $user = $stmt->fetch();
        
        if (password_verify($_POST['current_password'], $user['password'])) {
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $currentUser['id']]);
                
                setSuccessMessage('Password updated successfully!');
                redirect(SITE_URL . '/student/profile.php');
            } else {
                setErrorMessage('New passwords do not match');
            }
        } else {
            setErrorMessage('Current password is incorrect');
        }
    } catch (PDOException $e) {
        error_log("Password update error: " . $e->getMessage());
        setErrorMessage('Error updating password');
    }
}

include '../includes/header.php';
include '../includes/sidebar_student.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">My Profile</h1>
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
                <h3>Student Information</h3>
            </div>
            
            <div style="padding: 1.5rem;">
                <div class="form-group">
                    <label>Student ID</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['student_id']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['full_name']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['department']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Year</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['year']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['phone']); ?>" disabled>
                </div>
            </div>
        </div>
        
        <div class="card" style="margin-top: 1.5rem;">
            <div class="card-header">
                <h3>Change Password</h3>
            </div>
            
            <form method="POST" style="padding: 1.5rem;">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label>Current Password *</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                    <small>Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label>Confirm New Password *</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
