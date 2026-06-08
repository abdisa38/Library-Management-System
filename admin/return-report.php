<?php
require_once '../config/config.php';
requireRole('super_admin');
$pageTitle = 'Return Reports';
include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Return Reports</h1>
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <span>/</span><span>Return Reports</span>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Returned Books</h3>
                <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            </div>
            <div class="card-body">
                <?php
                try {
                    $db = getDB();
                    $stmt = $db->query("
                        SELECT br.*, s.student_id, s.full_name as student_name, b.book_title, b.author
                        FROM borrow_records br
                        INNER JOIN students s ON br.student_id = s.id
                        INNER JOIN books b ON br.book_id = b.id
                        WHERE br.status = 'returned'
                        ORDER BY br.return_date DESC
                        LIMIT 100
                    ");
                    $records = $stmt->fetchAll();
                ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Book</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                                <th>Fine</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $rec): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rec['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($rec['book_title']); ?></td>
                                <td><?php echo formatDate($rec['borrow_date'], 'M d, Y'); ?></td>
                                <td><?php echo formatDate($rec['due_date'], 'M d, Y'); ?></td>
                                <td><?php echo formatDate($rec['return_date'], 'M d, Y'); ?></td>
                                <td><?php echo $rec['fine_amount'] > 0 ? formatCurrency($rec['fine_amount']) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php } catch (PDOException $e) { echo '<p>Error loading records.</p>'; } ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
