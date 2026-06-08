<?php
require_once '../config/config.php';
requireRole('librarian');
$pageTitle = 'Manage Books';

// Simply reuse the admin books functionality for librarian
include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
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
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
