<?php
/**
 * My Borrowed Books - Student
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
    
    // Get currently borrowed books
    $stmt = $db->prepare("
        SELECT br.*, b.book_title, b.author, b.isbn, b.book_cover
        FROM borrow_records br
        INNER JOIN books b ON br.book_id = b.id
        WHERE br.student_id = ? AND br.status IN ('borrowed', 'overdue')
        ORDER BY br.due_date ASC
    ");
    $stmt->execute([$student['id']]);
    $borrowedBooks = $stmt->fetchAll();
    
    // Get total fines
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(f.amount), 0) as total_fines
        FROM fines f
        INNER JOIN borrow_records br ON f.borrow_id = br.id
        WHERE br.student_id = ? AND f.paid_status = 'unpaid'
    ");
    $stmt->execute([$student['id']]);
    $totalFines = $stmt->fetch()['total_fines'];
    
} catch (PDOException $e) {
    error_log("Borrowed books error: " . $e->getMessage());
    $borrowedBooks = [];
    $totalFines = 0;
}

include '../includes/header.php';
include '../includes/sidebar_student.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">My Borrowed Books</h1>
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>/student/dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <span>/</span>
                <span>Borrowed Books</span>
            </div>
        </div>
        
        <?php if ($totalFines > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span>You have unpaid fines totaling <strong><?php echo formatCurrency($totalFines); ?></strong>. Please pay at the library.</span>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Currently Borrowed (<?php echo count($borrowedBooks); ?> / <?php echo MAX_BOOKS_PER_STUDENT; ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (empty($borrowedBooks)): ?>
                <div class="empty-state">
                    <i class="fas fa-book-reader"></i>
                    <h3>No Borrowed Books</h3>
                    <p>You haven't borrowed any books yet.</p>
                    <a href="<?php echo SITE_URL; ?>/student/available-books.php" class="btn btn-primary">
                        <i class="fas fa-search"></i> Browse Available Books
                    </a>
                </div>
                <?php else: ?>
                <div class="books-grid">
                    <?php foreach ($borrowedBooks as $book): 
                        $today = date('Y-m-d');
                        $isOverdue = $today > $book['due_date'];
                        $daysLeft = $isOverdue ? 0 : daysBetween($today, $book['due_date']);
                        $overdueDays = $isOverdue ? calculateOverdueDays($book['due_date']) : 0;
                    ?>
                    <div class="book-card" style="<?php echo $isOverdue ? 'border: 2px solid var(--danger-color);' : ''; ?>">
                        <?php if ($book['book_cover']): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/book_covers/<?php echo $book['book_cover']; ?>" 
                                 alt="<?php echo htmlspecialchars($book['book_title']); ?>" 
                                 class="book-cover">
                        <?php else: ?>
                            <div class="book-cover" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-book" style="font-size: 3rem; color: white; opacity: 0.7;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="book-info">
                            <div class="book-title"><?php echo htmlspecialchars($book['book_title']); ?></div>
                            <div class="book-author">by <?php echo htmlspecialchars($book['author']); ?></div>
                            
                            <div style="margin: 0.75rem 0; font-size: 0.9rem; color: var(--text-secondary);">
                                <div style="margin-bottom: 0.5rem;">
                                    <i class="fas fa-calendar"></i>
                                    Borrowed: <?php echo formatDate($book['borrow_date'], 'M d, Y'); ?>
                                </div>
                                <div style="margin-bottom: 0.5rem;">
                                    <i class="fas fa-calendar-check"></i>
                                    Due: <?php echo formatDate($book['due_date'], 'M d, Y'); ?>
                                </div>
                            </div>
                            
                            <div class="book-meta">
                                <?php if ($isOverdue): ?>
                                    <span class="badge badge-danger">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Overdue (<?php echo $overdueDays; ?> days)
                                    </span>
                                    <span class="badge badge-warning">
                                        Fine: <?php echo formatCurrency(calculateFine($overdueDays)); ?>
                                    </span>
                                <?php elseif ($daysLeft <= 3): ?>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-clock"></i>
                                        Due in <?php echo $daysLeft; ?> day<?php echo $daysLeft != 1 ? 's' : ''; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check"></i>
                                        <?php echo $daysLeft; ?> days left
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($isOverdue): ?>
                            <div class="alert alert-danger" style="margin-top: 0.75rem; padding: 0.5rem; font-size: 0.85rem;">
                                <i class="fas fa-exclamation-circle"></i>
                                Please return this book immediately to avoid additional fines!
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="alert alert-info" style="margin-top: 1.5rem;">
                    <i class="fas fa-info-circle"></i>
                    <span>To return a book, please visit the library and hand it to the librarian.</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
