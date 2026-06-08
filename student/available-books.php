<?php
/**
 * Available Books - Student
 * Library Management System
 */

require_once '../config/config.php';
requireRole('student');

$pageTitle = 'Available Books';
<<<<<<< HEAD
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
    
} catch (PDOException $e) {
    error_log("Student fetch error: " . $e->getMessage());
}

// Fetch available books with search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;
=======

// Get filters
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84

try {
    $db = getDB();
    
<<<<<<< HEAD
    // Build query
    $whereConditions = ["b.available_quantity > 0"];
    $params = [];
    
    if ($search) {
        $whereConditions[] = "(b.book_title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
=======
    // Get categories
    $stmt = $db->query("SELECT * FROM categories ORDER BY category_name");
    $categories = $stmt->fetchAll();
    
    // Build books query
    $sql = "
        SELECT b.*, c.category_name 
        FROM books b
        INNER JOIN categories c ON b.category_id = c.id
        WHERE b.available_quantity > 0
    ";
    $params = [];
    
    if ($search) {
        $sql .= " AND (b.book_title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($categoryFilter) {
<<<<<<< HEAD
        $whereConditions[] = "b.category_id = ?";
        $params[] = $categoryFilter;
    }
    
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    
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
=======
        $sql .= " AND b.category_id = ?";
        $params[] = $categoryFilter;
    }
    
    $sql .= " ORDER BY b.book_title ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Fetch books error: " . $e->getMessage());
    $books = [];
    $categories = [];
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
}

include '../includes/header.php';
include '../includes/sidebar_student.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Available Books</h1>
<<<<<<< HEAD
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
                <h3>Browse Books</h3>
                <div style="display: flex; gap: 1rem;">
                    <form method="GET" style="display: flex; gap: 0.5rem;">
                        <input type="text" name="search" placeholder="Search books..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
=======
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>/student/dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <span>/</span>
                <span>Available Books</span>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Browse Books</h3>
            </div>
            <div class="card-body">
                <!-- Search and Filter -->
                <form method="GET" class="mb-2">
                    <div class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by title, author, or ISBN..." 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               style="max-width: 400px;">
                        
                        <select name="category" class="form-select" style="max-width: 200px;">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
<<<<<<< HEAD
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
            </div>
            
            <?php if (empty($books)): ?>
            <div style="padding: 3rem; text-align: center;">
                <i class="fas fa-book" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                <h3>No Books Found</h3>
                <p style="color: var(--text-secondary);">Try adjusting your search filters</p>
            </div>
            <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; padding: 1.5rem;">
                <?php foreach ($books as $book): ?>
                <div class="card" style="height: 100%; display: flex; flex-direction: column;">
                    <?php if ($book['book_cover']): ?>
                    <img src="<?php echo SITE_URL . '/uploads/book_covers/' . $book['book_cover']; ?>" alt="Cover" style="width: 100%; height: 300px; object-fit: cover; border-radius: 8px 8px 0 0;">
                    <?php else: ?>
                    <div style="width: 100%; height: 300px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; border-radius: 8px 8px 0 0;">
                        <i class="fas fa-book" style="font-size: 4rem; color: white;"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div style="padding: 1rem; flex: 1; display: flex; flex-direction: column;">
                        <h3 style="margin: 0 0 0.5rem 0; font-size: 1.1rem;"><?php echo htmlspecialchars($book['book_title']); ?></h3>
                        <p style="color: var(--text-secondary); margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($book['author']); ?></p>
                        <p style="margin: 0 0 0.5rem 0;"><span class="badge badge-info"><?php echo htmlspecialchars($book['category_name']); ?></span></p>
                        <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0 0 0.5rem 0;">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></p>
                        <?php if ($book['description']): ?>
                        <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0 0 1rem 0; flex: 1;"><?php echo htmlspecialchars(substr($book['description'], 0, 100)) . (strlen($book['description']) > 100 ? '...' : ''); ?></p>
                        <?php endif; ?>
                        
                        <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center;">
                            <span class="badge badge-success"><?php echo $book['available_quantity']; ?> Available</span>
                            <button class="btn btn-sm btn-primary" onclick='viewBook(<?php echo json_encode($book); ?>)'>
                                <i class="fas fa-eye"></i> View
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div class="pagination" style="padding: 1rem;">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>" class="btn btn-sm">Previous</a>
                <?php endif; ?>
                
                <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>" class="btn btn-sm">Next</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
