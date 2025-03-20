<?php
$pageTitle = 'Audit Logs';
$currentPage = 'audit_logs';
require_once 'includes/header.php';
checkAdminAuth();

// Get date range from request, default to last 7 days
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get module filter
$moduleFilter = isset($_GET['module']) ? $_GET['module'] : '';
$actionFilter = isset($_GET['action']) ? $_GET['action'] : '';

try {
    // Build the query
    $query = "SELECT al.*, 
              CONCAT(u.firstname) as user_name,
              u.email as user_email
              FROM audit_logs al
              LEFT JOIN users u ON al.user_id = u.id
              WHERE DATE(al.timestamp) BETWEEN ? AND ? ";
    $params = [$startDate, $endDate];

    if ($moduleFilter) {
        $query .= "AND al.module = ? ";
        $params[] = $moduleFilter;
    }

    if ($actionFilter) {
        $query .= "AND al.action = ? ";
        $params[] = $actionFilter;
    }

    $query .= "ORDER BY al.timestamp DESC";

    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unique modules for filter
    $moduleStmt = $pdo->query("SELECT DISTINCT module FROM audit_logs WHERE module IS NOT NULL ORDER BY module");
    $modules = $moduleStmt->fetchAll(PDO::FETCH_COLUMN);

    // Get unique actions for filter
    $actionStmt = $pdo->query("SELECT DISTINCT action FROM audit_logs WHERE action IS NOT NULL ORDER BY action");
    $actions = $actionStmt->fetchAll(PDO::FETCH_COLUMN);

} catch(PDOException $e) {
    error_log("Error in audit logs: " . $e->getMessage());
    $logs = [];
    $modules = [];
    $actions = [];
}

// Function to get badge class for different actions
function getActionBadgeClass($action) {
    switch (strtoupper($action)) {
        case 'CREATE':
            return 'bg-success';
        case 'UPDATE':
            return 'bg-warning';
        case 'DELETE':
            return 'bg-danger';
        case 'LOGIN':
            return 'bg-info';
        case 'LOGOUT':
            return 'bg-secondary';
        default:
            return 'bg-primary';
    }
}
?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-6">
                <h2>Audit Logs</h2>
            </div>
            <div class="col-md-6">
                <form class="row g-3 justify-content-end" method="GET">
                    <div class="col-auto">
                        <input type="date" class="form-control" name="start_date" 
                               value="<?php echo htmlspecialchars($startDate); ?>">
                    </div>
                    <div class="col-auto">
                        <input type="date" class="form-control" name="end_date" 
                               value="<?php echo htmlspecialchars($endDate); ?>">
                    </div>
                    <div class="col-auto">
                        <select name="module" class="form-select">
                            <option value="">All Modules</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?php echo htmlspecialchars($module); ?>" 
                                        <?php echo $moduleFilter === $module ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($module); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="action" class="form-select">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?php echo htmlspecialchars($action); ?>"
                                        <?php echo $actionFilter === $action ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($action); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Apply Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="auditLogsTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>IP Address</th>
                                <th>Module</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No audit logs found for the selected period</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($log['timestamp'])); ?></td>
                                        <td>
                                            <?php if ($log['user_name']): ?>
                                                <?php echo htmlspecialchars($log['user_name']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($log['user_email']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">System</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($log['module']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo getActionBadgeClass($log['action']); ?>">
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['details']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Add custom scripts
$customScripts = <<<SCRIPTS
<script>
$(document).ready(function() {
    $('#auditLogsTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true
    });
});
</script>
SCRIPTS;

require_once 'includes/footer.php';
?> 