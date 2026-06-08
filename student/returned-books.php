<?php
/**
 * Returned Books - Student
 * Library Management System
 */

require_once '../config/config.php';
requireRole('student');

$pageTitle = 'Returned Books';
$currentUser = getCurrentUser();

// Get student record
try {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->execute([$currentUser['id']]);
    $student = $stmt->fetch();
    
    if (!$student) {
        setErrorMessage("Student record not found");
        redirect(SITE_URL . '/auth/logout.php');
    }
    
    // Get returned books
    $stmt = $db->prepare("
        SELECT br.*, b.book_title, b.author, b.isbn, b.book_cover,
        DATEDIFF(br.return_date, br.due_date) as days_diff
        FROM borrow_records br
        INNER JOIN books b ON br.book_id = b.id
        WHERE br.student_id = ? AND br.status = 'returned'
        ORDER BY br.return_date DESC
        LIMIT 50
    ");
    $stmt->execute([$student['id']]);
    $returnedBooks = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Returned books fetch error: " . $e->getMessage());
    setErrorMessage("Error loading returned books");
}

include '../includes/header.php';
include '../includes/sidebar_student.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Returned Books</h1>
        </div>
        
        <?php if ($error = getErrorMessage()): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Books You Have Returned</h3>
                <span class="badge badge-success"><?php echo count($returnedBooks); ?> Total Returns</span>
            </div>
            
            <?php if (empty($returnedBooks)): ?>
            <div style="padding: 3rem; text-align: center;">
                <i class="fas fa-undo" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                <h3>No Returned Books</h3>
                <p style="color: var(--text-secondary);">You haven't returned any books yet</p>
            </div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Book Title</th>
                        <th>Author</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Return Status</th>
                        <th>Fine</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($returnedBooks as $borrow): ?>
                    <?php 
                    $returnedLate = $borrow['days_diff'] > 0;
                    ?>
                    <tr>
                        <td>
                            <?php if ($borrow['book_cover']): ?>
                            <img src="<?php echo SITE_URL . '/uploads/book_covers/' . $borrow['book_cover']; ?>" alt="Cover" style="width: 40px; height: 60px; object-fit: cover;">
                            <?php else: ?>
                            <div style="width: 40px; height: 60px; background: #ddd; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-book"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($borrow['book_title']); ?></td>
                        <td><?php echo htmlspecialchars($borrow['author']); ?></td>
                        <td><?php echo formatDate($borrow['borrow_date'], 'M d, Y'); ?></td>
                        <td><?php echo formatDate($borrow['due_date'], 'M d, Y'); ?></td>
                        <td><?php echo formatDate($borrow['return_date'], 'M d, Y'); ?></td>
                        <td>
                            <?php if ($returnedLate): ?>
                            <span class="badge badge-warning">Returned Late</span>
                            <?php else: ?>
                            <span class="badge badge-success">On Time</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($borrow['fine_amount'] > 0): ?>
                            <span class="badge badge-danger"><?php echo formatCurrency($borrow['fine_amount']); ?></span>
                            <?php else: ?>
                            <span class="badge badge-success">No Fine</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($returnedBooks)): ?>
        <div class="card" style="margin-top: 1.5rem;">
            <div class="card-header">
                <h3>Summary Statistics</h3>
            </div>
            <div style="padding: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <?php
                $totalReturns = count($returnedBooks);
                $onTimeReturns = count(array_filter($returnedBooks, function($b) { return $b['days_diff'] <= 0; }));
                $lateReturns = $totalReturns - $onTimeReturns;
                $totalFines = array_sum(array_column($returnedBooks, 'fine_amount'));
                ?>
                <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <h2 style="margin: 0; color: var(--success-color);"><?php echo $onTimeReturns; ?></h2>
                    <p style="margin: 0.5rem 0 0 0; color: var(--text-secondary);">On-Time Returns</p>
                </div>
                <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <h2 style="margin: 0; color: var(--warning-color);"><?php echo $lateReturns; ?></h2>
                    <p style="margin: 0.5rem 0 0 0; color: var(--text-secondary);">Late Returns</p>
                </div>
                <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <h2 style="margin: 0; color: var(--danger-color);"><?php echo formatCurrency($totalFines); ?></h2>
                    <p style="margin: 0.5rem 0 0 0; color: var(--text-secondary);">Total Fines Paid</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
