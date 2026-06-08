<?php
<<<<<<< HEAD
/**
 * Manage Books - Librarian
 * Library Management System
 */

require_once '../config/config.php';
requireRole('librarian');

$pageTitle = 'Manage Books';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Add new book
        if ($action === 'add' && validateCSRFToken($_POST['csrf_token'])) {
            try {
                $db = getDB();
                
                // Handle book cover upload
                $bookCover = null;
                if (isset($_FILES['book_cover']) && $_FILES['book_cover']['error'] === 0) {
                    $uploadResult = uploadBookCover($_FILES['book_cover']);
                    if ($uploadResult['success']) {
                        $bookCover = $uploadResult['filename'];
                    } else {
                        setErrorMessage($uploadResult['message']);
                    }
                }
                
                $stmt = $db->prepare("INSERT INTO books (category_id, book_title, author, isbn, publisher, publication_year, quantity, available_quantity, book_cover, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $quantity = (int)$_POST['quantity'];
                $stmt->execute([
                    $_POST['category_id'],
                    $_POST['book_title'],
                    $_POST['author'],
                    $_POST['isbn'],
                    $_POST['publisher'],
                    $_POST['publication_year'],
                    $quantity,
                    $quantity, // available_quantity same as quantity initially
                    $bookCover,
                    $_POST['description']
                ]);
                
                setSuccessMessage('Book added successfully!');
                redirect(SITE_URL . '/librarian/books.php');
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    setErrorMessage('ISBN already exists in the system');
                } else {
                    error_log("Add book error: " . $e->getMessage());
                    setErrorMessage('Error adding book');
                }
            }
        }
        
        // Update book
        if ($action === 'update' && validateCSRFToken($_POST['csrf_token'])) {
            try {
                $db = getDB();
                
                // Handle book cover upload
                $bookCover = $_POST['existing_cover'];
                if (isset($_FILES['book_cover']) && $_FILES['book_cover']['error'] === 0) {
                    $uploadResult = uploadBookCover($_FILES['book_cover']);
                    if ($uploadResult['success']) {
                        $bookCover = $uploadResult['filename'];
                        // Delete old cover
                        if ($bookCover && file_exists(BOOK_COVER_PATH . '/' . $_POST['existing_cover'])) {
                            unlink(BOOK_COVER_PATH . '/' . $_POST['existing_cover']);
                        }
                    }
                }
                
                $stmt = $db->prepare("UPDATE books SET category_id = ?, book_title = ?, author = ?, isbn = ?, publisher = ?, publication_year = ?, quantity = ?, book_cover = ?, description = ? WHERE id = ?");
                
                $stmt->execute([
                    $_POST['category_id'],
                    $_POST['book_title'],
                    $_POST['author'],
                    $_POST['isbn'],
                    $_POST['publisher'],
                    $_POST['publication_year'],
                    $_POST['quantity'],
                    $bookCover,
                    $_POST['description'],
                    $_POST['book_id']
                ]);
                
                setSuccessMessage('Book updated successfully!');
                redirect(SITE_URL . '/librarian/books.php');
            } catch (PDOException $e) {
                error_log("Update book error: " . $e->getMessage());
                setErrorMessage('Error updating book');
            }
        }
        
        // Delete book
        if ($action === 'delete' && validateCSRFToken($_POST['csrf_token'])) {
            try {
                $db = getDB();
                
                // Get book cover filename before deleting
                $stmt = $db->prepare("SELECT book_cover FROM books WHERE id = ?");
                $stmt->execute([$_POST['book_id']]);
                $book = $stmt->fetch();
                
                // Delete book
                $stmt = $db->prepare("DELETE FROM books WHERE id = ?");
                $stmt->execute([$_POST['book_id']]);
                
                // Delete cover file
                if ($book && $book['book_cover'] && file_exists(BOOK_COVER_PATH . '/' . $book['book_cover'])) {
                    unlink(BOOK_COVER_PATH . '/' . $book['book_cover']);
                }
                
                setSuccessMessage('Book deleted successfully!');
                redirect(SITE_URL . '/librarian/books.php');
            } catch (PDOException $e) {
                error_log("Delete book error: " . $e->getMessage());
                setErrorMessage('Error deleting book. Book may have active borrows.');
            }
        }
    }
}

// Fetch books with search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

