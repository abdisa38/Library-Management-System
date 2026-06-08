<?php
/**
 * Borrowed Books - Student
 * Library Management System
 */

require_once '../config/config.php';
requireRole('student');

$pageTitle = 'My Borrowed Books';
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
    
    // Get borrowed books
    $stmt = $db->prepare("
        SELECT br.*, b.book_title, b.author, b.isbn, b.book_cover,
        DATEDIFF(br.due_date, CURDATE()) as days_left
        FROM borrow_records br
        INNER JOIN books b ON br.book_id = b.id
        WHERE br.student_id = ? AND br.status IN ('borrowed', 'overdue')
        ORDER BY br.due_date ASC
    ");
    $stmt->execute([$student['id']]);
    $borrowedBooks = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Borrowed books fetch error: " . $e->getMessage());
    setErrorMessage("Error loading borrowed books");
}

include '../includes/header.php';
include '../includes/sidebar_student.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">My Borrowed Books</h1>
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
                <h3>Currently Borrowed Books</h3>
                <span class="badge badge-primary"><?php echo count($borrowedBooks); ?> / <?php echo MAX_BOOKS_PER_STUDENT; ?> Books</span>
            </div>
            
            <?php if (empty($borrowedBooks)): ?>
            <div style="padding: 3rem; text-align: center;">
                <i class="fas fa-book-reader" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                <h3>No Borrowed Books</h3>
                <p style="color: var(--text-secondary); margin-bottom: 1rem;">You haven't borrowed any books yet</p>
                <a href="<?php echo SITE_URL; ?>/student/available-books.php" class="btn btn-primary">Browse Available Books</a>
            </div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Book Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Fine</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($borrowedBooks as $borrow): ?>
                    <?php 
                    $isOverdue = $borrow['days_left'] < 0;
                    $overdueDays = abs(min(0, $borrow['days_left']));
                    $fine = $isOverdue ? calculateFine($overdueDays) : 0;
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
                        <td><?php echo htmlspecialchars($borrow['isbn']); ?></td>
                        <td><?php echo formatDate($borrow['borrow_date'], 'M d, Y'); ?></td>
                        <td><?php echo formatDate($borrow['due_date'], 'M d, Y'); ?></td>
                        <td>
                            <?php if ($isOverdue): ?>
                            <span class="badge badge-danger">Overdue (<?php echo $overdueDays; ?> days)</span>
                            <?php elseif ($borrow['days_left'] <= 3): ?>
                            <span class="badge badge-warning">Due Soon (<?php echo $borrow['days_left']; ?> days left)</span>
                            <?php else: ?>
                            <span class="badge badge-success"><?php echo $borrow['days_left']; ?> days left</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($fine > 0): ?>
                            <span class="badge badge-danger"><?php echo formatCurrency($fine); ?></span>
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
        
        <?php if (!empty($borrowedBooks)): ?>
        <div class="alert alert-info" style="margin-top: 1rem;">
            <i class="fas fa-info-circle"></i>
            <span><strong>Note:</strong> Please return books on or before the due date to avoid fines. Fine rate: <?php echo formatCurrency(FINE_PER_DAY); ?> per day.</span>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
