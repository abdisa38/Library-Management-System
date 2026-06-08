<?php
/**
 * Logout Handler
 * Library Management System
 */

require_once '../config/config.php';

// Clear all session data
session_unset();
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}

// Redirect to login page
redirect(SITE_URL . '/auth/login.php');
