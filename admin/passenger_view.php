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
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Passenger - Admin Dashboard</title>
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
                <div class="col-md-6">
                    <h2>Passenger Details</h2>
                </div>
                <div class="col-md-6 text-end">
                    <a href="passenger_edit.php?id=<?php echo $passenger['id']; ?>" class="btn btn-warning">
                        <i class='bx bx-edit'></i> Edit
                    </a>
                    <a href="passengers.php" class="btn btn-secondary">
                        <i class='bx bx-arrow-back'></i> Back
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-4">
                            <?php if ($passenger['profile_picture']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($passenger['profile_picture']); ?>" 
                                     class="img-fluid rounded" alt="Profile Picture">
                            <?php else: ?>
                                <i class='bx bxs-user-circle' style='font-size: 150px'></i>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <table class="table">
                                <tr>
                                    <th width="150">Name</th>
                                    <td><?php echo htmlspecialchars($passenger['first_name'] . ' ' . $passenger['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?php echo htmlspecialchars($passenger['email']); ?></td>
                                </tr>
                                <tr>
                                    <th>Age</th>
                                    <td><?php echo htmlspecialchars($passenger['age']); ?></td>
                                </tr>
                                <tr>
                                    <th>Gender</th>
                                    <td><?php echo htmlspecialchars(ucfirst($passenger['gender'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td><?php echo htmlspecialchars($passenger['address']); ?></td>
                                </tr>
                                <tr>
                                    <th>Registration Date</th>
                                    <td><?php echo date('F d, Y', strtotime($passenger['created_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <?php if ($passenger['valid_id']): ?>
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h4>Valid ID</h4>
                            <img src="../uploads/<?php echo htmlspecialchars($passenger['valid_id']); ?>" 
                                 class="img-fluid" alt="Valid ID">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 