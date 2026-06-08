<?php
/**
 * System Configuration
 * Library Management System
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/database.php';

// Site Configuration
define('SITE_NAME', 'Library Management System');
define('SITE_URL', 'http://localhost/Library%20Management%20System/Library-Management-System');
define('BASE_PATH', dirname(__DIR__));

// Directory paths
define('ASSETS_PATH', SITE_URL . '/assets');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('BOOK_COVER_PATH', UPLOAD_PATH . '/book_covers');

// Create upload directories if they don't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!file_exists(BOOK_COVER_PATH)) {
    mkdir(BOOK_COVER_PATH, 0755, true);
}

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Fine per day (USD)
define('FINE_PER_DAY', 1.00);

// Maximum borrow days
define('MAX_BORROW_DAYS', 14);

// Maximum books per student
define('MAX_BOOKS_PER_STUDENT', 5);

// Timezone
date_default_timezone_set('UTC');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// CSRF Token Generation
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Token Validation
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize Input
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Redirect function
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Check session timeout
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}

// Get current user
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role']
        ];
    }
    return null;
}

// Check user role
function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

// Require login
function requireLogin() {
    if (!isLoggedIn() || !checkSessionTimeout()) {
        redirect(SITE_URL . '/auth/login.php');
    }
}

// Require specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        redirect(SITE_URL . '/auth/login.php');
    }
}

// Format date
function formatDate($date, $format = 'Y-m-d') {
    return date($format, strtotime($date));
}

// Format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// Calculate overdue days
function calculateOverdueDays($dueDate) {
    $due = new DateTime($dueDate);
    $today = new DateTime();
    
    if ($today > $due) {
        $interval = $today->diff($due);
        return $interval->days;
    }
    
    return 0;
}

// Calculate fine
function calculateFine($overdueDays) {
    return $overdueDays * FINE_PER_DAY;
}

// Success message
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

// Error message
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

// Get and clear success message
function getSuccessMessage() {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    return null;
}

// Get and clear error message
function getErrorMessage() {
    if (isset($_SESSION['error_message'])) {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
    return null;
}

// File upload validation
function validateImageUpload($file, $maxSize = 5242880) { // 5MB default
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    if ($fileError !== 0) {
        return ['success' => false, 'message' => 'Error uploading file'];
    }
    
    $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowed)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, and GIF allowed'];
    }
    
    if ($fileSize > $maxSize) {
        return ['success' => false, 'message' => 'File size too large. Maximum 5MB allowed'];
    }
    
    // Verify it's actually an image
    $check = getimagesize($fileTmpName);
    if ($check === false) {
        return ['success' => false, 'message' => 'File is not a valid image'];
    }
    
    return ['success' => true];
}

// Upload book cover
function uploadBookCover($file) {
    $validation = validateImageUpload($file);
    
    if (!$validation['success']) {
        return $validation;
    }
    
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newFileName = uniqid('book_', true) . '.' . $fileExt;
    $destination = BOOK_COVER_PATH . '/' . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $newFileName];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file'];
}

// Get system setting
function getSystemSetting($key, $default = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        error_log("Error getting system setting: " . $e->getMessage());
        return $default;
    }
}

// Update system setting
function updateSystemSetting($key, $value) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
        return $stmt->execute([$value, $key]);
    } catch (PDOException $e) {
        error_log("Error updating system setting: " . $e->getMessage());
        return false;
    }
}

// Calculate days between two dates
function daysBetween($date1, $date2) {
    $d1 = new DateTime($date1);
    $d2 = new DateTime($date2);
    $interval = $d1->diff($d2);
    return $interval->format('%r%a');
}


// Send notification to user
function sendNotification($userId, $message) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, message, status) VALUES (?, ?, 'unread')");
        return $stmt->execute([$userId, $message]);
    } catch (PDOException $e) {
        error_log("Send notification error: " . $e->getMessage());
        return false;
    }
}

// Get unread notification count
function getUnreadNotificationCount($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND status = 'unread'");
        $stmt->execute([$userId]);
        return $stmt->fetch()['total'];
    } catch (PDOException $e) {
        error_log("Get notification count error: " . $e->getMessage());
        return 0;
    }
}
