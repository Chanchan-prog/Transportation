<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Get passenger ID from URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: passengers.php");
    exit();
}

// Fetch passenger data
try {
    $stmt = $pdo->prepare("SELECT * FROM passengers WHERE id = ?");
    $stmt->execute([$id]);
    $passenger = $stmt->fetch();
    
    if (!$passenger) {
        header("Location: passengers.php");
        exit();
    }
} catch(PDOException $e) {
    die("Error fetching passenger: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate input
        $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
        $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);

        // Check email uniqueness if changed
        if ($email !== $passenger['email']) {
            $stmt = $pdo->prepare("SELECT id FROM passengers WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Email already exists!");
            }
        }

        // Handle file uploads
        $profilePicture = $passenger['profile_picture'];
        $validId = $passenger['valid_id'];

        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $profilePicture = uploadFile($_FILES['profile_picture'], '../uploads/');
            // Delete old file
            if ($passenger['profile_picture']) {
                @unlink('../uploads/' . $passenger['profile_picture']);
            }
        }

        if (isset($_FILES['valid_id']) && $_FILES['valid_id']['error'] == 0) {
            $validId = uploadFile($_FILES['valid_id'], '../uploads/');
            // Delete old file
            if ($passenger['valid_id']) {
                @unlink('../uploads/' . $passenger['valid_id']);
            }
        }

        // Update password if provided
        $passwordSQL = '';
        $params = [$firstName, $lastName, $email, $age, $gender, $address, $profilePicture, $validId];
        
        if (!empty($_POST['password'])) {
            $passwordSQL = ', password = ?';
            $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        $params[] = $id;

        // Update database
        $stmt = $pdo->prepare("UPDATE passengers SET 
                              first_name = ?, last_name = ?, email = ?, 
                              age = ?, gender = ?, address = ?,
                              profile_picture = ?, valid_id = ?" . $passwordSQL . "
                              WHERE id = ?");
        $stmt->execute($params);

        header("Location: passengers.php?success=2");
        exit();

    } catch(Exception $e) {
        $error_message = $e->getMessage();
    }
}

// File upload helper function
function uploadFile($file, $targetDir) {
    $fileName = uniqid() . '_' . basename($file['name']);
    $targetPath = $targetDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Error uploading file.");
    }
    
    return $fileName;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Passenger - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <!-- Include your admin CSS -->
</head>
<body class="bg-light">
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Your sidebar content -->
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-md-12">
                    <h2>Edit Passenger</h2>
                </div>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($passenger['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($passenger['last_name']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($passenger['email']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password (leave blank to keep current)</label>
                                <input type="password" name="password" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Age</label>
                                <input type="number" name="age" class="form-control" 
                                       value="<?php echo htmlspecialchars($passenger['age']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select" required>
                                    <option value="male" <?php echo $passenger['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo $passenger['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo $passenger['gender'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($passenger['address']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Profile Picture</label>
                                <?php if ($passenger['profile_picture']): ?>
                                    <div class="mb-2">
                                        <img src="../uploads/<?php echo htmlspecialchars($passenger['profile_picture']); ?>" 
                                             class="img-thumbnail" width="100" alt="Current Profile Picture">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="profile_picture" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Valid ID</label>
                                <?php if ($passenger['valid_id']): ?>
                                    <div class="mb-2">
                                        <img src="../uploads/<?php echo htmlspecialchars($passenger['valid_id']); ?>" 
                                             class="img-thumbnail" width="100" alt="Current Valid ID">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="valid_id" class="form-control" accept="image/*">
                            </div>
                        </div>

                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">Update Passenger</button>
                            <a href="passengers.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 