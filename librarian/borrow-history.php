<?php
/**
 * Borrow History - Librarian
 * Library Management System
 */

require_once '../config/config.php';
requireRole('librarian');

$pageTitle = 'Borrow History';

// Fetch borrow history
$search = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

try {
    $db = getDB();
    
    $whereConditions = [];
    $params = [];
    
    if ($search) {
        $whereConditions[] = "(s.student_id LIKE ? OR s.full_name LIKE ? OR b.book_title LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($statusFilter) {
        $whereConditions[] = "br.status = ?";
        $params[] = $statusFilter;
    }
    
    $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    $stmt = $db->prepare("SELECT br.*, s.student_id, s.full_name as student_name, b.book_title, b.isbn, b.author
                          FROM borrow_records br
                          INNER JOIN students s ON br.student_id = s.id
                          INNER JOIN books b ON br.book_id = b.id
                          $whereClause
                          ORDER BY br.created_at DESC
                          LIMIT 100");
    $stmt->execute($params);
    $history = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Borrow history error: " . $e->getMessage());
}

include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Borrow History</h1>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>All Transactions</h3>
                <form method="GET" style="display: flex; gap: 0.5rem;">
                    <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="borrowed" <?php echo $statusFilter == 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                        <option value="returned" <?php echo $statusFilter == 'returned' ? 'selected' : ''; ?>>Returned</option>
                        <option value="overdue" <?php echo $statusFilter == 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Book Title</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Fine</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No records found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($history as $record): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($record['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($record['book_title']); ?></td>
                        <td><?php echo formatDate($record['borrow_date'], 'M d, Y'); ?></td>
                        <td><?php echo formatDate($record['due_date'], 'M d, Y'); ?></td>
                        <td><?php echo $record['return_date'] ? formatDate($record['return_date'], 'M d, Y') : '-'; ?></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $record['status'] == 'returned' ? 'success' : 
                                    ($record['status'] == 'overdue' ? 'danger' : 'warning'); 
                            ?>">
                                <?php echo ucfirst($record['status']); ?>
                            </span>
                        </td>
                        <td><?php echo $record['fine_amount'] > 0 ? formatCurrency($record['fine_amount']) : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
