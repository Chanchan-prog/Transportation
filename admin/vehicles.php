<?php
$pageTitle = 'Manage Vehicles';
$currentPage = 'vehicles';
require_once 'includes/header.php';
checkAdminAuth();

// Handle Delete Action
if (isset($_POST['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM donsals WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        
        // Log the action
        $stmt = $pdo->prepare("INSERT INTO audit_logs (timestamp, user_id, ip_address, action, module, details) 
                              VALUES (NOW(), ?, ?, 'DELETE', 'Vehicles', 'Vehicle deleted')");
        $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
        
        $success_message = "Vehicle deleted successfully!";
    } catch(PDOException $e) {
        $error_message = "Error deleting vehicle: " . $e->getMessage();
    }
}

// Handle Add/Edit Vehicle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        $vehicleNumber = filter_input(INPUT_POST, 'vehicle_number', FILTER_SANITIZE_STRING);
        $plateNumber = filter_input(INPUT_POST, 'plate_number', FILTER_SANITIZE_STRING);
        $driverId = filter_input(INPUT_POST, 'driver_id', FILTER_VALIDATE_INT);
        $routeId = filter_input(INPUT_POST, 'route_id', FILTER_VALIDATE_INT);
        $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

        if ($_POST['action'] === 'add') {
            // Check vehicle number uniqueness
            $stmt = $pdo->prepare("SELECT id FROM donsals WHERE vehicle_number = ?");
            $stmt->execute([$vehicleNumber]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Vehicle number already exists!");
            }

            // Insert new vehicle
            $stmt = $pdo->prepare("INSERT INTO donsals (vehicle_number, plate_number, driver_id, route_id, 
                                  capacity, available_seats, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$vehicleNumber, $plateNumber, $driverId, $routeId, $capacity, $capacity, $status]);
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO audit_logs (timestamp, user_id, ip_address, action, module, details) 
                                  VALUES (NOW(), ?, ?, 'CREATE', 'Vehicles', ?)");
            $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], "New vehicle added: $vehicleNumber"]);
            
            $success_message = "Vehicle added successfully!";

        } elseif ($_POST['action'] === 'edit') {
            $id = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
            
            // Check vehicle number uniqueness
            $stmt = $pdo->prepare("SELECT id FROM donsals WHERE vehicle_number = ? AND id != ?");
            $stmt->execute([$vehicleNumber, $id]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Vehicle number already exists!");
            }

            // Update vehicle
            $stmt = $pdo->prepare("UPDATE donsals SET vehicle_number = ?, plate_number = ?, driver_id = ?, 
                                  route_id = ?, capacity = ?, status = ? WHERE id = ?");
            $stmt->execute([$vehicleNumber, $plateNumber, $driverId, $routeId, $capacity, $status, $id]);
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO audit_logs (timestamp, user_id, ip_address, action, module, details) 
                                  VALUES (NOW(), ?, ?, 'UPDATE', 'Vehicles', ?)");
            $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], "Vehicle updated: $vehicleNumber"]);
            
            $success_message = "Vehicle updated successfully!";
        }
    } catch(Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch all vehicles with driver and route information
try {
    $stmt = $pdo->query("SELECT d.*, dr.name as driver_name, r.route_name 
                         FROM donsals d 
                         LEFT JOIN drivers dr ON d.driver_id = dr.id 
                         LEFT JOIN routes r ON d.route_id = r.id 
                         ORDER BY d.created_at DESC");
    $vehicles = $stmt->fetchAll();

    // Fetch drivers for dropdown
    $stmt = $pdo->query("SELECT id, name FROM drivers WHERE status = 'active' ORDER BY name");
    $drivers = $stmt->fetchAll();

    // Fetch routes for dropdown
    $stmt = $pdo->query("SELECT id, route_name FROM routes WHERE status = 'active' ORDER BY route_name");
    $routes = $stmt->fetchAll();

} catch(PDOException $e) {
    $error_message = "Error fetching data: " . $e->getMessage();
    $vehicles = [];
    $drivers = [];
    $routes = [];
}
?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-6">
                <h2>Manage Vehicles</h2>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                    <i class='bx bx-plus'></i> Add New Vehicle
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
                    <table id="vehiclesTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Vehicle Number</th>
                                <th>Plate Number</th>
                                <th>Driver</th>
                                <th>Route</th>
                                <th>Capacity</th>
                                <th>Available Seats</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicles as $vehicle): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($vehicle['id']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['vehicle_number']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['plate_number']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['driver_name'] ?? 'Not Assigned'); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['route_name'] ?? 'Not Assigned'); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['capacity']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['available_seats']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($vehicle['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($vehicle['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info view-vehicle" 
                                                data-bs-toggle="modal" data-bs-target="#viewVehicleModal"
                                                data-vehicle='<?php echo json_encode($vehicle); ?>'>
                                            <i class='bx bx-show'></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning edit-vehicle" 
                                                data-bs-toggle="modal" data-bs-target="#editVehicleModal"
                                                data-vehicle='<?php echo json_encode($vehicle); ?>'>
                                            <i class='bx bx-edit'></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-vehicle" 
                                                data-id="<?php echo $vehicle['id']; ?>"
                                                data-number="<?php echo htmlspecialchars($vehicle['vehicle_number']); ?>">
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

<!-- Add Vehicle Modal -->
<div class="modal fade" id="addVehicleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addVehicleForm" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Vehicle Number</label>
                        <input type="text" name="vehicle_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Plate Number</label>
                        <input type="text" name="plate_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Driver</label>
                        <select name="driver_id" class="form-select">
                            <option value="">Select Driver</option>
                            <?php foreach ($drivers as $driver): ?>
                                <option value="<?php echo $driver['id']; ?>">
                                    <?php echo htmlspecialchars($driver['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Route</label>
                        <select name="route_id" class="form-select">
                            <option value="">Select Route</option>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?php echo $route['id']; ?>">
                                    <?php echo htmlspecialchars($route['route_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity</label>
                        <input type="number" name="capacity" class="form-control" value="16" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addVehicleForm" class="btn btn-primary">Add Vehicle</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Vehicle Modal -->
<div class="modal fade" id="editVehicleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editVehicleForm" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="vehicle_id" id="editVehicleId">
                    <div class="mb-3">
                        <label class="form-label">Vehicle Number</label>
                        <input type="text" name="vehicle_number" id="editVehicleNumber" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Plate Number</label>
                        <input type="text" name="plate_number" id="editPlateNumber" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Driver</label>
                        <select name="driver_id" id="editDriverId" class="form-select">
                            <option value="">Select Driver</option>
                            <?php foreach ($drivers as $driver): ?>
                                <option value="<?php echo $driver['id']; ?>">
                                    <?php echo htmlspecialchars($driver['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Route</label>
                        <select name="route_id" id="editRouteId" class="form-select">
                            <option value="">Select Route</option>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?php echo $route['id']; ?>">
                                    <?php echo htmlspecialchars($route['route_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity</label>
                        <input type="number" name="capacity" id="editCapacity" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="editStatus" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editVehicleForm" class="btn btn-primary">Update Vehicle</button>
            </div>
        </div>
    </div>
</div>

<!-- View Vehicle Modal -->
<div class="modal fade" id="viewVehicleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vehicle Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table">
                    <tr>
                        <th width="150">Vehicle Number</th>
                        <td id="viewVehicleNumber"></td>
                    </tr>
                    <tr>
                        <th>Plate Number</th>
                        <td id="viewPlateNumber"></td>
                    </tr>
                    <tr>
                        <th>Driver</th>
                        <td id="viewDriver"></td>
                    </tr>
                    <tr>
                        <th>Route</th>
                        <td id="viewRoute"></td>
                    </tr>
                    <tr>
                        <th>Capacity</th>
                        <td id="viewCapacity"></td>
                    </tr>
                    <tr>
                        <th>Available Seats</th>
                        <td id="viewAvailableSeats"></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td id="viewStatus"></td>
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
                Are you sure you want to delete this vehicle: <span id="vehicleNumber"></span>?
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
// Helper function for status badge colors
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'active':
            return 'success';
        case 'inactive':
            return 'danger';
        case 'maintenance':
            return 'warning';
        default:
            return 'secondary';
    }
}

// Add custom scripts before footer
$customScripts = <<<SCRIPTS
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#vehiclesTable').DataTable({
        "order": [[0, "desc"]]
    });

    // Handle edit button click
    $('.edit-vehicle').click(function() {
        const vehicle = $(this).data('vehicle');
        $('#editVehicleId').val(vehicle.id);
        $('#editVehicleNumber').val(vehicle.vehicle_number);
        $('#editPlateNumber').val(vehicle.plate_number);
        $('#editDriverId').val(vehicle.driver_id);
        $('#editRouteId').val(vehicle.route_id);
        $('#editCapacity').val(vehicle.capacity);
        $('#editStatus').val(vehicle.status);
    });

    // Handle view button click
    $('.view-vehicle').click(function() {
        const vehicle = $(this).data('vehicle');
        $('#viewVehicleNumber').text(vehicle.vehicle_number);
        $('#viewPlateNumber').text(vehicle.plate_number);
        $('#viewDriver').text(vehicle.driver_name || 'Not Assigned');
        $('#viewRoute').text(vehicle.route_name || 'Not Assigned');
        $('#viewCapacity').text(vehicle.capacity);
        $('#viewAvailableSeats').text(vehicle.available_seats);
        $('#viewStatus').html('<span class="badge bg-' + getStatusBadgeClass(vehicle.status) + '">' + 
                            vehicle.status.charAt(0).toUpperCase() + vehicle.status.slice(1) + '</span>');
        $('#viewCreatedDate').text(new Date(vehicle.created_at).toLocaleDateString());
    });

    // Handle delete button click
    $('.delete-vehicle').click(function() {
        const id = $(this).data('id');
        const number = $(this).data('number');
        $('#deleteId').val(id);
        $('#vehicleNumber').text(number);
        $('#deleteModal').modal('show');
    });

    function getStatusBadgeClass(status) {
        switch (status) {
            case 'active':
                return 'success';
            case 'inactive':
                return 'danger';
            case 'maintenance':
                return 'warning';
            default:
                return 'secondary';
        }
    }
});
</script>
SCRIPTS;

require_once 'includes/footer.php';
?> 