=======
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="<?php echo SITE_URL; ?>/student/available-books.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
                
                <?php if (empty($books)): ?>
                <div class="empty-state">
                    <i class="fas fa-book"></i>
                    <h3>No Books Available</h3>
                    <p>No books match your search criteria or all books are currently borrowed.</p>
                </div>
                <?php else: ?>
                <div class="books-grid">
                    <?php foreach ($books as $book): ?>
                    <div class="book-card">
                        <?php if ($book['book_cover']): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/book_covers/<?php echo $book['book_cover']; ?>" 
                                 alt="<?php echo htmlspecialchars($book['book_title']); ?>" 
                                 class="book-cover">
                        <?php else: ?>
                            <div class="book-cover" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-book" style="font-size: 3rem; color: white; opacity: 0.7;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="book-info">
                            <div class="book-title"><?php echo htmlspecialchars($book['book_title']); ?></div>
                            <div class="book-author">by <?php echo htmlspecialchars($book['author']); ?></div>
                            
                            <div class="book-meta">
                                <span class="badge badge-primary"><?php echo htmlspecialchars($book['category_name']); ?></span>
                                <span class="badge badge-success"><?php echo $book['available_quantity']; ?> available</span>
                            </div>
                            
                            <div class="book-actions">
                                <button class="btn btn-primary btn-sm" style="width: 100%;" 
                                        onclick="viewBookDetails(<?php echo htmlspecialchars(json_encode($book)); ?>)">
                                    <i class="fas fa-info-circle"></i> View Details
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
        </div>
    </div>
</div>

<<<<<<< HEAD
<!-- View Book Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal_book_title"></h2>
            <span class="close" onclick="closeViewModal()">&times;</span>
        </div>
        <div style="padding: 1.5rem;">
            <div style="display: grid; grid-template-columns: 200px 1fr; gap: 2rem;">
                <div>
                    <img id="modal_book_cover" src="" alt="Cover" style="width: 100%; border-radius: 8px;">
                </div>
                <div>
                    <p><strong>Author:</strong> <span id="modal_author"></span></p>
                    <p><strong>ISBN:</strong> <span id="modal_isbn"></span></p>
                    <p><strong>Category:</strong> <span id="modal_category"></span></p>
                    <p><strong>Publisher:</strong> <span id="modal_publisher"></span></p>
                    <p><strong>Publication Year:</strong> <span id="modal_year"></span></p>
                    <p><strong>Available Copies:</strong> <span id="modal_available" class="badge badge-success"></span></p>
                    <p><strong>Description:</strong></p>
                    <p id="modal_description" style="color: var(--text-secondary);"></p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
=======
<!-- Book Details Modal -->
<div id="bookDetailsModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2 class="modal-title">Book Details</h2>
            <button class="modal-close" onclick="closeModal('bookDetailsModal')">&times;</button>
        </div>
        <div class="modal-body" id="bookDetailsContent">
            <!-- Content will be dynamically inserted -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('bookDetailsModal')">Close</button>
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
        </div>
    </div>
</div>

<script>
<<<<<<< HEAD
function viewBook(book) {
    document.getElementById('modal_book_title').textContent = book.book_title;
    document.getElementById('modal_author').textContent = book.author;
    document.getElementById('modal_isbn').textContent = book.isbn;
    document.getElementById('modal_category').textContent = book.category_name;
    document.getElementById('modal_publisher').textContent = book.publisher || 'N/A';
    document.getElementById('modal_year').textContent = book.publication_year || 'N/A';
    document.getElementById('modal_available').textContent = book.available_quantity;
    document.getElementById('modal_description').textContent = book.description || 'No description available';
    
    if (book.book_cover) {
        document.getElementById('modal_book_cover').src = '<?php echo SITE_URL; ?>/uploads/book_covers/' + book.book_cover;
    } else {
        document.getElementById('modal_book_cover').src = '';
    }
    
    document.getElementById('viewModal').style.display = 'flex';
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
=======
function viewBookDetails(book) {
    const content = `
        <div style="text-align: center; margin-bottom: 1.5rem;">
            ${book.book_cover ? 
                `<img src="<?php echo SITE_URL; ?>/uploads/book_covers/${book.book_cover}" 
                     alt="Book Cover" style="max-width: 200px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">` :
                `<div style="width: 200px; height: 280px; margin: 0 auto; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                     border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-book" style="font-size: 4rem; color: white; opacity: 0.7;"></i>
                </div>`
            }
        </div>
        
        <div style="line-height: 1.8;">
            <p><strong>Title:</strong> ${book.book_title}</p>
            <p><strong>Author:</strong> ${book.author}</p>
            <p><strong>ISBN:</strong> ${book.isbn}</p>
            <p><strong>Category:</strong> <span class="badge badge-primary">${book.category_name}</span></p>
            ${book.publisher ? `<p><strong>Publisher:</strong> ${book.publisher}</p>` : ''}
            ${book.publication_year ? `<p><strong>Year:</strong> ${book.publication_year}</p>` : ''}
            <p><strong>Available Copies:</strong> <span class="badge badge-success">${book.available_quantity} / ${book.quantity}</span></p>
            ${book.description ? `<p><strong>Description:</strong><br>${book.description}</p>` : ''}
        </div>
        
        <div class="alert alert-info" style="margin-top: 1rem;">
            <i class="fas fa-info-circle"></i>
            <span>To borrow this book, please visit the library and request it from the librarian.</span>
        </div>
    `;
    
    document.getElementById('bookDetailsContent').innerHTML = content;
    openModal('bookDetailsModal');
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
}
</script>

<?php include '../includes/footer.php'; ?>
