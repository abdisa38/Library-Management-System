<?php
/**
 * Manage Books - Super Admin
 * Library Management System
 */

require_once '../config/config.php';
requireRole('super_admin');

$pageTitle = 'Manage Books';
$currentUser = getCurrentUser();

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $db = getDB();
        
        if ($action === 'add') {
            $categoryId = sanitizeInput($_POST['category_id']);
            $bookTitle = sanitizeInput($_POST['book_title']);
            $author = sanitizeInput($_POST['author']);
            $isbn = sanitizeInput($_POST['isbn']);
            $publisher = sanitizeInput($_POST['publisher']);
            $publicationYear = sanitizeInput($_POST['publication_year']);
            $quantity = intval($_POST['quantity']);
            $description = sanitizeInput($_POST['description']);
            
            // Handle book cover upload
            $bookCover = null;
            if (isset($_FILES['book_cover']) && $_FILES['book_cover']['error'] === 0) {
                $upload = uploadBookCover($_FILES['book_cover']);
                if ($upload['success']) {
                    $bookCover = $upload['filename'];
                }
            }
            
            $stmt = $db->prepare("
                INSERT INTO books (category_id, book_title, author, isbn, publisher, publication_year, 
                                   quantity, available_quantity, book_cover, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $categoryId, $bookTitle, $author, $isbn, $publisher, $publicationYear,
                $quantity, $quantity, $bookCover, $description
            ]);
            
            setSuccessMessage('Book added successfully!');
            redirect(SITE_URL . '/admin/books.php');
            
        } elseif ($action === 'edit') {
            $bookId = intval($_POST['book_id']);
            $categoryId = sanitizeInput($_POST['category_id']);
            $bookTitle = sanitizeInput($_POST['book_title']);
            $author = sanitizeInput($_POST['author']);
            $isbn = sanitizeInput($_POST['isbn']);
            $publisher = sanitizeInput($_POST['publisher']);
            $publicationYear = sanitizeInput($_POST['publication_year']);
            $quantity = intval($_POST['quantity']);
            $description = sanitizeInput($_POST['description']);
            
            // Get current book data
            $stmt = $db->prepare("SELECT * FROM books WHERE id = ?");
            $stmt->execute([$bookId]);
            $currentBook = $stmt->fetch();
            
            $bookCover = $currentBook['book_cover'];
            
            // Handle new cover upload
            if (isset($_FILES['book_cover']) && $_FILES['book_cover']['error'] === 0) {
                $upload = uploadBookCover($_FILES['book_cover']);
                if ($upload['success']) {
                    // Delete old cover
                    if ($bookCover && file_exists(BOOK_COVER_PATH . '/' . $bookCover)) {
                        unlink(BOOK_COVER_PATH . '/' . $bookCover);
                    }
                    $bookCover = $upload['filename'];
                }
            }
            
            // Calculate available quantity
            $borrowed = $currentBook['quantity'] - $currentBook['available_quantity'];
            $availableQuantity = max(0, $quantity - $borrowed);
            
            $stmt = $db->prepare("
                UPDATE books 
                SET category_id = ?, book_title = ?, author = ?, isbn = ?, publisher = ?, 
                    publication_year = ?, quantity = ?, available_quantity = ?, 
                    book_cover = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $categoryId, $bookTitle, $author, $isbn, $publisher, $publicationYear,
                $quantity, $availableQuantity, $bookCover, $description, $bookId
            ]);
            
            setSuccessMessage('Book updated successfully!');
            redirect(SITE_URL . '/admin/books.php');
            
        } elseif ($action === 'delete') {
            $bookId = intval($_POST['book_id']);
            
            // Check if book is borrowed
            $stmt = $db->prepare("
                SELECT COUNT(*) as count FROM borrow_records 
                WHERE book_id = ? AND status IN ('borrowed', 'overdue')
            ");
            $stmt->execute([$bookId]);
            $borrowed = $stmt->fetch()['count'];
            
            if ($borrowed > 0) {
                setErrorMessage('Cannot delete book. It is currently borrowed.');
            } else {
                // Get book cover to delete
                $stmt = $db->prepare("SELECT book_cover FROM books WHERE id = ?");
                $stmt->execute([$bookId]);
                $book = $stmt->fetch();
                
                // Delete book
                $stmt = $db->prepare("DELETE FROM books WHERE id = ?");
                $stmt->execute([$bookId]);
                
                // Delete cover file
                if ($book['book_cover'] && file_exists(BOOK_COVER_PATH . '/' . $book['book_cover'])) {
                    unlink(BOOK_COVER_PATH . '/' . $book['book_cover']);
                }
                
                setSuccessMessage('Book deleted successfully!');
            }
            redirect(SITE_URL . '/admin/books.php');
        }
    } catch (PDOException $e) {
        error_log("Books error: " . $e->getMessage());
        setErrorMessage('An error occurred. Please try again.');
        redirect(SITE_URL . '/admin/books.php');
    }
}

// Fetch books
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

