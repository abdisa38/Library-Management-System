<?php
require_once '../config/config.php';
requireRole('student');
$pageTitle = 'Borrow History';
$currentUser = getCurrentUser();

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->execute([$currentUser['id']]);
    $student = $stmt->fetch();
    
    $stmt = $db->prepare("
        SELECT br.*, b.book_title, b.author, b.isbn
        FROM borrow_records br
        INNER JOIN books b ON br.book_id = b.id
        WHERE br.student_id = ?
        ORDER BY br.created_at DESC
    ");
    $stmt->execute([$student['id']]);
    $history = $stmt->fetchAll();
} catch (PDOException $e) {
    $history = [];
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
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">My Borrowing History</h3>
            </div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h3>No History</h3>
                    <p>You haven't borrowed any books yet.</p>
                </div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>Author</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                                <th>Fine</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($h['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($h['author']); ?></td>
                                <td><?php echo formatDate($h['borrow_date'], 'M d, Y'); ?></td>
                                <td><?php echo formatDate($h['due_date'], 'M d, Y'); ?></td>
                                <td><?php echo $h['return_date'] ? formatDate($h['return_date'], 'M d, Y') : '-'; ?></td>
                                <td><span class="badge badge-<?php echo $h['status'] == 'returned' ? 'success' : ($h['status'] == 'overdue' ? 'danger' : 'warning'); ?>"><?php echo $h['status']; ?></span></td>
                                <td><?php echo $h['fine_amount'] > 0 ? formatCurrency($h['fine_amount']) : '-'; ?></td>
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
