<?php
/**
 * Return Book - Librarian
 * Library Management System
 */

require_once '../config/config.php';
requireRole('librarian');

$pageTitle = 'Return Book';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'])) {
    try {
        $db = getDB();
        
        $borrowId = $_POST['borrow_id'];
        $returnDate = date('Y-m-d');
        
        // Get borrow record
        $stmt = $db->prepare("SELECT * FROM borrow_records WHERE id = ? AND status IN ('borrowed', 'overdue')");
        $stmt->execute([$borrowId]);
        $borrow = $stmt->fetch();
        
        if (!$borrow) {
            setErrorMessage('Borrow record not found or already returned');
        } else {
            // Calculate fine if overdue
            $overdueDays = calculateOverdueDays($borrow['due_date']);
            $fine = $overdueDays > 0 ? calculateFine($overdueDays) : 0;
            
            // Update borrow record
            $stmt = $db->prepare("UPDATE borrow_records SET status = 'returned', return_date = ?, fine_amount = ? WHERE id = ?");
            $stmt->execute([$returnDate, $fine, $borrowId]);
            
            setSuccessMessage('Book returned successfully!' . ($fine > 0 ? " Fine: " . formatCurrency($fine) : ''));
            redirect(SITE_URL . '/librarian/return-book.php');
        }
    } catch (PDOException $e) {
        error_log("Return book error: " . $e->getMessage());
        setErrorMessage('Error processing return');
    }
}

// Fetch active borrows
try {
    $db = getDB();
    $stmt = $db->query("SELECT br.*, s.student_id, s.full_name as student_name, b.book_title, b.isbn, b.author,
                        DATEDIFF(CURDATE(), br.due_date) as overdue_days
                        FROM borrow_records br
                        INNER JOIN students s ON br.student_id = s.id
                        INNER JOIN books b ON br.book_id = b.id
                        WHERE br.status IN ('borrowed', 'overdue')
                        ORDER BY br.due_date ASC");
    $activeBorrows = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch borrows error: " . $e->getMessage());
}

include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Return Book</h1>
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
                <h3>Active Borrows</h3>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Book Title</th>
                        <th>ISBN</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Fine</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activeBorrows)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">No active borrows</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($activeBorrows as $borrow): ?>
                    <?php 
                    $overdueDays = max(0, $borrow['overdue_days']);
                    $fine = $overdueDays > 0 ? calculateFine($overdueDays) : 0;
                    $isOverdue = $overdueDays > 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($borrow['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($borrow['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($borrow['book_title']); ?></td>
                        <td><?php echo htmlspecialchars($borrow['isbn']); ?></td>
                        <td><?php echo formatDate($borrow['borrow_date'], 'M d, Y'); ?></td>
                        <td><?php echo formatDate($borrow['due_date'], 'M d, Y'); ?></td>
                        <td>
                            <span class="badge <?php echo $isOverdue ? 'badge-danger' : 'badge-success'; ?>">
                                <?php echo $isOverdue ? "Overdue ($overdueDays days)" : 'On Time'; ?>
                            </span>
                        </td>
                        <td><?php echo $fine > 0 ? formatCurrency($fine) : '-'; ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="borrow_id" value="<?php echo $borrow['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fas fa-check"></i> Return
                                </button>
                            </form>
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
