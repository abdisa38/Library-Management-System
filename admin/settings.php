<?php
require_once '../config/config.php';
requireRole('super_admin');
$pageTitle = 'System Settings';
include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">System Settings</h1>
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <span>/</span><span>Settings</span>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Library Configuration</h3>
            </div>
            <div class="card-body">
                <?php
                try {
                    $db = getDB();
                    $stmt = $db->query("SELECT * FROM system_settings ORDER BY setting_key");
                    $settings = $stmt->fetchAll();
                ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Setting</th>
                                <th>Value</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($settings as $setting): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $setting['setting_key']))); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($setting['setting_value']); ?></code></td>
                                <td><?php echo htmlspecialchars($setting['description']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info" style="margin-top: 1rem;">
                    <i class="fas fa-info-circle"></i>
                    <span>To modify settings, edit them in config/config.php or database table 'system_settings'</span>
                </div>
                <?php } catch (PDOException $e) { echo '<p>Error loading settings.</p>'; } ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
