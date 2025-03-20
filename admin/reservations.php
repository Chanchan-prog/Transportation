<?php
$pageTitle = 'Manage Reservations';
$currentPage = 'reservations';
require_once 'includes/header.php';
checkAdminAuth();

// Handle Delete Action
if (isset($_POST['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        
        // Log the action
        $stmt = $pdo->prepare("INSERT INTO audit_logs (timestamp, user_id, ip_address, action, module, details) 
                              VALUES (NOW(), ?, ?, 'DELETE', 'Reservations', 'Reservation deleted')");
        $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
        
        $success_message = "Reservation deleted successfully!";
    } catch(PDOException $e) {
        $error_message = "Error deleting reservation: " . $e->getMessage();
    }
}

// Handle Update Reservation Status
if (isset($_POST['update_status'])) {
    try {
        $reservationId = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);
        $newStatus = filter_input(INPUT_POST, 'new_status', FILTER_SANITIZE_STRING);
        
        $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $reservationId]);

        // Update available seats if status is cancelled
        if ($newStatus === 'cancelled') {
            $stmt = $pdo->prepare("UPDATE donsals d 
                                  JOIN reservations r ON d.id = r.donsal_id 
                                  SET d.available_seats = d.available_seats + r.seats 
                                  WHERE r.id = ?");
            $stmt->execute([$reservationId]);
        }
        
        // Log the action
        $stmt = $pdo->prepare("INSERT INTO audit_logs (timestamp, user_id, ip_address, action, module, details) 
                              VALUES (NOW(), ?, ?, 'UPDATE', 'Reservations', ?)");
        $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], 
                       "Reservation status updated to: $newStatus"]);
        
        $success_message = "Reservation status updated successfully!";
    } catch(PDOException $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Handle Add/Edit Reservation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        $passengerId = filter_input(INPUT_POST, 'passenger_id', FILTER_VALIDATE_INT);
        $donsalId = filter_input(INPUT_POST, 'donsal_id', FILTER_VALIDATE_INT);
        $seats = filter_input(INPUT_POST, 'seats', FILTER_VALIDATE_INT);
        $travelDate = filter_input(INPUT_POST, 'travel_date', FILTER_SANITIZE_STRING);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

        if ($_POST['action'] === 'add') {
            // Check available seats
            $stmt = $pdo->prepare("SELECT available_seats FROM donsals WHERE id = ?");
            $stmt->execute([$donsalId]);
            $availableSeats = $stmt->fetchColumn();

            if ($seats > $availableSeats) {
                throw new Exception("Not enough available seats!");
            }

            // Insert new reservation
            $stmt = $pdo->prepare("INSERT INTO reservations (passenger_id, donsal_id, seats, travel_date, 
                                  status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$passengerId, $donsalId, $seats, $travelDate, $status]);
            
            // Update available seats
            $stmt = $pdo->prepare("UPDATE donsals SET available_seats = available_seats - ? WHERE id = ?");
            $stmt->execute([$seats, $donsalId]);
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO audit_logs (timestamp, user_id, ip_address, action, module, details) 
                                  VALUES (NOW(), ?, ?, 'CREATE', 'Reservations', 'New reservation added')");
            $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
            
            $success_message = "Reservation added successfully!";
        }
    } catch(Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch all reservations with related information
try {
    $stmt = $pdo->query("SELECT r.*, 
                         p.first_name as passenger_name, 
                         p.email as passenger_email,
                         d.vehicle_number, d.plate_number,
                         rt.route_name
                         FROM reservations r 
                         JOIN passengers p ON r.user_id = p.id
                         JOIN donsals d ON r.donsal_id = d.id
                         JOIN routes rt ON d.route_id = rt.id
                         ORDER BY r.created_at DESC");
    $reservations = $stmt->fetchAll();

    // Fetch passengers for dropdown
    $stmt = $pdo->query("SELECT id, first_name as name, email 
                         FROM passengers 
                         ORDER BY first_name");
    $passengers = $stmt->fetchAll();

    // Fetch vehicles with available seats for dropdown
    $stmt = $pdo->query("SELECT d.id, d.vehicle_number, d.available_seats, r.route_name 
                         FROM donsals d 
                         JOIN routes r ON d.route_id = r.id 
                         WHERE d.status = 'active' AND d.available_seats > 0 
                         ORDER BY d.vehicle_number");
    $vehicles = $stmt->fetchAll();

} catch(PDOException $e) {
    $error_message = "Error fetching data: " . $e->getMessage();
    $reservations = [];
    $passengers = [];
    $vehicles = [];
}
?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-6">
                <h2>Manage Reservations</h2>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReservationModal">
                    <i class='bx bx-plus'></i> Add New Reservation
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
                    <table id="reservationsTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Passenger</th>
                                <th>Vehicle</th>
                                <th>Route</th>
                                <th>Seats</th>
                                <th>Travel Date</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reservation['id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($reservation['passenger_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($reservation['passenger_email']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($reservation['vehicle_number']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($reservation['plate_number']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($reservation['route_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($reservation['seats']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($reservation['travel_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($reservation['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($reservation['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($reservation['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info view-reservation" 
                                                data-bs-toggle="modal" data-bs-target="#viewReservationModal"
                                                data-reservation='<?php echo json_encode($reservation); ?>'>
                                            <i class='bx bx-show'></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning update-status" 
                                                data-bs-toggle="modal" data-bs-target="#updateStatusModal"
                                                data-id="<?php echo $reservation['id']; ?>"
                                                data-status="<?php echo $reservation['status']; ?>">
                                            <i class='bx bx-transfer'></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-reservation" 
                                                data-id="<?php echo $reservation['id']; ?>"
                                                data-passenger="<?php echo htmlspecialchars($reservation['passenger_name']); ?>">
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

<!-- Add Reservation Modal -->
<div class="modal fade" id="addReservationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Reservation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addReservationForm" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Passenger</label>
                        <select name="passenger_id" class="form-select" required>
                            <option value="">Select Passenger</option>
                            <?php foreach ($passengers as $passenger): ?>
                                <option value="<?php echo $passenger['id']; ?>">
                                    <?php echo htmlspecialchars($passenger['name']); ?> 
                                    (<?php echo htmlspecialchars($passenger['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vehicle & Route</label>
                        <select name="donsal_id" class="form-select" required>
                            <option value="">Select Vehicle</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['id']; ?>">
                                    <?php echo htmlspecialchars($vehicle['vehicle_number']); ?> - 
                                    <?php echo htmlspecialchars($vehicle['route_name']); ?> 
                                    (<?php echo $vehicle['available_seats']; ?> seats available)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Number of Seats</label>
                        <input type="number" name="seats" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Travel Date</label>
                        <input type="date" name="travel_date" class="form-control" required 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addReservationForm" class="btn btn-primary">Add Reservation</button>
            </div>
        </div>
    </div>
</div>

<!-- View Reservation Modal -->
<div class="modal fade" id="viewReservationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reservation Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table">
                    <tr>
                        <th width="150">Passenger</th>
                        <td id="viewPassenger"></td>
                    </tr>
                    <tr>
                        <th>Vehicle</th>
                        <td id="viewVehicle"></td>
                    </tr>
                    <tr>
                        <th>Route</th>
                        <td id="viewRoute"></td>
                    </tr>
                    <tr>
                        <th>Seats</th>
                        <td id="viewSeats"></td>
                    </tr>
                    <tr>
                        <th>Travel Date</th>
                        <td id="viewTravelDate"></td>
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

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Reservation Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="updateStatusForm" method="POST">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="reservation_id" id="updateStatusId">
                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select name="new_status" id="newStatus" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="updateStatusForm" class="btn btn-primary">Update Status</button>
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
                Are you sure you want to delete the reservation for <span id="passengerName"></span>?
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
        case 'confirmed':
            return 'success';
        case 'pending':
            return 'warning';
        case 'cancelled':
            return 'danger';
        case 'completed':
            return 'info';
        default:
            return 'secondary';
    }
}

// Add custom scripts before footer
$customScripts = <<<SCRIPTS
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#reservationsTable').DataTable({
        "order": [[7, "desc"]]
    });

    // Handle view button click
    $('.view-reservation').click(function() {
        const reservation = $(this).data('reservation');
        $('#viewPassenger').html(reservation.passenger_name + '<br><small class="text-muted">' + 
                               reservation.passenger_email + '</small>');
        $('#viewVehicle').html(reservation.vehicle_number + '<br><small class="text-muted">' + 
                             reservation.plate_number + '</small>');
        $('#viewRoute').text(reservation.route_name);
        $('#viewSeats').text(reservation.seats);
        $('#viewTravelDate').text(new Date(reservation.travel_date).toLocaleDateString());
        $('#viewStatus').html('<span class="badge bg-' + getStatusBadgeClass(reservation.status) + '">' + 
                            reservation.status.charAt(0).toUpperCase() + reservation.status.slice(1) + '</span>');
        $('#viewCreatedDate').text(new Date(reservation.created_at).toLocaleString());
    });

    // Handle update status button click
    $('.update-status').click(function() {
        const id = $(this).data('id');
        const status = $(this).data('status');
        $('#updateStatusId').val(id);
        $('#newStatus').val(status);
    });

    // Handle delete button click
    $('.delete-reservation').click(function() {
        const id = $(this).data('id');
        const passenger = $(this).data('passenger');
        $('#deleteId').val(id);
        $('#passengerName').text(passenger);
        $('#deleteModal').modal('show');
    });

    function getStatusBadgeClass(status) {
        switch (status) {
            case 'confirmed':
                return 'success';
            case 'pending':
                return 'warning';
            case 'cancelled':
                return 'danger';
            case 'completed':
                return 'info';
            default:
                return 'secondary';
        }
    }
});
</script>
SCRIPTS;

require_once 'includes/footer.php';
?> 