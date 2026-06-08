<?php
<<<<<<< HEAD
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

=======
require_once '../config/config.php';
requireRole('librarian');
$pageTitle = 'Borrow History';
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
<<<<<<< HEAD
    
=======
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Borrow History</h1>
        </div>
        
        <div class="card">
            <div class="card-header">
<<<<<<< HEAD
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
=======
                <h3 class="card-title">All Borrowing Records</h3>
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
                        ORDER BY br.created_at DESC
                        LIMIT 100
                    ");
                    $records = $stmt->fetchAll();
                ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Book</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                                <th>Fine</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $rec): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rec['student_name']); ?><br><small><?php echo $rec['student_id']; ?></small></td>
                                <td><?php echo htmlspecialchars($rec['book_title']); ?></td>
                                <td><?php echo formatDate($rec['borrow_date'], 'M d, Y'); ?></td>
                                <td><?php echo formatDate($rec['due_date'], 'M d, Y'); ?></td>
                                <td><?php echo $rec['return_date'] ? formatDate($rec['return_date'], 'M d, Y') : '-'; ?></td>
                                <td><span class="badge badge-<?php echo $rec['status'] == 'returned' ? 'success' : ($rec['status'] == 'overdue' ? 'danger' : 'warning'); ?>"><?php echo $rec['status']; ?></span></td>
                                <td><?php echo $rec['fine_amount'] > 0 ? formatCurrency($rec['fine_amount']) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php } catch (PDOException $e) { echo '<p>Error loading records.</p>'; } ?>
            </div>
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
