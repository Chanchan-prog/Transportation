<?php
$pageTitle = 'Manage Routes';
$currentPage = 'routes';
require_once 'includes/header.php';
checkAdminAuth();

// Handle Delete Action
if (isset($_POST['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM routes WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        
        // Log the action
        $stmt = $pdo->prepare("INSERT INTO audit_logs (timestamp, user_id, ip_address, action, module, details) 
                              VALUES (NOW(), ?, ?, 'DELETE', 'Routes', 'Route deleted')");
        $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
        
        $success_message = "Route deleted successfully!";
    } catch(PDOException $e) {
        $error_message = "Error deleting route: " . $e->getMessage();
    }
}

// Handle Add/Edit Route
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        $routeName = filter_input(INPUT_POST, 'route_name', FILTER_SANITIZE_STRING);
        $startPoint = filter_input(INPUT_POST, 'start_point', FILTER_SANITIZE_STRING);
        $endPoint = filter_input(INPUT_POST, 'end_point', FILTER_SANITIZE_STRING);
        $fareAmount = filter_input(INPUT_POST, 'fare_amount', FILTER_VALIDATE_FLOAT);
        $estimatedTime = filter_input(INPUT_POST, 'estimated_time', FILTER_VALIDATE_INT);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

        if ($_POST['action'] === 'add') {
            // Insert new route
            $stmt = $pdo->prepare("INSERT INTO routes (route_name, start_point, end_point, fare_amount, estimated_time, status) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$routeName, $startPoint, $endPoint, $fareAmount, $estimatedTime, $status]);
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO audit_logs (timestamp, user_id, ip_address, action, module, details) 
                                  VALUES (NOW(), ?, ?, 'CREATE', 'Routes', ?)");
            $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], "New route created: $routeName"]);
            
            $success_message = "Route added successfully!";

        } elseif ($_POST['action'] === 'edit') {
            $id = filter_input(INPUT_POST, 'route_id', FILTER_VALIDATE_INT);
            
            // Update route
            $stmt = $pdo->prepare("UPDATE routes SET route_name = ?, start_point = ?, end_point = ?, 
                                 fare_amount = ?, estimated_time = ?, status = ? WHERE id = ?");
            $stmt->execute([$routeName, $startPoint, $endPoint, $fareAmount, $estimatedTime, $status, $id]);
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO audit_logs (timestamp, user_id, ip_address, action, module, details) 
                                  VALUES (NOW(), ?, ?, 'UPDATE', 'Routes', ?)");
            $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], "Route updated: $routeName"]);
            
            $success_message = "Route updated successfully!";
        }
    } catch(Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch all routes
try {
    $stmt = $pdo->query("SELECT * FROM routes ORDER BY created_at DESC");
    $routes = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Error fetching routes: " . $e->getMessage();
    $routes = [];
}
?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-6">
                <h2>Manage Routes</h2>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRouteModal">
                    <i class='bx bx-plus'></i> Add New Route
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
                    <table id="routesTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Route Name</th>
                                <th>Start Point</th>
                                <th>End Point</th>
                                <th>Fare (₱)</th>
                                <th>Est. Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($routes as $route): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($route['id']); ?></td>
                                <td><?php echo htmlspecialchars($route['route_name']); ?></td>
                                <td><?php echo htmlspecialchars($route['start_point']); ?></td>
                                <td><?php echo htmlspecialchars($route['end_point']); ?></td>
                                <td><?php echo number_format($route['fare_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($route['estimated_time']); ?> mins</td>
                                <td>
                                    <span class="badge bg-<?php echo $route['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($route['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info view-route" 
                                                data-bs-toggle="modal" data-bs-target="#viewRouteModal"
                                                data-route='<?php echo json_encode($route); ?>'>
                                            <i class='bx bx-show'></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning edit-route" 
                                                data-bs-toggle="modal" data-bs-target="#editRouteModal"
                                                data-route='<?php echo json_encode($route); ?>'>
                                            <i class='bx bx-edit'></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-route" 
                                                data-id="<?php echo $route['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($route['route_name']); ?>">
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

<!-- Add Route Modal -->
<div class="modal fade" id="addRouteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Route</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addRouteForm" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Route Name</label>
                        <input type="text" name="route_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Point</label>
                        <input type="text" name="start_point" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End Point</label>
                        <input type="text" name="end_point" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fare Amount (₱)</label>
                        <input type="number" name="fare_amount" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estimated Time (minutes)</label>
                        <input type="number" name="estimated_time" class="form-control" required>
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
                <button type="submit" form="addRouteForm" class="btn btn-primary">Add Route</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Route Modal -->
<div class="modal fade" id="editRouteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Route</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editRouteForm" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="route_id" id="editRouteId">
                    <div class="mb-3">
                        <label class="form-label">Route Name</label>
                        <input type="text" name="route_name" id="editRouteName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Point</label>
                        <input type="text" name="start_point" id="editStartPoint" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End Point</label>
                        <input type="text" name="end_point" id="editEndPoint" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fare Amount (₱)</label>
                        <input type="number" name="fare_amount" id="editFareAmount" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estimated Time (minutes)</label>
                        <input type="number" name="estimated_time" id="editEstimatedTime" class="form-control" required>
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
                <button type="submit" form="editRouteForm" class="btn btn-primary">Update Route</button>
            </div>
        </div>
    </div>
</div>

<!-- View Route Modal -->
<div class="modal fade" id="viewRouteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Route Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table">
                    <tr>
                        <th width="150">Route Name</th>
                        <td id="viewRouteName"></td>
                    </tr>
                    <tr>
                        <th>Start Point</th>
                        <td id="viewStartPoint"></td>
                    </tr>
                    <tr>
                        <th>End Point</th>
                        <td id="viewEndPoint"></td>
                    </tr>
                    <tr>
                        <th>Fare Amount</th>
                        <td id="viewFareAmount"></td>
                    </tr>
                    <tr>
                        <th>Estimated Time</th>
                        <td id="viewEstimatedTime"></td>
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
                Are you sure you want to delete this route: <span id="routeName"></span>?
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
    $('#routesTable').DataTable({
        "order": [[0, "desc"]]
    });

    // Handle edit button click
    $('.edit-route').click(function() {
        const route = $(this).data('route');
        $('#editRouteId').val(route.id);
        $('#editRouteName').val(route.route_name);
        $('#editStartPoint').val(route.start_point);
        $('#editEndPoint').val(route.end_point);
        $('#editFareAmount').val(route.fare_amount);
        $('#editEstimatedTime').val(route.estimated_time);
        $('#editStatus').val(route.status);
    });

    // Handle view button click
    $('.view-route').click(function() {
        const route = $(this).data('route');
        $('#viewRouteName').text(route.route_name);
        $('#viewStartPoint').text(route.start_point);
        $('#viewEndPoint').text(route.end_point);
        $('#viewFareAmount').text('₱' + parseFloat(route.fare_amount).toFixed(2));
        $('#viewEstimatedTime').text(route.estimated_time + ' minutes');
        $('#viewStatus').html('<span class="badge bg-' + (route.status === 'active' ? 'success' : 'danger') + '">' + 
                            route.status.charAt(0).toUpperCase() + route.status.slice(1) + '</span>');
        $('#viewCreatedDate').text(new Date(route.created_at).toLocaleDateString());
    });

    // Handle delete button click
    $('.delete-route').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        $('#deleteId').val(id);
        $('#routeName').text(name);
        $('#deleteModal').modal('show');
    });
});
</script>
SCRIPTS;

require_once 'includes/footer.php';
?> 