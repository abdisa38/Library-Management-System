<?php
<<<<<<< HEAD
/**
 * Reports - Librarian
 * Library Management System
 */

require_once '../config/config.php';
requireRole('librarian');

$pageTitle = 'Reports';

=======
require_once '../config/config.php';
requireRole('librarian');
$pageTitle = 'Reports';
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
<<<<<<< HEAD
    
=======
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Reports</h1>
        </div>
        
<<<<<<< HEAD
        <div class="card">
            <div class="card-header">
                <h3>Library Reports</h3>
            </div>
            
            <div style="padding: 2rem;">
                <p>Reports functionality coming soon...</p>
                <ul>
                    <li>Monthly borrow statistics</li>
                    <li>Most popular books</li>
                    <li>Student activity reports</li>
                    <li>Fine collection reports</li>
                    <li>Book availability reports</li>
                </ul>
=======
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            <a href="<?php echo SITE_URL; ?>/librarian/borrow-history.php" class="card" style="text-align: center; padding: 2rem;">
                <i class="fas fa-file-alt" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h3>Borrow Report</h3>
                <p style="color: var(--text-secondary);">View all borrowing records</p>
            </a>
            
            <a href="<?php echo SITE_URL; ?>/librarian/fines.php" class="card" style="text-align: center; padding: 2rem;">
                <i class="fas fa-money-bill" style="font-size: 3rem; color: var(--warning-color); margin-bottom: 1rem;"></i>
                <h3>Fine Report</h3>
                <p style="color: var(--text-secondary);">View all fines and payments</p>
            </a>
            
            <div class="card" style="text-align: center; padding: 2rem;">
                <i class="fas fa-book" style="font-size: 3rem; color: var(--info-color); margin-bottom: 1rem;"></i>
                <h3>Book Inventory</h3>
                <p style="color: var(--text-secondary);">
                    <?php
                    try {
                        $db = getDB();
                        $stmt = $db->query("SELECT COUNT(*) as total, SUM(quantity) as books FROM books");
                        $data = $stmt->fetch();
                        echo $data['total'] . ' titles, ' . $data['books'] . ' books';
                    } catch (PDOException $e) { echo 'N/A'; }
                    ?>
                </p>
                <a href="<?php echo SITE_URL; ?>/librarian/books.php" class="btn btn-primary btn-sm">View Books</a>
>>>>>>> 533eea2df3a74a0379fdafca4c2a807b467aef84
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
