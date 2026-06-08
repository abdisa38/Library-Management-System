<?php
require_once '../config/config.php';
requireRole('librarian');
$pageTitle = 'Categories';
include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Book Categories</h1>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Categories</h3>
            </div>
            <div class="card-body">
                <?php
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
                ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Books Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cat['category_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cat['description']); ?></td>
                                <td><span class="badge badge-primary"><?php echo $cat['book_count']; ?> books</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php } catch (PDOException $e) { echo '<p>Error loading categories.</p>'; } ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
