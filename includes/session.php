<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function redirectBasedOnUserType() {
    if (!isLoggedIn()) {
        return;
    }

    $currentPath = $_SERVER['PHP_SELF'];
    $isLoginPage = strpos($currentPath, 'index.php') !== false && dirname($currentPath) === dirname($_SERVER['DOCUMENT_ROOT']);
    $isRegisterPage = strpos($currentPath, 'register.php') !== false;

    if ($isLoginPage || $isRegisterPage) {
        switch ($_SESSION['user_type']) {
            case 'admin':
                header("Location: admin/");
                break;
            case 'dispatcher':
                header("Location: dispatcher/");
                break;
            case 'conductor':
                header("Location: conductor/");
                break;
            case 'passenger':
                header("Location: passenger/");
                break;
        }
        exit();
    }
}

function checkUserAccess($requiredRole) {
    if (!isLoggedIn()) {
        header("Location: ../index.php");
        exit();
    }

    if ($_SESSION['user_type'] !== $requiredRole) {
        header("Location: ../unauthorized.php");
        exit();
    }
}

// Set session cookie parameters for better security
$secure = true; // Set to true if using HTTPS
$httponly = true;
$samesite = 'Strict';
$lifetime = 3600; // 1 hour session lifetime

session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => $httponly,
    'samesite' => $samesite
]);

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} else if (time() - $_SESSION['last_regeneration'] > 300) { // Regenerate every 5 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
?> 