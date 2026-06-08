<?php
/**
 * Login Page
 * Library Management System
 */

require_once '../config/config.php';

// Redirect if already logged in
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
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                SELECT id, full_name, email, username, password, role, status 
                FROM users 
                WHERE (username = ? OR email = ?) AND status = 'active'
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                
                // Remember me functionality
                if ($remember) {
                    setcookie('remember_user', $user['username'], time() + (86400 * 30), '/');
                }
                
                // Redirect based on role
                switch ($user['role']) {
                    case 'super_admin':
                        redirect(SITE_URL . '/admin/dashboard.php');
                        break;
                    case 'librarian':
                        redirect(SITE_URL . '/librarian/dashboard.php');
                        break;
                    case 'student':
                        redirect(SITE_URL . '/student/dashboard.php');
                        break;
                    default:
                        redirect(SITE_URL . '/auth/login.php');
                }
            } else {
                $error = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'Login failed. Please try again later.';
        }
    }
}

// Get remembered username
$rememberedUser = $_COOKIE['remember_user'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-book-reader"></i>
                </div>
                <h1><?php echo SITE_NAME; ?></h1>
                <p>Please login to your account</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>
            
            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <i class="fas fa-user form-icon"></i>
                    <input 
                        type="text" 
                        name="username" 
                        class="form-control" 
                        placeholder="Username or Email"
                        value="<?php echo htmlspecialchars($rememberedUser); ?>"
                        required
                        autofocus
                    >
                </div>
                
                <div class="form-group">
                    <i class="fas fa-lock form-icon"></i>
                    <input 
                        type="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Password"
                        required
                    >
                </div>
                
                <div class="remember-forgot">
                    <div class="remember-me">
                        <input type="checkbox" name="remember" id="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="#" class="forgot-password">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
            </form>
            
            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                <p style="margin-top: 10px; font-size: 0.85rem;">
                    <strong>Demo Accounts:</strong><br>
                    Admin: admin / admin123<br>
                    Librarian: librarian / librarian123<br>
                    Student: student / student123
                </p>
            </div>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_PATH; ?>/js/main.js"></script>
</body>
</html>
