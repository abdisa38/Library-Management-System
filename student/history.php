<?php
/**
 * Borrow History - Student
 * Library Management System
 */

require_once '../config/config.php';
requireRole('student');

$pageTitle = 'Borrow History';
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
    
    // Get borrow history
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
    
    $whereConditions = ["br.student_id = ?"];
    $params = [$student['id']];
    
    if ($statusFilter) {
        $whereConditions[] = "br.status = ?";
        $params[] = $statusFilter;
    }
    
    $whereClause = implode(" AND ", $whereConditions);
    
    $stmt = $db->prepare("
        SELECT br.*, b.book_title, b.author, b.isbn, b.book_cover
        FROM borrow_records br
        INNER JOIN books b ON br.book_id = b.id
        WHERE $whereClause
        ORDER BY br.created_at DESC
    ");
    $stmt->execute($params);
    $history = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("History fetch error: " . $e->getMessage());
    setErrorMessage("Error loading history");
}

include '../includes/header.php';
include '../includes/sidebar_student.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Borrow History</h1>
        </div>
        
        <?php if ($error = getErrorMessage()): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>All Transactions</h3>
                <form method="GET" style="display: flex; gap: 0.5rem;">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="borrowed" <?php echo $statusFilter == 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                        <option value="returned" <?php echo $statusFilter == 'returned' ? 'selected' : ''; ?>>Returned</option>
                        <option value="overdue" <?php echo $statusFilter == 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </div>
            
            <?php if (empty($history)): ?>
            <div style="padding: 3rem; text-align: center;">
                <i class="fas fa-history" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                <h3>No History Found</h3>
                <p style="color: var(--text-secondary);">You don't have any borrow history yet</p>
            </div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Book Title</th>
                        <th>Author</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Fine</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $record): ?>
                    <tr>
                        <td>
                            <?php if ($record['book_cover']): ?>
                            <img src="<?php echo SITE_URL . '/uploads/book_covers/' . $record['book_cover']; ?>" alt="Cover" style="width: 40px; height: 60px; object-fit: cover;">
                            <?php else: ?>
                            <div style="width: 40px; height: 60px; background: #ddd; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-book"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($record['book_title']); ?></td>
                        <td><?php echo htmlspecialchars($record['author']); ?></td>
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
                        <td>
                            <?php if ($record['fine_amount'] > 0): ?>
                            <span class="badge badge-danger"><?php echo formatCurrency($record['fine_amount']); ?></span>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
