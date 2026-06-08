<?php
require_once '../config/config.php';
requireRole('super_admin');
$pageTitle = 'Manage Librarians';
include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Manage Librarians</h1>
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <span>/</span><span>Librarians</span>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Librarians</h3>
            </div>
            <div class="card-body">
                <?php
                try {
                    $db = getDB();
                    $stmt = $db->query("SELECT * FROM users WHERE role = 'librarian' ORDER BY created_at DESC");
                    $librarians = $stmt->fetchAll();
                    
                    if (empty($librarians)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-tie"></i>
                            <h3>No Librarians Found</h3>
                        </div>
                    <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Username</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($librarians as $lib): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($lib['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($lib['email']); ?></td>
                                    <td><?php echo htmlspecialchars($lib['username']); ?></td>
                                    <td><span class="badge badge-<?php echo $lib['status'] == 'active' ? 'success' : 'danger'; ?>"><?php echo $lib['status']; ?></span></td>
                                    <td><?php echo formatDate($lib['created_at'], 'M d, Y'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif;
                } catch (PDOException $e) { echo '<p>Error loading librarians.</p>'; } ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
