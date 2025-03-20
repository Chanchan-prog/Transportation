<?php
$pageTitle = 'Reports';
$currentPage = 'reports';
require_once 'includes/header.php';
checkAdminAuth();

// Get date range from request, default to current month
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Function to get reservation statistics
function getReservationStats($pdo, $startDate, $endDate) {
    try {
        $stmt = $pdo->prepare("SELECT 
            COUNT(*) as total_reservations,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_reservations,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_reservations,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reservations,
            COALESCE(SUM(seats), 0) as total_seats_booked
            FROM reservations 
            WHERE created_at BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate . ' 23:59:59']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: [
            'total_reservations' => 0,
            'confirmed_reservations' => 0,
            'cancelled_reservations' => 0,
            'pending_reservations' => 0,
            'total_seats_booked' => 0
        ];
    } catch(PDOException $e) {
        error_log("Error in getReservationStats: " . $e->getMessage());
        return [
            'total_reservations' => 0,
            'confirmed_reservations' => 0,
            'cancelled_reservations' => 0,
            'pending_reservations' => 0,
            'total_seats_booked' => 0
        ];
    }
}

// Function to get popular routes
function getPopularRoutes($pdo, $startDate, $endDate) {
    try {
        $stmt = $pdo->prepare("SELECT 
            rt.route_name,
            COUNT(r.id) as total_bookings,
            COALESCE(SUM(r.seats), 0) as total_seats
            FROM reservations r
            JOIN donsals d ON r.donsal_id = d.id
            JOIN routes rt ON d.route_id = rt.id
            WHERE r.created_at BETWEEN ? AND ?
            AND r.status = 'confirmed'
            GROUP BY rt.route_name
            ORDER BY total_bookings DESC
            LIMIT 5");
        $stmt->execute([$startDate, $endDate . ' 23:59:59']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch(PDOException $e) {
        error_log("Error in getPopularRoutes: " . $e->getMessage());
        return [];
    }
}

// Function to get daily reservation counts
function getDailyReservations($pdo, $startDate, $endDate) {
    try {
        $stmt = $pdo->prepare("SELECT 
            DATE(created_at) as date,
            COUNT(*) as total_reservations,
            COALESCE(SUM(seats), 0) as total_seats
            FROM reservations
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date");
        $stmt->execute([$startDate, $endDate . ' 23:59:59']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch(PDOException $e) {
        error_log("Error in getDailyReservations: " . $e->getMessage());
        return [];
    }
}

// Get statistics
$stats = getReservationStats($pdo, $startDate, $endDate);
$popularRoutes = getPopularRoutes($pdo, $startDate, $endDate);
$dailyReservations = getDailyReservations($pdo, $startDate, $endDate);

// Prepare data for charts
$dates = [];
$reservationCounts = [];
$seatCounts = [];
foreach ($dailyReservations as $day) {
    $dates[] = date('M d', strtotime($day['date']));
    $reservationCounts[] = intval($day['total_reservations']);
    $seatCounts[] = intval($day['total_seats']);
}

// Prepare route data for charts
$routeNames = [];
$routeBookings = [];
foreach ($popularRoutes as $route) {
    $routeNames[] = $route['route_name'];
    $routeBookings[] = intval($route['total_bookings']);
}

// Prepare the JSON data before the JavaScript
$datesJson = json_encode($dates ?: []);
$reservationCountsJson = json_encode($reservationCounts ?: []);
$seatCountsJson = json_encode($seatCounts ?: []);
$routeNamesJson = json_encode($routeNames ?: []);
$routeBookingsJson = json_encode($routeBookings ?: []);
?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-6">
                <h2>Reports & Analytics</h2>
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
                        <button type="submit" class="btn btn-primary">Apply Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Reservations</h5>
                        <h2 class="card-text"><?php echo number_format($stats['total_reservations']); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Confirmed Reservations</h5>
                        <h2 class="card-text"><?php echo number_format($stats['confirmed_reservations']); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Cancelled Reservations</h5>
                        <h2 class="card-text"><?php echo number_format($stats['cancelled_reservations']); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Seats Booked</h5>
                        <h2 class="card-text"><?php echo number_format($stats['total_seats_booked']); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Daily Reservations</h5>
                        <canvas id="dailyReservationsChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Popular Routes</h5>
                        <canvas id="popularRoutesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Popular Routes Table -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Popular Routes Details</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Route</th>
                                <th>Total Bookings</th>
                                <th>Total Seats</th>
                                <th>Average Seats per Booking</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($popularRoutes)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No data available</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($popularRoutes as $route): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($route['route_name']); ?></td>
                                    <td><?php echo number_format($route['total_bookings']); ?></td>
                                    <td><?php echo number_format($route['total_seats']); ?></td>
                                    <td><?php echo number_format($route['total_bookings'] > 0 ? $route['total_seats'] / $route['total_bookings'] : 0, 1); ?></td>
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
// Add Chart.js library and custom scripts
$customScripts = <<<SCRIPTS
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Daily Reservations Chart
    new Chart(document.getElementById('dailyReservationsChart'), {
        type: 'line',
        data: {
            labels: {$datesJson},
            datasets: [{
                label: 'Reservations',
                data: {$reservationCountsJson},
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }, {
                label: 'Seats',
                data: {$seatCountsJson},
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Popular Routes Chart
    new Chart(document.getElementById('popularRoutesChart'), {
        type: 'pie',
        data: {
            labels: {$routeNamesJson},
            datasets: [{
                data: {$routeBookingsJson},
                backgroundColor: [
                    'rgb(255, 99, 132)',
                    'rgb(54, 162, 235)',
                    'rgb(255, 205, 86)',
                    'rgb(75, 192, 192)',
                    'rgb(153, 102, 255)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>
SCRIPTS;

require_once 'includes/footer.php';
?> 