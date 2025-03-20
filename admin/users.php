<?php
$pageTitle = 'Manage Users';
$currentPage = 'users';
require_once 'includes/header.php';
checkAdminAuth();

// Handle Delete Action
if (isset($_POST['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'dispatcher'");
        $stmt->execute([$_POST['delete_id']]);
        
        // Log the action
        $stmt = $pdo->prepare("INSERT INTO audit_logs (timestamp, user_id, ip_address, action, module, details) 
                              VALUES (NOW(), ?, ?, 'DELETE', 'Users', 'Dispatcher account deleted')");
        $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
        
        $success_message = "User deleted successfully!";
    } catch(PDOException $e) {
        $error_message = "Error deleting user: " . $e->getMessage();
    }
}

// Handle Add/Edit User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        $firstname = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING);
        $lastname = filter_input(INPUT_POST, 'lastname', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

        if ($_POST['action'] === 'add') {
            // Validate password
            if (strlen($password) < 6) {
                throw new Exception("Password must be at least 6 characters long!");
            }

            // Check email uniqueness
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Email already exists!");
            }

            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, email, phone, password, role, status) 
                                  VALUES (?, ?, ?, ?, ?, 'dispatcher', ?)");
            $stmt->execute([$firstname, $lastname, $email, $phone, $hashedPassword, $status]);
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO audit_logs (timestamp, user_id, ip_address, action, module, details) 
                                  VALUES (NOW(), ?, ?, 'CREATE', 'Users', ?)");
            $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], "New dispatcher account created: $email"]);
            
            $success_message = "User added successfully!";

        } elseif ($_POST['action'] === 'edit') {
            $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            
            // Check email uniqueness
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Email already exists!");
            }

            // Update user
            $stmt = $pdo->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, 
                                  phone = ?, status = ? WHERE id = ? AND role = 'dispatcher'");
            $stmt->execute([$firstname, $lastname, $email, $phone, $status, $userId]);
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO audit_logs (timestamp, user_id, ip_address, action, module, details) 
                                  VALUES (NOW(), ?, ?, 'UPDATE', 'Users', ?)");
            $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], "Dispatcher account updated: $email"]);
            
            $success_message = "User updated successfully!";
        }
    } catch(Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Reset Password
if (isset($_POST['reset_password'])) {
    try {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        
        // Generate a new random password
        $newPassword = bin2hex(random_bytes(8)); // 16 characters long
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'dispatcher'");
        $stmt->execute([$hashedPassword, $userId]);
        
        // Log the action
        $stmt = $pdo->prepare("INSERT INTO audit_logs (timestamp, user_id, ip_address, action, module, details) 
                              VALUES (NOW(), ?, ?, 'UPDATE', 'Users', 'Password reset performed')");
        $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
        
        $success_message = "Password reset successfully! New password: $newPassword";
    } catch(PDOException $e) {
        $error_message = "Error resetting password: " . $e->getMessage();
    }
}

// Fetch all dispatcher users
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'dispatcher' ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching users: " . $e->getMessage();
    $users = [];
}
?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-6">
                <h2>Manage Dispatchers</h2>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class='bx bx-plus'></i> Add New Dispatcher
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
                    <table id="usersTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($user['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-warning edit-user" 
                                                data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                data-user='<?php echo json_encode($user); ?>'>
                                            <i class='bx bx-edit'></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-info reset-password"
                                                data-bs-toggle="modal" data-bs-target="#resetPasswordModal"
                                                data-id="<?php echo $user['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>">
                                            <i class='bx bx-key'></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-user" 
                                                data-id="<?php echo $user['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>">
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Dispatcher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="firstname" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="lastname" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" class="form-control" id="password" 
                                   required minlength="6">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class='bx bx-show'></i>
                            </button>
                        </div>
                        <small class="text-muted">Password must be at least 6 characters long</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addUserForm" class="btn btn-primary">Add Dispatcher</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Dispatcher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="firstname" id="editFirstName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="lastname" id="editLastName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="editEmail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" id="editPhone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="editStatus" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editUserForm" class="btn btn-primary">Update Dispatcher</button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reset the password for <span id="resetUserName"></span>?</p>
                <p>A new random password will be generated.</p>
                <form id="resetPasswordForm" method="POST">
                    <input type="hidden" name="reset_password" value="1">
                    <input type="hidden" name="user_id" id="resetUserId">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="resetPasswordForm" class="btn btn-warning">Reset Password</button>
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
                Are you sure you want to delete the dispatcher account for <span id="deleteUserName"></span>?
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
// Add custom scripts
$customScripts = <<<SCRIPTS
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#usersTable').DataTable({
        order: [[5, 'desc']]
    });

    // Handle edit button click
    $('.edit-user').click(function() {
        const user = $(this).data('user');
        $('#editUserId').val(user.id);
        $('#editFirstName').val(user.firstname);
        $('#editLastName').val(user.lastname);
        $('#editEmail').val(user.email);
        $('#editPhone').val(user.phone);
        $('#editStatus').val(user.status);
    });

    // Handle reset password button click
    $('.reset-password').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        $('#resetUserId').val(id);
        $('#resetUserName').text(name);
    });

    // Handle delete button click
    $('.delete-user').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        $('#deleteId').val(id);
        $('#deleteUserName').text(name);
        $('#deleteModal').modal('show');
    });

    // Toggle password visibility
    $('#togglePassword').click(function() {
        const passwordInput = $('#password');
        const icon = $(this).find('i');
        
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            icon.removeClass('bx-show').addClass('bx-hide');
        } else {
            passwordInput.attr('type', 'password');
            icon.removeClass('bx-hide').addClass('bx-show');
        }
    });
});
</script>
SCRIPTS;

require_once 'includes/footer.php';
?> 