<?php
/**
 * Librarian Dashboard
 * Library Management System
 */

require_once '../config/config.php';
requireRole('librarian');

$pageTitle = 'Librarian Dashboard';
$currentUser = getCurrentUser();

// Fetch dashboard statistics
try {
    $db = getDB();
    
    // Total Books
    $stmt = $db->query("SELECT COUNT(*) as total FROM books");
    $totalBooks = $stmt->fetch()['total'];
    
    // Available Books
    $stmt = $db->query("SELECT SUM(available_quantity) as total FROM books");
    $availableBooks = $stmt->fetch()['total'];
    
    // Borrowed Books
    $stmt = $db->query("SELECT COUNT(*) as total FROM borrow_records WHERE status IN ('borrowed', 'overdue')");
    $borrowedBooks = $stmt->fetch()['total'];
    
    // Returned Books Today
    $stmt = $db->query("SELECT COUNT(*) as total FROM borrow_records WHERE status = 'returned' AND DATE(return_date) = CURDATE()");
    $returnedToday = $stmt->fetch()['total'];
    
    // Overdue Books
    $stmt = $db->query("SELECT COUNT(*) as total FROM borrow_records WHERE status = 'borrowed' AND due_date < CURDATE()");
    $overdueBooks = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    setErrorMessage("Error loading dashboard data");
}

include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Librarian Dashboard</h1>
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
        
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo number_format($totalBooks); ?></h3>
                        <p>Total Books</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo number_format($availableBooks); ?></h3>
                        <p>Available Books</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo number_format($borrowedBooks); ?></h3>
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
                        <h3><?php echo number_format($returnedToday); ?></h3>
                        <p>Returned Today</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-undo"></i>
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
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            <a href="<?php echo SITE_URL; ?>/librarian/issue-book.php" class="card" style="text-align: center; padding: 2rem;">
                <i class="fas fa-hand-holding" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h3>Issue Book</h3>
                <p style="color: var(--text-secondary);">Issue books to students</p>
            </a>
            
            <a href="<?php echo SITE_URL; ?>/librarian/return-book.php" class="card" style="text-align: center; padding: 2rem;">
                <i class="fas fa-undo" style="font-size: 3rem; color: var(--success-color); margin-bottom: 1rem;"></i>
                <h3>Return Book</h3>
                <p style="color: var(--text-secondary);">Process book returns</p>
            </a>
            
            <a href="<?php echo SITE_URL; ?>/librarian/books.php" class="card" style="text-align: center; padding: 2rem;">
                <i class="fas fa-book" style="font-size: 3rem; color: var(--info-color); margin-bottom: 1rem;"></i>
                <h3>Manage Books</h3>
                <p style="color: var(--text-secondary);">Add and manage books</p>
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
