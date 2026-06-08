<?php
/**
 * Issue Book - Librarian
 * Library Management System
 */

require_once '../config/config.php';
requireRole('librarian');

$pageTitle = 'Issue Book';

<<<<<<< HEAD
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

=======
// Handle book issue
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = sanitizeInput($_POST['student_id']);
    $bookId = intval($_POST['book_id']);
    $dueDate = sanitizeInput($_POST['due_date']);
    
    try {
        $db = getDB();
        
        // Get student
        $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();
        
        if (!$student) {
            setErrorMessage('Student not found!');
            redirect(SITE_URL . '/librarian/issue-book.php');
        }
        
        // Check max books limit
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM borrow_records WHERE student_id = ? AND status IN ('borrowed', 'overdue')");
        $stmt->execute([$student['id']]);
        $currentBorrows = $stmt->fetch()['count'];
        
        if ($currentBorrows >= MAX_BOOKS_PER_STUDENT) {
            setErrorMessage("Student has reached maximum borrow limit (" . MAX_BOOKS_PER_STUDENT . " books)");
            redirect(SITE_URL . '/librarian/issue-book.php');
        }
        
        // Check if book is available
        $stmt = $db->prepare("SELECT * FROM books WHERE id = ? AND available_quantity > 0");
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();
        
        if (!$book) {
            setErrorMessage('Book is not available!');
            redirect(SITE_URL . '/librarian/issue-book.php');
        }
        
        // Check if student already borrowed this book
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM borrow_records WHERE student_id = ? AND book_id = ? AND status IN ('borrowed', 'overdue')");
        $stmt->execute([$student['id'], $bookId]);
        $alreadyBorrowed = $stmt->fetch()['count'];
        
        if ($alreadyBorrowed > 0) {
            setErrorMessage('Student already has this book borrowed!');
            redirect(SITE_URL . '/librarian/issue-book.php');
        }
        
        // Issue the book
        $stmt = $db->prepare("
            INSERT INTO borrow_records (student_id, book_id, borrow_date, due_date, status)
            VALUES (?, ?, CURDATE(), ?, 'borrowed')
        ");
        $stmt->execute([$student['id'], $bookId, $dueDate]);
        
        // Update book availability (handled by trigger, but we can do it explicitly too)
        $stmt = $db->prepare("UPDATE books SET available_quantity = available_quantity - 1 WHERE id = ?");
        $stmt->execute([$bookId]);
        
        // Create notification
        $message = "Book '{$book['book_title']}' has been issued to you. Due date: " . formatDate($dueDate, 'M d, Y');
        $stmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->execute([$student['user_id'], $message]);
        
        setSuccessMessage('Book issued successfully!');
        redirect(SITE_URL . '/librarian/issue-book.php');
        
    } catch (PDOException $e) {
        error_log("Issue book error: " . $e->getMessage());
        setErrorMessage('An error occurred. Please try again.');
        redirect(SITE_URL . '/librarian/issue-book.php');
    }
}

// Get available books
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT b.*, c.category_name 
        FROM books b
        INNER JOIN categories c ON b.category_id = c.id
        WHERE b.available_quantity > 0
        ORDER BY b.book_title
    ");
    $books = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch books error: " . $e->getMessage());
    $books = [];
}

// Default due date (14 days from now)
$defaultDueDate = date('Y-m-d', strtotime('+' . MAX_BORROW_DAYS . ' days'));

>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Issue Book</h1>
<<<<<<< HEAD
=======
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>/librarian/dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <span>/</span>
                <span>Issue Book</span>
            </div>
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
        </div>
        
        <?php if ($success = getSuccessMessage()): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $success; ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($error = getErrorMessage()): ?>
<<<<<<< HEAD
        <div class="alert alert-error">
=======
        <div class="alert alert-danger">
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
<<<<<<< HEAD
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
=======
                <h3 class="card-title">Issue Book to Student</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div style="max-width: 600px;">
                        <div class="form-group">
                            <label class="form-label required">Student ID</label>
                            <input type="text" name="student_id" class="form-control" 
                                   placeholder="Enter student ID (e.g., STU001)" required autofocus>
                            <small class="form-help">Enter the student's ID number</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required">Select Book</label>
                            <select name="book_id" class="form-select" required id="bookSelect">
                                <option value="">-- Select a Book --</option>
                                <?php foreach ($books as $book): ?>
                                <option value="<?php echo $book['id']; ?>" 
                                        data-title="<?php echo htmlspecialchars($book['book_title']); ?>"
                                        data-author="<?php echo htmlspecialchars($book['author']); ?>"
                                        data-isbn="<?php echo htmlspecialchars($book['isbn']); ?>"
                                        data-available="<?php echo $book['available_quantity']; ?>">
                                    <?php echo htmlspecialchars($book['book_title']); ?> 
                                    by <?php echo htmlspecialchars($book['author']); ?> 
                                    (ISBN: <?php echo $book['isbn']; ?>) 
                                    - Available: <?php echo $book['available_quantity']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="bookInfo" class="alert alert-info" style="display: none;">
                            <strong>Book Details:</strong><br>
                            <span id="bookDetails"></span>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required">Due Date</label>
                            <input type="date" name="due_date" class="form-control" 
                                   value="<?php echo $defaultDueDate; ?>" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                            <small class="form-help">Default: <?php echo MAX_BORROW_DAYS; ?> days from today</small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-hand-holding"></i> Issue Book
                            </button>
                            <a href="<?php echo SITE_URL; ?>/librarian/dashboard.php" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Recently Issued Books -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recently Issued Books</h3>
            </div>
            <div class="card-body">
                <?php
                try {
                    $stmt = $db->query("
                        SELECT br.*, s.student_id, s.full_name as student_name, 
                               b.book_title, b.author
                        FROM borrow_records br
                        INNER JOIN students s ON br.student_id = s.id
                        INNER JOIN books b ON br.book_id = b.id
                        WHERE DATE(br.created_at) = CURDATE()
                        ORDER BY br.created_at DESC
                        LIMIT 10
                    ");
                    $recentIssues = $stmt->fetchAll();
                    
                    if (empty($recentIssues)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No books issued today</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Book Title</th>
                                        <th>Author</th>
                                        <th>Issue Time</th>
                                        <th>Due Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentIssues as $issue): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($issue['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($issue['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($issue['book_title']); ?></td>
                                        <td><?php echo htmlspecialchars($issue['author']); ?></td>
                                        <td><?php echo formatDate($issue['created_at'], 'M d, Y h:i A'); ?></td>
                                        <td><?php echo formatDate($issue['due_date'], 'M d, Y'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif;
                } catch (PDOException $e) {
                    error_log("Recent issues error: " . $e->getMessage());
                }
                ?>
            </div>
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
        </div>
    </div>
</div>

<<<<<<< HEAD
=======
<script>
document.getElementById('bookSelect').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const bookInfo = document.getElementById('bookInfo');
    const bookDetails = document.getElementById('bookDetails');
    
    if (this.value) {
        const title = selected.dataset.title;
        const author = selected.dataset.author;
        const isbn = selected.dataset.isbn;
        const available = selected.dataset.available;
        
        bookDetails.innerHTML = `
            <strong>Title:</strong> ${title}<br>
            <strong>Author:</strong> ${author}<br>
            <strong>ISBN:</strong> ${isbn}<br>
            <strong>Available Copies:</strong> ${available}
        `;
        bookInfo.style.display = 'block';
    } else {
        bookInfo.style.display = 'none';
    }
});
</script>

>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
<?php include '../includes/footer.php'; ?>
