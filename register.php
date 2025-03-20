<?php
session_start();
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
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Sanitize and validate input
        $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
        $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password']; // Raw password for hashing
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
        $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM passengers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Email already exists!");
        }

        // Validate password strength
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Handle file uploads
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Process profile picture
        $profilePicture = '';
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['profile_picture']['type'], $allowedTypes)) {
                throw new Exception("Invalid profile picture format. Only JPG, PNG, and GIF are allowed.");
            }
            $profilePicture = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadDir . $profilePicture);
        }

        // Process valid ID
        $validId = '';
        if (isset($_FILES['valid_id']) && $_FILES['valid_id']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['valid_id']['type'], $allowedTypes)) {
                throw new Exception("Invalid ID format. Only JPG, PNG, and GIF are allowed.");
            }
            $validId = uniqid() . '_' . basename($_FILES['valid_id']['name']);
            move_uploaded_file($_FILES['valid_id']['tmp_name'], $uploadDir . $validId);
        }

        // Begin transaction
        $pdo->beginTransaction();

        // Insert into passengers table
        $stmt = $pdo->prepare("INSERT INTO passengers (first_name, last_name, email, password, age, gender, address, profile_picture, valid_id) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $firstName,
            $lastName,
            $email,
            $hashedPassword, // Store the hashed password
            $age,
            $gender,
            $address,
            $profilePicture,
            $validId
        ]);

        // Get the new passenger ID
        $passengerId = $pdo->lastInsertId();

        // Insert into passenger_registrations table
        $stmt = $pdo->prepare("INSERT INTO passenger_registrations (passenger_id, registration_date, registration_time) 
                              VALUES (?, CURDATE(), CURTIME())");
        $stmt->execute([$passengerId]);

        // Commit transaction
        $pdo->commit();

        $success = "Registration successful! You can now login.";
        
        // Redirect to login page after 3 seconds
        header("refresh:3;url=index.php");

    } catch(Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
        
        // Delete uploaded files if registration failed
        if (!empty($profilePicture) && file_exists($uploadDir . $profilePicture)) {
            unlink($uploadDir . $profilePicture);
        }
        if (!empty($validId) && file_exists($uploadDir . $validId)) {
            unlink($uploadDir . $validId);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Bus Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .password-requirements {
            font-size: 0.8em;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Passenger Registration</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>First Name</label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required 
                                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" 
                                       title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                                <div class="password-requirements">
                                    Password must contain:
                                    <ul>
                                        <li>At least 8 characters</li>
                                        <li>At least one uppercase letter</li>
                                        <li>At least one lowercase letter</li>
                                        <li>At least one number</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Age</label>
                                    <input type="number" name="age" class="form-control" required min="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Gender</label>
                                    <select name="gender" class="form-control" required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label>Address</label>
                                <textarea name="address" class="form-control" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label>Profile Picture</label>
                                <input type="file" name="profile_picture" class="form-control" required 
                                       accept="image/jpeg,image/png,image/gif">
                            </div>
                            <div class="mb-3">
                                <label>Valid ID</label>
                                <input type="file" name="valid_id" class="form-control" required 
                                       accept="image/jpeg,image/png,image/gif">
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary w-100">Register</button>
                            </div>
                            <div class="text-center">
                                <a href="index.php">Already have an account? Login here</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Client-side password validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]').value;
            const passwordRegex = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/;
            
            if (!passwordRegex.test(password)) {
                e.preventDefault();
                alert('Password must contain at least 8 characters, including uppercase, lowercase, and numbers');
            }
        });
    </script>
</body>
</html> 