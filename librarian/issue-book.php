<?php
/**
 * Issue Book - Librarian
 * Library Management System
 */

require_once '../config/config.php';
requireRole('librarian');

$pageTitle = 'Issue Book';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'])) {
    try {
        $db = getDB();
        
        $studentId = $_POST['student_id'];
        $bookId = $_POST['book_id'];
        $borrowDays = (int)$_POST['borrow_days'];
        
        // Check if book is available
        $stmt = $db->prepare("SELECT available_quantity FROM books WHERE id = ?");
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();
        
        if (!$book || $book['available_quantity'] <= 0) {
            setErrorMessage('Book is not available');
        } else {
            // Check student's active borrows
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM borrow_records WHERE student_id = ? AND status IN ('borrowed', 'overdue')");
            $stmt->execute([$studentId]);
            $activeBorrows = $stmt->fetch()['count'];
            
            if ($activeBorrows >= MAX_BOOKS_PER_STUDENT) {
                setErrorMessage('Student has reached maximum borrow limit');
            } else {
                // Issue the book
                $borrowDate = date('Y-m-d');
                $dueDate = date('Y-m-d', strtotime("+$borrowDays days"));
                
                $stmt = $db->prepare("INSERT INTO borrow_records (student_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, 'borrowed')");
                $stmt->execute([$studentId, $bookId, $borrowDate, $dueDate]);
                
                setSuccessMessage('Book issued successfully!');
                redirect(SITE_URL . '/librarian/issue-book.php');
            }
        }
    } catch (PDOException $e) {
        error_log("Issue book error: " . $e->getMessage());
        setErrorMessage('Error issuing book');
    }
}

include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Issue Book</h1>
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
                <h3>Issue Book to Student</h3>
            </div>
            
            <form method="POST" style="padding: 1.5rem;">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label>Student ID *</label>
                    <input type="text" name="student_id" id="student_id" class="form-control" required>
                    <small>Enter student ID to search</small>
                </div>
                
                <div class="form-group">
                    <label>Book ISBN/Title *</label>
                    <input type="text" name="book_search" id="book_search" class="form-control" required>
                    <input type="hidden" name="book_id" id="book_id" required>
                    <small>Enter ISBN or book title to search</small>
                </div>
                
                <div class="form-group">
                    <label>Borrow Duration (Days) *</label>
                    <input type="number" name="borrow_days" class="form-control" value="<?php echo MAX_BORROW_DAYS; ?>" min="1" max="30" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Issue Book</button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
