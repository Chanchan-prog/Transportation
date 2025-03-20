<?php
$pageTitle = 'Manage Drivers';
$currentPage = 'drivers';
require_once 'includes/header.php';
checkAdminAuth();

// Handle Delete Action
if (isset($_POST['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM drivers WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $success_message = "Driver deleted successfully!";
    } catch(PDOException $e) {
        $error_message = "Error deleting driver: " . $e->getMessage();
    }
}

// Handle Add/Edit Driver
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $licenseNumber = filter_input(INPUT_POST, 'license_number', FILTER_SANITIZE_STRING);
        $contactNumber = filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_STRING);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
        $isConductor = isset($_POST['is_conductor']) ? 1 : 0;

        if ($_POST['action'] === 'add') {
            // Check license number uniqueness
            $stmt = $pdo->prepare("SELECT id FROM drivers WHERE license_number = ?");
            $stmt->execute([$licenseNumber]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("License number already exists!");
            }

            // Insert new driver
            $stmt = $pdo->prepare("INSERT INTO drivers (name, license_number, contact_number, address, status, is_conductor) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $licenseNumber, $contactNumber, $address, $status, $isConductor]);
            $success_message = "Driver added successfully!";

        } elseif ($_POST['action'] === 'edit') {
            $id = filter_input(INPUT_POST, 'driver_id', FILTER_VALIDATE_INT);
            
            // Check license number uniqueness for other drivers
            $stmt = $pdo->prepare("SELECT id FROM drivers WHERE license_number = ? AND id != ?");
            $stmt->execute([$licenseNumber, $id]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("License number already exists!");
            }

            // Update driver
            $stmt = $pdo->prepare("UPDATE drivers SET name = ?, license_number = ?, contact_number = ?, 
                                 address = ?, status = ?, is_conductor = ? WHERE id = ?");
            $stmt->execute([$name, $licenseNumber, $contactNumber, $address, $status, $isConductor, $id]);
            $success_message = "Driver updated successfully!";
        }

        // Log the action
        $action = $_POST['action'] === 'add' ? 'CREATE' : 'UPDATE';
        $stmt = $pdo->prepare("INSERT INTO audit_logs (timestamp, user_id, ip_address, action, module, details) 
                              VALUES (NOW(), ?, ?, ?, 'Drivers', ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $_SERVER['REMOTE_ADDR'],
            $action,
            "Driver {$action}: {$name}"
        ]);

    } catch(Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch all drivers
try {
    $stmt = $pdo->query("SELECT * FROM drivers ORDER BY created_at DESC");
    $drivers = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Error fetching drivers: " . $e->getMessage();
    $drivers = [];
}
?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-6">
                <h2>Manage Drivers</h2>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDriverModal">
                    <i class='bx bx-plus'></i> Add New Driver
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
                    <table id="driversTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>License Number</th>
                                <th>Contact Number</th>
                                <th>Status</th>
                                <th>Role</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($drivers as $driver): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($driver['id']); ?></td>
                                <td><?php echo htmlspecialchars($driver['name']); ?></td>
                                <td><?php echo htmlspecialchars($driver['license_number']); ?></td>
                                <td><?php echo htmlspecialchars($driver['contact_number']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $driver['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($driver['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo $driver['is_conductor'] ? 'Conductor' : 'Driver'; ?></td>
                                <td><?php echo date('M d, Y', strtotime($driver['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info view-driver" 
                                                data-bs-toggle="modal" data-bs-target="#viewDriverModal"
                                                data-driver='<?php echo json_encode($driver); ?>'>
                                            <i class='bx bx-show'></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning edit-driver" 
                                                data-bs-toggle="modal" data-bs-target="#editDriverModal"
                                                data-driver='<?php echo json_encode($driver); ?>'>
                                            <i class='bx bx-edit'></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-driver" 
                                                data-id="<?php echo $driver['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($driver['name']); ?>">
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

<!-- Add Driver Modal -->
<div class="modal fade" id="addDriverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addDriverForm" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">License Number</label>
                        <input type="text" name="license_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_conductor" class="form-check-input" id="addIsConductor">
                            <label class="form-check-label" for="addIsConductor">Is Conductor</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addDriverForm" class="btn btn-primary">Add Driver</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Driver Modal -->
<div class="modal fade" id="editDriverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editDriverForm" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="driver_id" id="editDriverId">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">License Number</label>
                        <input type="text" name="license_number" id="editLicenseNumber" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_number" id="editContactNumber" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="editAddress" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="editStatus" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_conductor" class="form-check-input" id="editIsConductor">
                            <label class="form-check-label" for="editIsConductor">Is Conductor</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editDriverForm" class="btn btn-primary">Update Driver</button>
            </div>
        </div>
    </div>
</div>

<!-- View Driver Modal -->
<div class="modal fade" id="viewDriverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Driver Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table">
                    <tr>
                        <th width="150">Name</th>
                        <td id="viewName"></td>
                    </tr>
                    <tr>
                        <th>License Number</th>
                        <td id="viewLicenseNumber"></td>
                    </tr>
                    <tr>
                        <th>Contact Number</th>
                        <td id="viewContactNumber"></td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td id="viewAddress"></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td id="viewStatus"></td>
                    </tr>
                    <tr>
                        <th>Role</th>
                        <td id="viewRole"></td>
                    </tr>
                    <tr>
                        <th>Created Date</th>
                        <td id="viewCreatedDate"></td>
                    </tr>
                </table>
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
                Are you sure you want to delete this driver: <span id="driverName"></span>?
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
    $('#driversTable').DataTable({
        "order": [[0, "desc"]]
    });

    // Handle edit button click
    $('.edit-driver').click(function() {
        const driver = $(this).data('driver');
        $('#editDriverId').val(driver.id);
        $('#editName').val(driver.name);
        $('#editLicenseNumber').val(driver.license_number);
        $('#editContactNumber').val(driver.contact_number);
        $('#editAddress').val(driver.address);
        $('#editStatus').val(driver.status);
        $('#editIsConductor').prop('checked', driver.is_conductor == 1);
    });

    // Handle view button click
    $('.view-driver').click(function() {
        const driver = $(this).data('driver');
        $('#viewName').text(driver.name);
        $('#viewLicenseNumber').text(driver.license_number);
        $('#viewContactNumber').text(driver.contact_number);
        $('#viewAddress').text(driver.address);
        $('#viewStatus').html('<span class="badge bg-' + (driver.status === 'active' ? 'success' : 'danger') + '">' + 
                            driver.status.charAt(0).toUpperCase() + driver.status.slice(1) + '</span>');
        $('#viewRole').text(driver.is_conductor == 1 ? 'Conductor' : 'Driver');
        $('#viewCreatedDate').text(new Date(driver.created_at).toLocaleDateString());
    });

    // Handle delete button click
    $('.delete-driver').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        $('#deleteId').val(id);
        $('#driverName').text(name);
        $('#deleteModal').modal('show');
    });
});
</script>
SCRIPTS;

require_once 'includes/footer.php';
?> 