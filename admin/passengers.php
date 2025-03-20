<?php
$pageTitle = 'Manage Passengers';
$currentPage = 'passengers';
require_once 'includes/header.php';
checkAdminAuth();

// Handle Delete Action
if (isset($_POST['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM passengers WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $success_message = "Passenger deleted successfully!";
    } catch(PDOException $e) {
        $error_message = "Error deleting passenger: " . $e->getMessage();
    }
}

// Handle Add/Edit Passenger
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
        $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);

        if ($_POST['action'] === 'add') {
            // Check email uniqueness
            $stmt = $pdo->prepare("SELECT id FROM passengers WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Email already exists!");
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert new passenger
            $stmt = $pdo->prepare("INSERT INTO passengers (first_name, last_name, email, password, age, gender, address) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $age, $gender, $address]);
            $success_message = "Passenger added successfully!";

        } elseif ($_POST['action'] === 'edit') {
            $id = filter_input(INPUT_POST, 'passenger_id', FILTER_VALIDATE_INT);
            
            // Update passenger
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE passengers SET first_name = ?, last_name = ?, email = ?, 
                                     password = ?, age = ?, gender = ?, address = ? WHERE id = ?");
                $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $age, $gender, $address, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE passengers SET first_name = ?, last_name = ?, email = ?, 
                                     age = ?, gender = ?, address = ? WHERE id = ?");
                $stmt->execute([$firstName, $lastName, $email, $age, $gender, $address, $id]);
            }
            $success_message = "Passenger updated successfully!";
        }
    } catch(Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch all passengers
try {
    $stmt = $pdo->query("SELECT * FROM passengers ORDER BY created_at DESC");
    $passengers = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Error fetching passengers: " . $e->getMessage();
    $passengers = [];
}
?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-6">
                <h2>Manage Passengers</h2>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPassengerModal">
                    <i class='bx bx-plus'></i> Add New Passenger
                </button>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="passengersTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Profile</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($passengers as $passenger): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($passenger['id']); ?></td>
                                <td>
                                    <?php if ($passenger['profile_picture']): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($passenger['profile_picture']); ?>" 
                                             class="rounded-circle" width="40" height="40" 
                                             alt="Profile Picture">
                                    <?php else: ?>
                                        <i class='bx bxs-user-circle' style='font-size: 40px'></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($passenger['first_name'] . ' ' . $passenger['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($passenger['email']); ?></td>
                                <td><?php echo htmlspecialchars($passenger['age']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($passenger['gender'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($passenger['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info view-passenger" 
                                                data-bs-toggle="modal" data-bs-target="#viewPassengerModal"
                                                data-passenger='<?php echo json_encode($passenger); ?>'>
                                            <i class='bx bx-show'></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning edit-passenger" 
                                                data-bs-toggle="modal" data-bs-target="#editPassengerModal"
                                                data-passenger='<?php echo json_encode($passenger); ?>'>
                                            <i class='bx bx-edit'></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-passenger" 
                                                data-id="<?php echo $passenger['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($passenger['first_name'] . ' ' . $passenger['last_name']); ?>">
                                            <i class='bx bx-trash'></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Passenger Modal -->
<div class="modal fade" id="addPassengerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Passenger</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addPassengerForm" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Age</label>
                            <input type="number" name="age" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addPassengerForm" class="btn btn-primary">Add Passenger</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Passenger Modal -->
<div class="modal fade" id="editPassengerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Passenger</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editPassengerForm" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="passenger_id" id="editPassengerId">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" id="editFirstName" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="editLastName" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="editEmail" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password (leave blank to keep current)</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Age</label>
                            <input type="number" name="age" id="editAge" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" id="editGender" class="form-select" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="editAddress" class="form-control" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editPassengerForm" class="btn btn-primary">Update Passenger</button>
            </div>
        </div>
    </div>
</div>

<!-- View Passenger Modal -->
<div class="modal fade" id="viewPassengerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Passenger Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-4">
                        <div id="viewProfilePicture">
                            <i class='bx bxs-user-circle' style='font-size: 150px'></i>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <table class="table">
                            <tr>
                                <th width="150">Name</th>
                                <td id="viewName"></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td id="viewEmail"></td>
                            </tr>
                            <tr>
                                <th>Age</th>
                                <td id="viewAge"></td>
                            </tr>
                            <tr>
                                <th>Gender</th>
                                <td id="viewGender"></td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td id="viewAddress"></td>
                            </tr>
                            <tr>
                                <th>Registration Date</th>
                                <td id="viewRegistrationDate"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this passenger: <span id="passengerName"></span>?
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="delete_id" id="deleteId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Add custom scripts before footer
$customScripts = <<<SCRIPTS
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#passengersTable').DataTable({
        "order": [[0, "desc"]]
    });

    // Handle edit button click
    $('.edit-passenger').click(function() {
        const passenger = $(this).data('passenger');
        $('#editPassengerId').val(passenger.id);
        $('#editFirstName').val(passenger.first_name);
        $('#editLastName').val(passenger.last_name);
        $('#editEmail').val(passenger.email);
        $('#editAge').val(passenger.age);
        $('#editGender').val(passenger.gender);
        $('#editAddress').val(passenger.address);
    });

    // Handle view button click
    $('.view-passenger').click(function() {
        const passenger = $(this).data('passenger');
        $('#viewName').text(passenger.first_name + ' ' + passenger.last_name);
        $('#viewEmail').text(passenger.email);
        $('#viewAge').text(passenger.age);
        $('#viewGender').text(passenger.gender.charAt(0).toUpperCase() + passenger.gender.slice(1));
        $('#viewAddress').text(passenger.address);
        $('#viewRegistrationDate').text(new Date(passenger.created_at).toLocaleDateString());
        
        if (passenger.profile_picture) {
            $('#viewProfilePicture').html('<img src="../uploads/' + passenger.profile_picture + '" class="img-fluid rounded" alt="Profile Picture">');
        } else {
            $('#viewProfilePicture').html('<i class="bx bxs-user-circle" style="font-size: 150px"></i>');
        }
    });

    // Handle delete button click
    $('.delete-passenger').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        $('#deleteId').val(id);
        $('#passengerName').text(name);
        $('#deleteModal').modal('show');
    });
});
</script>
SCRIPTS;

require_once 'includes/footer.php';
?> 