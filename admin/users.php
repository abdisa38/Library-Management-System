<?php
require_once '../config/config.php';
requireRole('super_admin');
$pageTitle = 'Manage Users';
include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Manage Users</h1>
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <span>/</span><span>Users</span>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All System Users</h3>
            </div>
            <div class="card-body">
                <?php
                try {
                    $db = getDB();
                    $stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
                    $users = $stmt->fetchAll();
                ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><span class="badge badge-primary"><?php echo str_replace('_', ' ', $user['role']); ?></span></td>
                                <td><span class="badge badge-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>"><?php echo $user['status']; ?></span></td>
                                <td><?php echo formatDate($user['created_at'], 'M d, Y'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php } catch (PDOException $e) { echo '<p>Error loading users.</p>'; } ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
