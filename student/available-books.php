<?php
/**
 * Available Books - Student
 * Library Management System
 */

require_once '../config/config.php';
requireRole('student');

$pageTitle = 'Available Books';
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

try {
    $db = getDB();
    
    // Build query
    $whereConditions = ["b.available_quantity > 0"];
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
}

include '../includes/header.php';
include '../includes/sidebar_student.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Available Books</h1>
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
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
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
        </div>
    </div>
</div>

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
        </div>
    </div>
</div>

<script>
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
}
</script>

<?php include '../includes/footer.php'; ?>
