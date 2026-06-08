<?php
/**
 * Super Admin Dashboard
 * Library Management System
 */

require_once '../config/config.php';
requireRole('super_admin');

$pageTitle = 'Admin Dashboard';
$currentUser = getCurrentUser();

// Fetch dashboard statistics
try {
    $db = getDB();
    
    // Total Books
    $stmt = $db->query("SELECT COUNT(*) as total FROM books");
    $totalBooks = $stmt->fetch()['total'];
    
    // Total Students
    $stmt = $db->query("SELECT COUNT(*) as total FROM students");
    $totalStudents = $stmt->fetch()['total'];
    
    // Total Librarians
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'librarian' AND status = 'active'");
    $totalLibrarians = $stmt->fetch()['total'];
    
    // Borrowed Books
    $stmt = $db->query("SELECT COUNT(*) as total FROM borrow_records WHERE status IN ('borrowed', 'overdue')");
    $borrowedBooks = $stmt->fetch()['total'];
    
    // Returned Books
    $stmt = $db->query("SELECT COUNT(*) as total FROM borrow_records WHERE status = 'returned'");
    $returnedBooks = $stmt->fetch()['total'];
    
    // Overdue Books
    $stmt = $db->query("SELECT COUNT(*) as total FROM borrow_records WHERE status = 'borrowed' AND due_date < CURDATE()");
    $overdueBooks = $stmt->fetch()['total'];
    
    // Total Fines
    $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM fines WHERE paid_status = 'unpaid'");
    $totalFines = $stmt->fetch()['total'];
    
    // Available Books
    $stmt = $db->query("SELECT SUM(available_quantity) as total FROM books");
    $availableBooks = $stmt->fetch()['total'];
    
    // Recent Borrowing
    $stmt = $db->query("
        SELECT br.*, s.student_id, s.full_name as student_name, b.book_title, b.author
        FROM borrow_records br
        INNER JOIN students s ON br.student_id = s.id
        INNER JOIN books b ON br.book_id = b.id
        ORDER BY br.created_at DESC
        LIMIT 10
    ");
    $recentBorrows = $stmt->fetchAll();
    
    // Monthly Statistics for Chart
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(borrow_date, '%Y-%m') as month,
            COUNT(*) as count
        FROM borrow_records
        WHERE borrow_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(borrow_date, '%Y-%m')
        ORDER BY month
    ");
    $monthlyBorrows = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    setErrorMessage("Error loading dashboard data");
}

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Dashboard</h1>
            <div class="breadcrumb">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
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
        
        <!-- Statistics Cards -->
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
