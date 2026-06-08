<?php
/**
 * Reports - Librarian
 * Library Management System
 */

require_once '../config/config.php';
requireRole('librarian');

$pageTitle = 'Reports';

include '../includes/header.php';
include '../includes/sidebar_librarian.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Reports</h1>
        </div>
        
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
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
