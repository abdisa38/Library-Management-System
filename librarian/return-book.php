<?php
/**
 * Return Book - Librarian
 * Library Management System
 */

require_once '../config/config.php';
requireRole('librarian');

$pageTitle = 'Return Book';

// Handle book return
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_id'])) {
    $borrowId = intval($_POST['borrow_id']);
    
    try {
        $db = getDB();
        
        // Get borrow record
        $stmt = $db->prepare("
            SELECT br.*, b.book_title, s.user_id
            FROM borrow_records br
            INNER JOIN books b ON br.book_id = b.id
            INNER JOIN students s ON br.student_id = s.id
            WHERE br.id = ?
        ");
        $stmt->execute([$borrowId]);
        $borrow = $stmt->fetch();
        
        if (!$borrow) {
            setErrorMessage('Borrow record not found!');
            redirect(SITE_URL . '/librarian/return-book.php');
        }
        
        // Calculate fine if overdue
        $returnDate = date('Y-m-d');
        $dueDate = $borrow['due_date'];
        $overdueDays = 0;
        $fineAmount = 0;
        
        if ($returnDate > $dueDate) {
            $overdueDays = calculateOverdueDays($dueDate);
            $fineAmount = calculateFine($overdueDays);
        }
        
        // Update borrow record
        $stmt = $db->prepare("
            UPDATE borrow_records 
            SET return_date = ?, status = 'returned', fine_amount = ?
            WHERE id = ?
        ");
        $stmt->execute([$returnDate, $fineAmount, $borrowId]);
        
        // Update book availability
        $stmt = $db->prepare("UPDATE books SET available_quantity = available_quantity + 1 WHERE id = ?");
        $stmt->execute([$borrow['book_id']]);
        
        // Create fine record if applicable
        if ($fineAmount > 0) {
            $reason = "Overdue return - $overdueDays day(s) late";
            $stmt = $db->prepare("INSERT INTO fines (borrow_id, amount, reason, paid_status) VALUES (?, ?, ?, 'unpaid')");
            $stmt->execute([$borrowId, $fineAmount, $reason]);
        }
        
        // Create notification
        $message = "Book '{$borrow['book_title']}' has been returned.";
        if ($fineAmount > 0) {
            $message .= " Fine: " . formatCurrency($fineAmount) . " ($overdueDays days overdue)";
        }
        $stmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->execute([$borrow['user_id'], $message]);
        
        if ($fineAmount > 0) {
            setSuccessMessage("Book returned successfully! Fine of " . formatCurrency($fineAmount) . " applied for $overdueDays overdue days.");
        } else {
            setSuccessMessage('Book returned successfully!');
        }
        
        redirect(SITE_URL . '/librarian/return-book.php');
        
    } catch (PDOException $e) {
        error_log("Return book error: " . $e->getMessage());
        setErrorMessage('An error occurred. Please try again.');
        redirect(SITE_URL . '/librarian/return-book.php');
    }
}

// Search for borrowed books
$search = $_GET['search'] ?? '';
$borrowedBooks = [];

if ($search) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT br.*, 
                   s.student_id, s.full_name as student_name, s.email,
                   b.book_title, b.author, b.isbn
            FROM borrow_records br
            INNER JOIN students s ON br.student_id = s.id
            INNER JOIN books b ON br.book_id = b.id
            WHERE br.status IN ('borrowed', 'overdue')
            AND (s.student_id LIKE ? OR b.isbn LIKE ? OR b.book_title LIKE ?)
            ORDER BY br.due_date ASC
        ");
        $searchTerm = "%$search%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $borrowedBooks = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Search error: " . $e->getMessage());
    }
}

include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Return Book</h1>
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>/librarian/dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <span>/</span>
                <span>Return Book</span>
            </div>
        </div>
        
        <?php if ($success = getSuccessMessage()): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $success; ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($error = getErrorMessage()): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Search Borrowed Books</h3>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="d-flex gap-2" style="max-width: 600px;">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Enter Student ID, Book ISBN, or Book Title..."
                               value="<?php echo htmlspecialchars($search); ?>" required autofocus>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($search): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Search Results</h3>
            </div>
            <div class="card-body">
                <?php if (empty($borrowedBooks)): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>No Borrowed Books Found</h3>
                    <p>No active borrowed books match your search criteria.</p>
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
                                <th>ISBN</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Fine</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrowedBooks as $book): 
                                $today = date('Y-m-d');
                                $isOverdue = $today > $book['due_date'];
                                $overdueDays = $isOverdue ? calculateOverdueDays($book['due_date']) : 0;
                                $potentialFine = $overdueDays > 0 ? calculateFine($overdueDays) : 0;
                            ?>
                            <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                                <td><strong><?php echo htmlspecialchars($book['student_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($book['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($book['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                <td><?php echo formatDate($book['borrow_date'], 'M d, Y'); ?></td>
                                <td><?php echo formatDate($book['due_date'], 'M d, Y'); ?></td>
                                <td>
                                    <?php if ($isOverdue): ?>
                                        <span class="badge badge-danger">
                                            Overdue (<?php echo $overdueDays; ?> days)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success">On Time</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($potentialFine > 0): ?>
                                        <span class="badge badge-warning">
                                            <?php echo formatCurrency($potentialFine); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success">$0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="borrow_id" value="<?php echo $book['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success" 
                                                onclick="return confirm('Process return for this book?<?php echo $potentialFine > 0 ? ' Fine: ' . formatCurrency($potentialFine) : ''; ?>')">
                                            <i class="fas fa-undo"></i> Return
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recently Returned Books -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recently Returned Books (Today)</h3>
            </div>
            <div class="card-body">
                <?php
                try {
                    $db = getDB();
                    $stmt = $db->query("
                        SELECT br.*, s.student_id, s.full_name as student_name, 
                               b.book_title, b.author
                        FROM borrow_records br
                        INNER JOIN students s ON br.student_id = s.id
                        INNER JOIN books b ON br.book_id = b.id
                        WHERE br.status = 'returned' AND DATE(br.return_date) = CURDATE()
                        ORDER BY br.return_date DESC
                        LIMIT 10
                    ");
                    $recentReturns = $stmt->fetchAll();
                    
                    if (empty($recentReturns)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No books returned today</p>
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
                                        <th>Return Time</th>
                                        <th>Fine</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentReturns as $return): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($return['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($return['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($return['book_title']); ?></td>
                                        <td><?php echo htmlspecialchars($return['author']); ?></td>
                                        <td><?php echo formatDate($return['return_date'], 'M d, Y h:i A'); ?></td>
                                        <td>
                                            <?php if ($return['fine_amount'] > 0): ?>
                                                <span class="badge badge-warning">
                                                    <?php echo formatCurrency($return['fine_amount']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-success">$0.00</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif;
                } catch (PDOException $e) {
                    error_log("Recent returns error: " . $e->getMessage());
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
