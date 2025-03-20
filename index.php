<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
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

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $userType = $_POST['user_type'];

        // Staff Login (Admin, Dispatcher, Conductor)
        if ($userType === 'staff') {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                // Log successful login
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $stmt = $pdo->prepare("INSERT INTO audit_logs (timestamp, user_id, ip_address, action, module, details) 
                                     VALUES (NOW(), ?, ?, 'LOGIN', 'Authentication', 'Staff logged in successfully')");
                $stmt->execute([$user['id'], $ip_address]);

                switch ($user['role']) {
                    case 'admin':
                        header("Location: admin/");
                        break;
                    case 'dispatcher':
                        header("Location: dispatcher/");
                        break;
                    case 'conductor':
                        header("Location: conductor/");
                        break;
                }
                exit();
            } else {
                $error = "Invalid staff credentials!";
            }
        }
        // Passenger Login
        else if ($userType === 'passenger') {
            $stmt = $pdo->prepare("SELECT * FROM passengers WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = 'passenger';
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];

                header("Location: passenger/");
                exit();
            } else {
                $error = "Invalid passenger credentials!";
            }
        }

        // Log failed login attempt
        if ($error) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $stmt = $pdo->prepare("INSERT INTO audit_logs (timestamp, user_id, ip_address, action, module, details) 
                                 VALUES (NOW(), NULL, ?, 'LOGIN_FAILED', 'Authentication', ?)");
            $stmt->execute([$ip_address, "Failed login attempt for email: $email"]);
        }

    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "System error. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Bus Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .login-type-selector {
            margin-bottom: 20px;
        }
        .login-form {
            display: none;
        }
        .login-form.active {
            display: block;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <!-- Login Type Selector -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <h4 class="mb-3">Select Login Type</h4>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-primary" onclick="showLoginForm('staff')">Staff Login</button>
                            <button type="button" class="btn btn-secondary" onclick="showLoginForm('passenger')">Passenger Login</button>
                        </div>
                    </div>
                </div>

                <!-- Staff Login Form -->
                <div id="staffLogin" class="card login-form">
                    <div class="card-header">
                        <h3 class="text-center mb-0">Staff Login</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error && isset($_POST['user_type']) && $_POST['user_type'] === 'staff'): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="user_type" value="staff">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary w-100">Login as Staff</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Passenger Login Form -->
                <div id="passengerLogin" class="card login-form">
                    <div class="card-header">
                        <h3 class="text-center mb-0">Passenger Login</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error && isset($_POST['user_type']) && $_POST['user_type'] === 'passenger'): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="user_type" value="passenger">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary w-100">Login as Passenger</button>
                            </div>
                            <div class="text-center">
                                <a href="register.php" class="text-decoration-none">Don't have an account? Register here</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showLoginForm(type) {
            // Hide all forms
            document.querySelectorAll('.login-form').forEach(form => {
                form.classList.remove('active');
            });
            
            // Show selected form
            document.getElementById(type + 'Login').classList.add('active');
        }

        // Show staff login by default
        document.addEventListener('DOMContentLoaded', function() {
            showLoginForm('staff');
        });
    </script>
</body>
</html> 