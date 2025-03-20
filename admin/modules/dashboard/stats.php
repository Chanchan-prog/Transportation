<?php
// Fetch dashboard statistics
try {
    $stats = [
        'passengers' => $pdo->query("SELECT COUNT(*) FROM passengers")->fetchColumn(),
        'drivers' => $pdo->query("SELECT COUNT(*) FROM drivers")->fetchColumn(),
        'routes' => $pdo->query("SELECT COUNT(*) FROM routes")->fetchColumn(),
        'vehicles' => $pdo->query("SELECT COUNT(*) FROM donsals")->fetchColumn()
    ];

    $recentLogs = $pdo->query("SELECT * FROM audit_logs ORDER BY timestamp DESC LIMIT 5")->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching dashboard stats: " . $e->getMessage());
    $stats = ['passengers' => 0, 'drivers' => 0, 'routes' => 0, 'vehicles' => 0];
    $recentLogs = [];
}
?>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted">Total Passengers</h6>
                    <h3 class="mb-0"><?php echo number_format($stats['passengers']); ?></h3>
                </div>
                <i class='bx bxs-user stats-icon'></i>
            </div>
        </div>
    </div>
    <!-- Add other stat cards similarly -->
</div>

<!-- Recent Activity -->
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
                        <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                        <td><?php echo htmlspecialchars($log['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                        <td><?php echo htmlspecialchars($log['module']); ?></td>
                        <td><?php echo htmlspecialchars($log['details']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div> 