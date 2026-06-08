<?php
/**
 * Manage Librarians - Admin
 * Library Management System
 */

require_once '../config/config.php';
requireRole('super_admin');

$pageTitle = 'Manage Librarians';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && validateCSRFToken($_POST['csrf_token'])) {
        $action = $_POST['action'];
        
        try {
            $db = getDB();
            
            // Add new librarian
            if ($action === 'add') {
                $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("INSERT INTO users (full_name, email, username, password, role, status) VALUES (?, ?, ?, ?, 'librarian', ?)");
                $stmt->execute([
                    $_POST['full_name'],
                    $_POST['email'],
                    $_POST['username'],
                    $hashedPassword,
                    $_POST['status']
                ]);
                
                setSuccessMessage('Librarian added successfully!');
                redirect(SITE_URL . '/admin/librarians.php');
            }
            
            // Update librarian
            if ($action === 'update') {
                if (!empty($_POST['password'])) {
                    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, username = ?, password = ?, status = ? WHERE id = ? AND role = 'librarian'");
                    $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['username'], $hashedPassword, $_POST['status'], $_POST['user_id']]);
                } else {
                    $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, username = ?, status = ? WHERE id = ? AND role = 'librarian'");
                    $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['username'], $_POST['status'], $_POST['user_id']]);
                }
                
                setSuccessMessage('Librarian updated successfully!');
                redirect(SITE_URL . '/admin/librarians.php');
            }
            
            // Delete librarian
            if ($action === 'delete') {
                $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'librarian'");
                $stmt->execute([$_POST['user_id']]);
                
                setSuccessMessage('Librarian deleted successfully!');
                redirect(SITE_URL . '/admin/librarians.php');
            }
            
        } catch (PDOException $e) {
            error_log("Librarian operation error: " . $e->getMessage());
            if ($e->getCode() == 23000) {
                setErrorMessage('Email or username already exists');
            } else {
                setErrorMessage('Error processing request');
            }
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Manage Librarians</h1>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New Librarian
            </button>
        </div>
        
        <?php if ($success = getSuccessMessage()): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $success; ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($error = getErrorMessage()): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>All Librarians</h3>
            </div>
            
            <?php
            try {
                $db = getDB();
                $stmt = $db->query("SELECT * FROM users WHERE role = 'librarian' ORDER BY created_at DESC");
                $librarians = $stmt->fetchAll();
            } catch (PDOException $e) {
                error_log("Fetch librarians error: " . $e->getMessage());
                $librarians = [];
            }
            ?>
            
            <?php if (empty($librarians)): ?>
            <div style="padding: 3rem; text-align: center;">
                <i class="fas fa-user-tie" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                <h3>No Librarians Found</h3>
                <p style="color: var(--text-secondary);">Add your first librarian to get started</p>
            </div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($librarians as $lib): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($lib['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($lib['email']); ?></td>
                        <td><?php echo htmlspecialchars($lib['username']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $lib['status'] == 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($lib['status']); ?>
                            </span>
                        </td>
                        <td><?php echo formatDate($lib['created_at'], 'M d, Y'); ?></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick='openEditModal(<?php echo json_encode($lib); ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="openDeleteModal(<?php echo $lib['id']; ?>, '<?php echo htmlspecialchars($lib['full_name']); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Librarian</h2>
            <span class="close" onclick="closeAddModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" class="form-control" required minlength="6">
            </div>
            
            <div class="form-group">
                <label>Status *</label>
                <select name="status" class="form-control" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Librarian</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Librarian</h2>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" id="edit_username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>New Password (leave empty to keep current)</label>
                <input type="password" name="password" class="form-control" minlength="6">
            </div>
            
            <div class="form-group">
                <label>Status *</label>
                <select name="status" id="edit_status" class="form-control" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Librarian</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2>Confirm Delete</h2>
            <span class="close" onclick="closeDeleteModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="user_id" id="delete_user_id">
            
            <p>Are you sure you want to delete <strong id="delete_librarian_name"></strong>?</p>
            <p style="color: var(--danger-color);">This action cannot be undone.</p>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').style.display = 'flex';
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
}

function openEditModal(librarian) {
    document.getElementById('edit_user_id').value = librarian.id;
    document.getElementById('edit_full_name').value = librarian.full_name;
    document.getElementById('edit_email').value = librarian.email;
    document.getElementById('edit_username').value = librarian.username;
    document.getElementById('edit_status').value = librarian.status;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function openDeleteModal(id, name) {
    document.getElementById('delete_user_id').value = id;
    document.getElementById('delete_librarian_name').textContent = name;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
