<?php
/**
 * Manage Categories - Librarian
 * Library Management System
 */

require_once '../config/config.php';
requireRole('librarian');

$pageTitle = 'Manage Categories';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && validateCSRFToken($_POST['csrf_token'])) {
        $action = $_POST['action'];
        
        try {
            $db = getDB();
            
            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
                $stmt->execute([$_POST['category_name'], $_POST['description']]);
                setSuccessMessage('Category added successfully!');
            } elseif ($action === 'update') {
                $stmt = $db->prepare("UPDATE categories SET category_name = ?, description = ? WHERE id = ?");
                $stmt->execute([$_POST['category_name'], $_POST['description'], $_POST['category_id']]);
                setSuccessMessage('Category updated successfully!');
            } elseif ($action === 'delete') {
                $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$_POST['category_id']]);
                setSuccessMessage('Category deleted successfully!');
            }
            
            redirect(SITE_URL . '/librarian/categories.php');
        } catch (PDOException $e) {
            error_log("Category operation error: " . $e->getMessage());
            setErrorMessage('Error processing request. Category may have books assigned to it.');
        }
    }
}

// Fetch categories
try {
    $db = getDB();
    $stmt = $db->query("SELECT c.*, COUNT(b.id) as book_count 
                        FROM categories c 
                        LEFT JOIN books b ON c.id = b.category_id 
                        GROUP BY c.id 
                        ORDER BY c.category_name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Categories fetch error: " . $e->getMessage());
    setErrorMessage("Error loading categories");
}

include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Manage Categories</h1>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New Category
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
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Books Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">No categories found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cat['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($cat['description']); ?></td>
                        <td><span class="badge badge-info"><?php echo $cat['book_count']; ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick='openEditModal(<?php echo json_encode($cat); ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="openDeleteModal(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['category_name']); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Category</h2>
            <span class="close" onclick="closeAddModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label>Category Name *</label>
                <input type="text" name="category_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Category</h2>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="category_id" id="edit_category_id">
            
            <div class="form-group">
                <label>Category Name *</label>
                <input type="text" name="category_name" id="edit_category_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Category</button>
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
            <input type="hidden" name="category_id" id="delete_category_id">
            
            <p>Are you sure you want to delete <strong id="delete_category_name"></strong>?</p>
            
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

function openEditModal(category) {
    document.getElementById('edit_category_id').value = category.id;
    document.getElementById('edit_category_name').value = category.category_name;
    document.getElementById('edit_description').value = category.description || '';
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function openDeleteModal(id, name) {
    document.getElementById('delete_category_id').value = id;
    document.getElementById('delete_category_name').textContent = name;
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
