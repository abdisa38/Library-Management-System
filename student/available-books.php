<?php
/**
 * Available Books - Student
 * Library Management System
 */

require_once '../config/config.php';
requireRole('student');

$pageTitle = 'Available Books';

// Get filters
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

try {
    $db = getDB();
    
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
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($categoryFilter) {
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
}

include '../includes/header.php';
include '../includes/sidebar_student.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Available Books</h1>
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
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
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
        </div>
    </div>
</div>

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
        </div>
    </div>
</div>

<script>
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
}
</script>

<?php include '../includes/footer.php'; ?>
