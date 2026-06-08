<?php
/**
 * Manage Students - Super Admin
 * Library Management System
 */

require_once '../config/config.php';
requireRole('super_admin');

$pageTitle = 'Manage Students';

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $db = getDB();
        
        if ($action === 'add') {
            $studentId = sanitizeInput($_POST['student_id']);
            $fullName = sanitizeInput($_POST['full_name']);
            $email = sanitizeInput($_POST['email']);
            $department = sanitizeInput($_POST['department']);
            $year = sanitizeInput($_POST['year']);
            $phone = sanitizeInput($_POST['phone']);
            $username = sanitizeInput($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            // Create user account
            $stmt = $db->prepare("
                INSERT INTO users (full_name, email, username, password, role, status)
                VALUES (?, ?, ?, ?, 'student', 'active')
            ");
            $stmt->execute([$fullName, $email, $username, $password]);
            $userId = $db->lastInsertId();
            
            // Create student record
            $stmt = $db->prepare("
                INSERT INTO students (user_id, student_id, full_name, department, year, phone, email)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $studentId, $fullName, $department, $year, $phone, $email]);
            
            setSuccessMessage('Student added successfully!');
            redirect(SITE_URL . '/admin/students.php');
            
        } elseif ($action === 'edit') {
            $id = intval($_POST['id']);
            $studentId = sanitizeInput($_POST['student_id']);
            $fullName = sanitizeInput($_POST['full_name']);
            $email = sanitizeInput($_POST['email']);
            $department = sanitizeInput($_POST['department']);
            $year = sanitizeInput($_POST['year']);
            $phone = sanitizeInput($_POST['phone']);
            
            $stmt = $db->prepare("
                UPDATE students 
                SET student_id = ?, full_name = ?, email = ?, department = ?, year = ?, phone = ?
                WHERE id = ?
            ");
            $stmt->execute([$studentId, $fullName, $email, $department, $year, $phone, $id]);
            
            // Update user account
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = (SELECT user_id FROM students WHERE id = ?)");
            $stmt->execute([$fullName, $email, $id]);
            
            setSuccessMessage('Student updated successfully!');
            redirect(SITE_URL . '/admin/students.php');
            
        } elseif ($action === 'delete') {
            $id = intval($_POST['id']);
            
            // Check if student has active borrows
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM borrow_records WHERE student_id = ? AND status IN ('borrowed', 'overdue')");
            $stmt->execute([$id]);
            $activeBorrows = $stmt->fetch()['count'];
            
            if ($activeBorrows > 0) {
                setErrorMessage('Cannot delete student. They have active borrowed books.');
            } else {
                // Get user_id
                $stmt = $db->prepare("SELECT user_id FROM students WHERE id = ?");
                $stmt->execute([$id]);
                $userId = $stmt->fetch()['user_id'];
                
                // Delete student and user
                $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
                $stmt->execute([$id]);
                
                if ($userId) {
                    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                }
                
                setSuccessMessage('Student deleted successfully!');
            }
            redirect(SITE_URL . '/admin/students.php');
        }
    } catch (PDOException $e) {
        error_log("Students error: " . $e->getMessage());
        setErrorMessage('An error occurred. Please try again.');
        redirect(SITE_URL . '/admin/students.php');
    }
}

// Fetch students
$search = $_GET['search'] ?? '';

try {
    $db = getDB();
    
    $sql = "SELECT s.*, 
            (SELECT COUNT(*) FROM borrow_records WHERE student_id = s.id AND status IN ('borrowed', 'overdue')) as active_borrows
            FROM students s WHERE 1=1";
    $params = [];
    
    if ($search) {
        $sql .= " AND (s.student_id LIKE ? OR s.full_name LIKE ? OR s.email LIKE ?)";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm];
    }
    
    $sql .= " ORDER BY s.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Fetch students error: " . $e->getMessage());
    $students = [];
}

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Manage Students</h1>
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <span>/</span>
                <span>Students</span>
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
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Students</h3>
                <button class="btn btn-primary" onclick="openModal('addStudentModal')">
                    <i class="fas fa-plus"></i> Add Student
                </button>
            </div>
            <div class="card-body">
                <form method="GET" class="mb-2">
                    <div class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" placeholder="Search by student ID, name, or email..." 
                               value="<?php echo htmlspecialchars($search); ?>" style="max-width: 400px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="<?php echo SITE_URL; ?>/admin/students.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
                
                <?php if (empty($students)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-graduate"></i>
                    <h3>No Students Found</h3>
                    <p>Start by adding your first student.</p>
                </div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Year</th>
                                <th>Phone</th>
                                <th>Active Borrows</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['student_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['department']); ?></td>
                                <td><?php echo htmlspecialchars($student['year']); ?></td>
                                <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $student['active_borrows'] > 0 ? 'warning' : 'success'; ?>">
                                        <?php echo $student['active_borrows']; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick='editStudent(<?php echo json_encode($student); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['full_name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
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

<!-- Add Student Modal -->
<div id="addStudentModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2 class="modal-title">Add New Student</h2>
            <button class="modal-close" onclick="closeModal('addStudentModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label required">Student ID</label>
                        <input type="text" name="student_id" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-select">
                            <option value="">Select Year</option>
                            <option value="Year 1">Year 1</option>
                            <option value="Year 2">Year 2</option>
                            <option value="Year 3">Year 3</option>
                            <option value="Year 4">Year 4</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addStudentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Student
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Student Modal -->
<div id="editStudentModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2 class="modal-title">Edit Student</h2>
            <button class="modal-close" onclick="closeModal('editStudentModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label required">Student ID</label>
                        <input type="text" name="student_id" id="edit_student_id" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Full Name</label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" id="edit_department" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Year</label>
                        <select name="year" id="edit_year" class="form-select">
                            <option value="">Select Year</option>
                            <option value="Year 1">Year 1</option>
                            <option value="Year 2">Year 2</option>
                            <option value="Year 3">Year 3</option>
                            <option value="Year 4">Year 4</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editStudentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Student
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
function editStudent(student) {
    document.getElementById('edit_id').value = student.id;
    document.getElementById('edit_student_id').value = student.student_id;
    document.getElementById('edit_full_name').value = student.full_name;
    document.getElementById('edit_email').value = student.email;
    document.getElementById('edit_department').value = student.department || '';
    document.getElementById('edit_year').value = student.year || '';
    document.getElementById('edit_phone').value = student.phone || '';
    openModal('editStudentModal');
}

function deleteStudent(id, name) {
    if (confirm('Are you sure you want to delete "' + name + '"?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