try {
    $db = getDB();
    
    // Get categories for filter
    $stmt = $db->query("SELECT * FROM categories ORDER BY category_name");
    $categories = $stmt->fetchAll();
    
    // Build query
    $sql = "
        SELECT b.*, c.category_name 
        FROM books b
        INNER JOIN categories c ON b.category_id = c.id
        WHERE 1=1
    ";
    $params = [];
    
    if ($search) {
        $sql .= " AND (b.book_title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($categoryFilter) {
        $sql .= " AND b.category_id = ?";
        $params[] = $categoryFilter;
    }
    
    $sql .= " ORDER BY b.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Fetch books error: " . $e->getMessage());
    $books = [];
    $categories = [];
}

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Manage Books</h1>
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <span>/</span>
                <span>Books</span>
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
                <h3 class="card-title">All Books</h3>
                <button class="btn btn-primary" onclick="openModal('addBookModal')">
                    <i class="fas fa-plus"></i> Add New Book
                </button>
            </div>
            <div class="card-body">
                <!-- Search and Filter -->
                <form method="GET" class="d-flex gap-2 mb-2">
                    <input type="text" name="search" class="form-control" placeholder="Search by title, author, or ISBN..." 
                           value="<?php echo htmlspecialchars($search); ?>" style="max-width: 400px;">
                    
                    <select name="category" class="form-select" style="max-width: 200px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="<?php echo SITE_URL; ?>/admin/books.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </form>
                
                <?php if (empty($books)): ?>
                <div class="empty-state">
                    <i class="fas fa-book"></i>
                    <h3>No Books Found</h3>
                    <p>Start by adding your first book to the library.</p>
                    <button class="btn btn-primary" onclick="openModal('addBookModal')">
                        <i class="fas fa-plus"></i> Add Book
                    </button>
                </div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table">
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
                            <?php foreach ($books as $book): ?>
                            <tr>
                                <td>
                                    <?php if ($book['book_cover']): ?>
                                    <img src="<?php echo SITE_URL; ?>/uploads/book_covers/<?php echo $book['book_cover']; ?>" 
                                         alt="Book Cover" style="width: 50px; height: 70px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                    <div style="width: 50px; height: 70px; background: var(--bg-color); display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                        <i class="fas fa-book" style="color: var(--text-muted);"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($book['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                <td><span class="badge badge-primary"><?php echo htmlspecialchars($book['category_name']); ?></span></td>
                                <td><?php echo $book['quantity']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $book['available_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                        <?php echo $book['available_quantity']; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editBook(<?php echo htmlspecialchars(json_encode($book)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteBook(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['book_title']); ?>')">
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

<!-- Add Book Modal -->
<div id="addBookModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2 class="modal-title">Add New Book</h2>
            <button class="modal-close" onclick="closeModal('addBookModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label required">Book Title</label>
                        <input type="text" name="book_title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Author</label>
                        <input type="text" name="author" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">ISBN</label>
                        <input type="text" name="isbn" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Publisher</label>
                        <input type="text" name="publisher" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Publication Year</label>
                        <input type="number" name="publication_year" class="form-control" min="1800" max="<?php echo date('Y'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Quantity</label>
                        <input type="number" name="quantity" class="form-control" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Book Cover</label>
                        <input type="file" name="book_cover" class="form-control" accept="image/*">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addBookModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Book
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Book Modal -->
<div id="editBookModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2 class="modal-title">Edit Book</h2>
            <button class="modal-close" onclick="closeModal('editBookModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="book_id" id="edit_book_id">
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label required">Book Title</label>
                        <input type="text" name="book_title" id="edit_book_title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Author</label>
                        <input type="text" name="author" id="edit_author" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">ISBN</label>
                        <input type="text" name="isbn" id="edit_isbn" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Category</label>
                        <select name="category_id" id="edit_category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Publisher</label>
                        <input type="text" name="publisher" id="edit_publisher" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Publication Year</label>
                        <input type="number" name="publication_year" id="edit_publication_year" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Quantity</label>
                        <input type="number" name="quantity" id="edit_quantity" class="form-control" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Book Cover</label>
                        <input type="file" name="book_cover" class="form-control" accept="image/*">
                        <small class="form-help">Leave empty to keep current cover</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editBookModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Book
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="book_id" id="delete_book_id">
</form>

<script>
function editBook(book) {
    document.getElementById('edit_book_id').value = book.id;
    document.getElementById('edit_book_title').value = book.book_title;
    document.getElementById('edit_author').value = book.author;
    document.getElementById('edit_isbn').value = book.isbn;
    document.getElementById('edit_category_id').value = book.category_id;
    document.getElementById('edit_publisher').value = book.publisher || '';
    document.getElementById('edit_publication_year').value = book.publication_year || '';
    document.getElementById('edit_quantity').value = book.quantity;
    document.getElementById('edit_description').value = book.description || '';
    
    openModal('editBookModal');
}

function deleteBook(id, title) {
    if (confirm('Are you sure you want to delete "' + title + '"?')) {
        document.getElementById('delete_book_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
