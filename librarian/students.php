<?php
/**
 * Manage Students - Librarian
 * Library Management System
 */

require_once '../config/config.php';
requireRole('librarian');

$pageTitle = 'Manage Students';

// Fetch students
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    $db = getDB();
    
    $whereClause = '';
    $params = [];
    
    if ($search) {
        $whereClause = "WHERE s.student_id LIKE ? OR s.full_name LIKE ? OR s.email LIKE ? OR s.department LIKE ?";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }
    
    $stmt = $db->prepare("SELECT s.*, 
                          (SELECT COUNT(*) FROM borrow_records WHERE student_id = s.id AND status IN ('borrowed', 'overdue')) as active_borrows
                          FROM students s 
                          $whereClause 
                          ORDER BY s.created_at DESC");
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Students fetch error: " . $e->getMessage());
    setErrorMessage("Error loading students");
}

include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Manage Students</h1>
        </div>
        
        <?php if ($error = getErrorMessage()): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Students List</h3>
                <form method="GET" style="display: flex; gap: 0.5rem;">
                    <input type="text" name="search" placeholder="Search students..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>Phone</th>
                        <th>Active Borrows</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No students found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['department']); ?></td>
                        <td><?php echo htmlspecialchars($student['year']); ?></td>
                        <td><?php echo htmlspecialchars($student['phone']); ?></td>
                        <td>
                            <span class="badge <?php echo $student['active_borrows'] > 0 ? 'badge-warning' : 'badge-success'; ?>">
                                <?php echo $student['active_borrows']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
