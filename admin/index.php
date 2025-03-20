<?php
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
require_once 'includes/header.php';
checkAdminAuth();

// Fetch dashboard statistics
try {
    // Count total passengers
    $stmt = $pdo->query("SELECT COUNT(*) FROM passengers");
    $totalPassengers = $stmt->fetchColumn();

    // Count total drivers
    $stmt = $pdo->query("SELECT COUNT(*) FROM drivers");
    $totalDrivers = $stmt->fetchColumn();

    // Count total routes
    $stmt = $pdo->query("SELECT COUNT(*) FROM routes");
    $totalRoutes = $stmt->fetchColumn();

    // Count total donsals (vehicles)
    $stmt = $pdo->query("SELECT COUNT(*) FROM donsals");
    $totalVehicles = $stmt->fetchColumn();

    // Recent audit logs
    $stmt = $pdo->query("SELECT al.*, u.name as user_name 
                        FROM audit_logs al 
                        LEFT JOIN users u ON al.user_id = u.id 
                        ORDER BY timestamp DESC LIMIT 5");
    $recentLogs = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
}
?>

<!-- Main Content -->
<div class="main-content">
    <!-- Header -->
    <div class="header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Dashboard Overview</h4>
        <div class="d-flex align-items-center">
            <span class="me-3">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Passengers</h6>
                        <h3 class="mb-0"><?php echo number_format($totalPassengers); ?></h3>
                    </div>
                    <i class='bx bxs-user stats-icon'></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Drivers</h6>
                        <h3 class="mb-0"><?php echo number_format($totalDrivers); ?></h3>
                    </div>
                    <i class='bx bxs-id-card stats-icon'></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Active Routes</h6>
                        <h3 class="mb-0"><?php echo number_format($totalRoutes); ?></h3>
                    </div>
                    <i class='bx bxs-map-alt stats-icon'></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Vehicles</h6>
                        <h3 class="mb-0"><?php echo number_format($totalVehicles); ?></h3>
                    </div>
                    <i class='bx bxs-bus stats-icon'></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Module</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentLogs as $log): ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($log['timestamp'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getActionBadgeClass($log['action']); ?>">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['module']); ?></td>
                                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Dashboard Widgets -->
    <div class="row mt-4">
        <!-- Quick Actions -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="passengers.php" class="btn btn-outline-primary">
                            <i class='bx bxs-user-plus'></i> Add New Passenger
                        </a>
                        <a href="drivers.php" class="btn btn-outline-success">
                            <i class='bx bxs-user-badge'></i> Add New Driver
                        </a>
                        <a href="routes.php" class="btn btn-outline-info">
                            <i class='bx bxs-map-plus'></i> Add New Route
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">System Status</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Active Vehicles
                            <span class="badge bg-primary rounded-pill">
                                <?php echo getActiveVehiclesCount($pdo); ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Today's Reservations
                            <span class="badge bg-success rounded-pill">
                                <?php echo getTodayReservationsCount($pdo); ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Active Drivers
                            <span class="badge bg-info rounded-pill">
                                <?php echo getActiveDriversCount($pdo); ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper functions
function getActionBadgeClass($action) {
    switch (strtoupper($action)) {
        case 'LOGIN':
            return 'success';
        case 'LOGOUT':
            return 'warning';
        case 'CREATE':
            return 'primary';
        case 'UPDATE':
            return 'info';
        case 'DELETE':
            return 'danger';
        default:
            return 'secondary';
    }
}

function getActiveVehiclesCount($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM donsals WHERE status = 'active'");
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        return 0;
    }
}

function getTodayReservationsCount($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM reservations WHERE DATE(reservation_date) = CURDATE()");
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        return 0;
    }
}

function getActiveDriversCount($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM drivers WHERE status = 'active'");
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        return 0;
    }
}

// Add custom scripts
$customScripts = <<<SCRIPTS
<script>
$(document).ready(function() {
    // Add any dashboard-specific JavaScript here
    
    // Example: Auto-refresh dashboard stats every 5 minutes
    setInterval(function() {
        location.reload();
    }, 300000);
});
</script>
SCRIPTS;

require_once 'includes/footer.php';
?> 