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
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo number_format($totalStudents); ?></h3>
                        <p>Total Students</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo number_format($totalLibrarians); ?></h3>
                        <p>Total Librarians</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo number_format($borrowedBooks); ?></h3>
                        <p>Borrowed Books</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-book-reader"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo number_format($returnedBooks); ?></h3>
                        <p>Returned Books</p>
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
            
            <div class="stat-card danger">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo formatCurrency($totalFines); ?></h3>
                        <p>Total Fines</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card primary">
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
        </div>
        
        <!-- Charts Row -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Monthly Borrow Statistics</h3>
                </div>
                <div class="card-body">
                    <canvas id="borrowChart" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Recent Borrowing Activity -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Borrowing Activity</h3>
                <a href="<?php echo SITE_URL; ?>/admin/borrow-report.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentBorrows)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Borrowing Records</h3>
                    <p>There are no borrowing records to display yet.</p>
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
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBorrows as $borrow): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($borrow['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($borrow['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($borrow['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($borrow['author']); ?></td>
                                <td><?php echo formatDate($borrow['borrow_date'], 'M d, Y'); ?></td>
                                <td><?php echo formatDate($borrow['due_date'], 'M d, Y'); ?></td>
                                <td>
                                    <?php
                                    $status = $borrow['status'];
                                    $badgeClass = $status === 'returned' ? 'success' : 
                                                ($status === 'overdue' ? 'danger' : 'warning');
                                    ?>
                                    <span class="badge badge-<?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($status); ?>
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

<script>
// Monthly Borrow Chart
const borrowCtx = document.getElementById('borrowChart');
if (borrowCtx) {
    const monthlyData = <?php echo json_encode($monthlyBorrows); ?>;
    
    new Chart(borrowCtx, {
        type: 'line',
        data: {
            labels: monthlyData.map(item => item.month),
            datasets: [{
                label: 'Books Borrowed',
                data: monthlyData.map(item => item.count),
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
