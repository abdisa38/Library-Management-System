<?php
/**
 * Index / Landing Page
 * Library Management System
 */

require_once 'config/config.php';

// Redirect to appropriate dashboard if logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    switch ($role) {
        case 'super_admin':
            redirect(SITE_URL . '/admin/dashboard.php');
            break;
        case 'librarian':
            redirect(SITE_URL . '/librarian/dashboard.php');
            break;
        case 'student':
            redirect(SITE_URL . '/student/dashboard.php');
            break;
    }
} else {
    // Redirect to login page
    redirect(SITE_URL . '/auth/login.php');
}