try {
    $db = getDB();
    
    // Build query
    $whereConditions = [];
    $params = [];
    
    if ($search) {
        $whereConditions[] = "(b.book_title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($categoryFilter) {
        $whereConditions[] = "b.category_id = ?";
        $params[] = $categoryFilter;
    }
    
    $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM books b $whereClause");
    $countStmt->execute($params);
    $totalBooks = $countStmt->fetch()['total'];
    $totalPages = ceil($totalBooks / $perPage);
    
    // Get books
    $stmt = $db->prepare("SELECT b.*, c.category_name 
                          FROM books b 
                          INNER JOIN categories c ON b.category_id = c.id 
                          $whereClause 
                          ORDER BY b.created_at DESC 
                          LIMIT ? OFFSET ?");
    $params[] = $perPage;
    $params[] = $offset;
    $stmt->execute($params);
    $books = $stmt->fetchAll();
    
    // Get categories for dropdown
    $categoriesStmt = $db->query("SELECT * FROM categories ORDER BY category_name");
    $categories = $categoriesStmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Books fetch error: " . $e->getMessage());
    setErrorMessage("Error loading books");
}

=======
require_once '../config/config.php';
requireRole('librarian');
$pageTitle = 'Manage Books';

// Simply reuse the admin books functionality for librarian
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
<<<<<<< HEAD
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Manage Books</h1>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New Book
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
                <h3>Books List</h3>
                <div style="display: flex; gap: 1rem;">
                    <form method="GET" style="display: flex; gap: 0.5rem;">
                        <input type="text" name="search" placeholder="Search books..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Available</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($books)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No books found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($books as $book): ?>
                    <tr>
                        <td>
                            <?php if ($book['book_cover']): ?>
                            <img src="<?php echo SITE_URL . '/uploads/book_covers/' . $book['book_cover']; ?>" alt="Cover" style="width: 40px; height: 60px; object-fit: cover;">
                            <?php else: ?>
                            <div style="width: 40px; height: 60px; background: #ddd; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-book"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($book['book_title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($book['category_name']); ?></span></td>
                        <td><?php echo $book['quantity']; ?></td>
                        <td>
                            <span class="badge <?php echo $book['available_quantity'] > 0 ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $book['available_quantity']; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick='openEditModal(<?php echo json_encode($book); ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="openDeleteModal(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['book_title']); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>" class="btn btn-sm">Previous</a>
                <?php endif; ?>
                
                <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>" class="btn btn-sm">Next</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
=======
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Manage Books</h1>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Books</h3>
            </div>
            <div class="card-body">
                <?php
                try {
                    $db = getDB();
                    $stmt = $db->query("
                        SELECT b.*, c.category_name 
                        FROM books b
                        INNER JOIN categories c ON b.category_id = c.id
                        ORDER BY b.book_title
                    ");
                    $books = $stmt->fetchAll();
                ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>ISBN</th>
                                <th>Category</th>
                                <th>Total</th>
                                <th>Available</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($book['book_title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                <td><span class="badge badge-primary"><?php echo $book['category_name']; ?></span></td>
                                <td><?php echo $book['quantity']; ?></td>
                                <td><?php echo $book['available_quantity']; ?></td>
                                <td><span class="badge badge-<?php echo $book['available_quantity'] > 0 ? 'success' : 'danger'; ?>"><?php echo $book['available_quantity'] > 0 ? 'Available' : 'Out of Stock'; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php } catch (PDOException $e) { echo '<p>Error loading books.</p>'; } ?>
            </div>
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
        </div>
    </div>
</div>

<<<<<<< HEAD
<!-- Add Book Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Book</h2>
            <span class="close" onclick="closeAddModal()">&times;</span>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label>Book Title *</label>
                <input type="text" name="book_title" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Author *</label>
                <input type="text" name="author" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>ISBN *</label>
                <input type="text" name="isbn" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Category *</label>
                <select name="category_id" class="form-control" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Publisher</label>
                <input type="text" name="publisher" class="form-control">
            </div>
            
            <div class="form-group">
                <label>Publication Year</label>
                <input type="number" name="publication_year" class="form-control" min="1000" max="<?php echo date('Y'); ?>">
            </div>
            
            <div class="form-group">
                <label>Quantity *</label>
                <input type="number" name="quantity" class="form-control" min="1" required>
            </div>
            
            <div class="form-group">
                <label>Book Cover</label>
                <input type="file" name="book_cover" class="form-control" accept="image/*">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Book</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Book Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Book</h2>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <form method="POST" enctype="multipart/form-data" id="editForm">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="book_id" id="edit_book_id">
            <input type="hidden" name="existing_cover" id="edit_existing_cover">
            
            <div class="form-group">
                <label>Book Title *</label>
                <input type="text" name="book_title" id="edit_book_title" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Author *</label>
                <input type="text" name="author" id="edit_author" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>ISBN *</label>
                <input type="text" name="isbn" id="edit_isbn" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Category *</label>
                <select name="category_id" id="edit_category_id" class="form-control" required>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Publisher</label>
                <input type="text" name="publisher" id="edit_publisher" class="form-control">
            </div>
            
            <div class="form-group">
                <label>Publication Year</label>
                <input type="number" name="publication_year" id="edit_publication_year" class="form-control">
            </div>
            
            <div class="form-group">
                <label>Quantity *</label>
                <input type="number" name="quantity" id="edit_quantity" class="form-control" min="1" required>
            </div>
            
            <div class="form-group">
                <label>Book Cover (leave empty to keep current)</label>
                <input type="file" name="book_cover" class="form-control" accept="image/*">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Book</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2>Confirm Delete</h2>
            <span class="close" onclick="closeDeleteModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="book_id" id="delete_book_id">
            
            <p>Are you sure you want to delete <strong id="delete_book_title"></strong>?</p>
            
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

function openEditModal(book) {
    document.getElementById('edit_book_id').value = book.id;
    document.getElementById('edit_book_title').value = book.book_title;
    document.getElementById('edit_author').value = book.author;
    document.getElementById('edit_isbn').value = book.isbn;
    document.getElementById('edit_category_id').value = book.category_id;
    document.getElementById('edit_publisher').value = book.publisher || '';
    document.getElementById('edit_publication_year').value = book.publication_year || '';
    document.getElementById('edit_quantity').value = book.quantity;
    document.getElementById('edit_description').value = book.description || '';
    document.getElementById('edit_existing_cover').value = book.book_cover || '';
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function openDeleteModal(id, title) {
    document.getElementById('delete_book_id').value = id;
    document.getElementById('delete_book_title').textContent = title;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

=======
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
<?php include '../includes/footer.php'; ?>
