<?php
/**
 * Fine Management - Librarian
 * Library Management System
 */

require_once '../config/config.php';
requireRole('librarian');

$pageTitle = 'Fine Management';

// Fetch records with fines
try {
    $db = getDB();
    $stmt = $db->query("SELECT br.*, s.student_id, s.full_name as student_name, s.email, b.book_title,
                        DATEDIFF(COALESCE(br.return_date, CURDATE()), br.due_date) as overdue_days
                        FROM borrow_records br
                        INNER JOIN students s ON br.student_id = s.id
                        INNER JOIN books b ON br.book_id = b.id
                        WHERE br.fine_amount > 0
                        ORDER BY br.fine_amount DESC");
    $fines = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fines fetch error: " . $e->getMessage());
}

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
                <h3>Fines and Penalties</h3>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Book Title</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Days Overdue</th>
                        <th>Fine Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fines)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No fines recorded</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($fines as $fine): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fine['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($fine['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($fine['book_title']); ?></td>
                        <td><?php echo formatDate($fine['due_date'], 'M d, Y'); ?></td>
                        <td><?php echo $fine['return_date'] ? formatDate($fine['return_date'], 'M d, Y') : 'Not Returned'; ?></td>
                        <td><?php echo max(0, $fine['overdue_days']); ?> days</td>
                        <td><strong><?php echo formatCurrency($fine['fine_amount']); ?></strong></td>
                        <td>
                            <span class="badge badge-<?php echo $fine['status'] == 'returned' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($fine['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
