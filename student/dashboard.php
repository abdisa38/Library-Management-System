<?php
/**
 * Student Dashboard
 * Library Management System
 */

require_once '../config/config.php';
requireRole('student');

$pageTitle = 'Student Dashboard';
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
    
    // Currently Borrowed Books
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM borrow_records 
        WHERE student_id = ? AND status IN ('borrowed', 'overdue')
    ");
    $stmt->execute([$student['id']]);
    $currentlyBorrowed = $stmt->fetch()['total'];
    
    // Total Borrowed (All Time)
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM borrow_records WHERE student_id = ?");
    $stmt->execute([$student['id']]);
    $totalBorrowed = $stmt->fetch()['total'];
    
    // Overdue Books
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM borrow_records 
        WHERE student_id = ? AND status = 'borrowed' AND due_date < CURDATE()
    ");
    $stmt->execute([$student['id']]);
    $overdueBooks = $stmt->fetch()['total'];
    
    // Unpaid Fines
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(f.amount), 0) as total 
        FROM fines f
        INNER JOIN borrow_records br ON f.borrow_id = br.id
        WHERE br.student_id = ? AND f.paid_status = 'unpaid'
    ");
    $stmt->execute([$student['id']]);
    $unpaidFines = $stmt->fetch()['total'];
    
    // Recent Borrowed Books
    $stmt = $db->prepare("
        SELECT br.*, b.book_title, b.author, b.book_cover
        FROM borrow_records br
        INNER JOIN books b ON br.book_id = b.id
        WHERE br.student_id = ? AND br.status IN ('borrowed', 'overdue')
        ORDER BY br.borrow_date DESC
        LIMIT 5
    ");
    $stmt->execute([$student['id']]);
    $recentBorrows = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    setErrorMessage("Error loading dashboard data");
}

include '../includes/header.php';
include '../includes/sidebar_student.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Student Dashboard</h1>
            <div class="breadcrumb">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
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
        
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo number_format($currentlyBorrowed); ?></h3>
                        <p>Currently Borrowed</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-book-reader"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo number_format($totalBorrowed); ?></h3>
                        <p>Total Borrowed</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo number_format($overdueBooks); ?></h3>
                        <p>Overdue Books</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo formatCurrency($unpaidFines); ?></h3>
                        <p>Unpaid Fines</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">My Borrowed Books</h3>
                <a href="<?php echo SITE_URL; ?>/student/borrowed-books.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentBorrows)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Borrowed Books</h3>
                    <p>You haven't borrowed any books yet.</p>
                    <a href="<?php echo SITE_URL; ?>/student/available-books.php" class="btn btn-primary">Browse Books</a>
                </div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Author</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBorrows as $borrow): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($borrow['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($borrow['author']); ?></td>
                                <td><?php echo formatDate($borrow['borrow_date'], 'M d, Y'); ?></td>
                                <td><?php echo formatDate($borrow['due_date'], 'M d, Y'); ?></td>
                                <td>
                                    <?php
                                    $status = $borrow['status'];
                                    $badgeClass = $status === 'overdue' ? 'danger' : 'success';
                                    $daysLeft = daysBetween(date('Y-m-d'), $borrow['due_date']);
                                    ?>
                                    <span class="badge badge-<?php echo $badgeClass; ?>">
                                        <?php 
                                        if ($status === 'overdue') {
                                            echo 'Overdue';
                                        } else {
                                            echo $daysLeft . ' days left';
                                        }
                                        ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
