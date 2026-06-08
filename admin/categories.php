<?php
/**
 * Manage Categories - Super Admin
 * Library Management System
 */

require_once '../config/config.php';
requireRole('super_admin');

$pageTitle = 'Manage Categories';

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $db = getDB();
        
        if ($action === 'add') {
            $categoryName = sanitizeInput($_POST['category_name']);
            $description = sanitizeInput($_POST['description']);
            
            $stmt = $db->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
            $stmt->execute([$categoryName, $description]);
            
            setSuccessMessage('Category added successfully!');
            
        } elseif ($action === 'edit') {
            $categoryId = intval($_POST['category_id']);
            $categoryName = sanitizeInput($_POST['category_name']);
            $description = sanitizeInput($_POST['description']);
            
            $stmt = $db->prepare("UPDATE categories SET category_name = ?, description = ? WHERE id = ?");
            $stmt->execute([$categoryName, $description, $categoryId]);
            
            setSuccessMessage('Category updated successfully!');
            
        } elseif ($action === 'delete') {
            $categoryId = intval($_POST['category_id']);
            
            // Check if category has books
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM books WHERE category_id = ?");
            $stmt->execute([$categoryId]);
            $bookCount = $stmt->fetch()['count'];
            
            if ($bookCount > 0) {
                setErrorMessage("Cannot delete category. It has $bookCount book(s) assigned.");
            } else {
                $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$categoryId]);
                setSuccessMessage('Category deleted successfully!');
            }
        }
        
        redirect(SITE_URL . '/admin/categories.php');
        
    } catch (PDOException $e) {
        error_log("Categories error: " . $e->getMessage());
        setErrorMessage('An error occurred. Please try again.');
        redirect(SITE_URL . '/admin/categories.php');
    }
}

// Fetch categories
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT c.*, COUNT(b.id) as book_count
        FROM categories c
        LEFT JOIN books b ON c.id = b.category_id
        GROUP BY c.id
        ORDER BY c.category_name
    ");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch categories error: " . $e->getMessage());
    $categories = [];
}

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Manage Categories</h1>
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <span>/</span>
                <span>Categories</span>
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
                <h3 class="card-title">Book Categories</h3>
                <button class="btn btn-primary" onclick="openModal('addCategoryModal')">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($categories)): ?>
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <h3>No Categories Found</h3>
                    <p>Start by adding your first category.</p>
                </div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Books Count</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($category['category_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($category['description']); ?></td>
                                <td><span class="badge badge-primary"><?php echo $category['book_count']; ?> books</span></td>
                                <td><?php echo formatDate($category['created_at'], 'M d, Y'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick='editCategory(<?php echo json_encode($category); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>')">
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

<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Add New Category</h2>
            <button class="modal-close" onclick="closeModal('addCategoryModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label required">Category Name</label>
                    <input type="text" name="category_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addCategoryModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Category
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Edit Category</h2>
            <button class="modal-close" onclick="closeModal('editCategoryModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="category_id" id="edit_category_id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label required">Category Name</label>
                    <input type="text" name="category_name" id="edit_category_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editCategoryModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Category
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="category_id" id="delete_category_id">
</form>

<script>
function editCategory(category) {
    document.getElementById('edit_category_id').value = category.id;
    document.getElementById('edit_category_name').value = category.category_name;
    document.getElementById('edit_description').value = category.description || '';
    openModal('editCategoryModal');
}

function deleteCategory(id, name) {
    if (confirm('Are you sure you want to delete "' + name + '"?')) {
        document.getElementById('delete_category_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
