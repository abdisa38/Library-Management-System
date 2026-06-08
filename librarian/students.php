<?php
require_once '../config/config.php';
requireRole('librarian');
$pageTitle = 'Students';
include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Students</h1>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Students</h3>
            </div>
            <div class="card-body">
                <?php
                try {
                    $db = getDB();
                    $stmt = $db->query("
                        SELECT s.*, 
                        (SELECT COUNT(*) FROM borrow_records WHERE student_id = s.id AND status IN ('borrowed', 'overdue')) as active_borrows
                        FROM students s ORDER BY s.full_name
                    ");
                    $students = $stmt->fetchAll();
                ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Year</th>
                                <th>Phone</th>
                                <th>Active Borrows</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($s['student_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                <td><?php echo htmlspecialchars($s['department']); ?></td>
                                <td><?php echo htmlspecialchars($s['year']); ?></td>
                                <td><?php echo htmlspecialchars($s['phone']); ?></td>
                                <td><span class="badge badge-<?php echo $s['active_borrows'] > 0 ? 'warning' : 'success'; ?>"><?php echo $s['active_borrows']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php } catch (PDOException $e) { echo '<p>Error loading students.</p>'; } ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
