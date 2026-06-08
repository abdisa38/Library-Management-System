<?php
/**
 * Profile - Librarian
 * Library Management System
 */

require_once '../config/config.php';
requireRole('librarian');

$pageTitle = 'My Profile';
$currentUser = getCurrentUser();

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
include '../includes/sidebar_librarian.php';
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
                <h3>Profile Information</h3>
            </div>
            
            <div style="padding: 1.5rem;">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($currentUser['email']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Role</label>
                    <input type="text" class="form-control" value="<?php echo ucfirst(str_replace('_', ' ', $currentUser['role'])); ?>" disabled>
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
