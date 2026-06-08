<?php
require_once '../config/config.php';
requireRole('librarian');
$pageTitle = 'Fine Management';
include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Fine Management</h1>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Fines</h3>
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
                ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Book</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                                <th>Amount</th>
                                <th>Reason</th>
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
                                <td><?php echo htmlspecialchars($fine['reason']); ?></td>
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
