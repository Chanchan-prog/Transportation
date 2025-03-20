<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'dbbus');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('SITE_NAME', 'Bus Reservation System');
define('ADMIN_EMAIL', 'admin@example.com');

// Path configuration
define('BASE_URL', 'http://localhost/your-project');
define('UPLOAD_PATH', dirname(dirname(__DIR__)) . '/uploads/');

// Initialize database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Session handling
session_start();

// Check admin authentication
function checkAdminAuth() {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        header("Location: ../index.php");
        exit();
    }
}

// Utility functions
function getPageTitle($module = '') {
    return SITE_NAME . ($module ? " - $module" : '');
}
?> 