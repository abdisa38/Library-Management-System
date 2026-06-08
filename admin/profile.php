<?php
require_once '../config/config.php';
requireLogin();
$pageTitle = 'My Profile';
$currentUser = getCurrentUser();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        $stmt->execute([$fullName, $email, $currentUser['id']]);
        
        $_SESSION['full_name'] = $fullName;
        $_SESSION['email'] = $email;
        
        setSuccessMessage('Profile updated successfully!');
        redirect($_SERVER['PHP_SELF']);
    } catch (PDOException $e) {
        setErrorMessage('Error updating profile.');
    }
}

$role = $_SESSION['role'];
if ($role === 'super_admin') {
    include '../includes/header.php';
    include '../includes/sidebar_admin.php';
} elseif ($role === 'librarian') {
    include '../includes/header.php';
    include '../includes/sidebar_librarian.php';
} else {
    include '../includes/header.php';
    include '../includes/sidebar_student.php';
}
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">My Profile</h1>
        </div>
        
        <?php if ($success = getSuccessMessage()): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <span><?php echo $success; ?></span></div>
        <?php endif; ?>
        
        <div class="card" style="max-width: 600px;">
            <div class="card-header">
                <h3 class="card-title">Profile Information</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?php echo ucwords(str_replace('_', ' ', $currentUser['role'])); ?>" disabled>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
