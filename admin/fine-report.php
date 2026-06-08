<?php
require_once '../config/config.php';
requireRole('super_admin');
$pageTitle = 'Fine Reports';
include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Fine Reports</h1>
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <span>/</span><span>Fine Reports</span>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Fines</h3>
                <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            </div>
            <div class="card-body">
                <?php
                try {
                    $db = getDB();
                    $stmt = $db->query("
                        SELECT f.*, br.borrow_date, br.due_date, br.return_date,
                               s.student_id, s.full_name as student_name,
                               b.book_title
                        FROM fines f
                        INNER JOIN borrow_records br ON f.borrow_id = br.id
                        INNER JOIN students s ON br.student_id = s.id
                        INNER JOIN books b ON br.book_id = b.id
                        ORDER BY f.created_at DESC
                    ");
                    $fines = $stmt->fetchAll();
                    
                    $totalFines = array_sum(array_column($fines, 'amount'));
                    $unpaidFines = array_sum(array_map(function($f) { 
                        return $f['paid_status'] == 'unpaid' ? $f['amount'] : 0; 
                    }, $fines));
                ?>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                    <div class="stat-card success">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo formatCurrency($totalFines); ?></h3>
                                <p>Total Fines</p>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo formatCurrency($unpaidFines); ?></h3>
                                <p>Unpaid Fines</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Book</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                                <th>Fine Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fines as $fine): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fine['student_name']); ?><br><small><?php echo $fine['student_id']; ?></small></td>
                                <td><?php echo htmlspecialchars($fine['book_title']); ?></td>
                                <td><?php echo formatDate($fine['due_date'], 'M d, Y'); ?></td>
                                <td><?php echo formatDate($fine['return_date'], 'M d, Y'); ?></td>
                                <td><strong><?php echo formatCurrency($fine['amount']); ?></strong></td>
                                <td><span class="badge badge-<?php echo $fine['paid_status'] == 'paid' ? 'success' : 'danger'; ?>"><?php echo $fine['paid_status']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php } catch (PDOException $e) { echo '<p>Error loading fines.</p>'; } ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
