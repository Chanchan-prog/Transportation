<?php
session_start();
require_once('../includes/config.php');

// Check if user is logged in and is a passenger
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'passenger') {
    header("Location: ../index.php");
    exit();
}

// Get passenger's statistics
$passenger_id = $_SESSION['user_id'];
$sql = "SELECT 
    COUNT(DISTINCT trip_id) as total_trips,
    COUNT(*) as total_bookings,
    SUM(fare) as total_spent
FROM bookings 
WHERE passenger_id = :passenger_id";
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute(['passenger_id' => $passenger_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $stats = ['total_trips' => 0, 'total_bookings' => 0, 'total_spent' => 0];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Donsal Track - Passenger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #2b2b2b;
            color: #ffffff;
        }
        .sidebar {
            background-color: #333333;
            min-height: 100vh;
            padding: 20px;
        }
        .nav-link {
            color: #ffffff;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .nav-link:hover, .nav-link.active {
            background-color: #28a745;
            color: #ffffff;
        }
        .top-bar {
            background-color: #333333;
            padding: 15px;
            margin-bottom: 20px;
        }
        .stats-card {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .stats-card h3 {
            font-size: 2.5em;
            margin: 10px 0;
        }
        .blue-card {
            background-color: #0d6efd;
        }
        .green-card {
            background-color: #198754;
        }
        .orange-card {
            background-color: #fd7e14;
        }
        .chart-container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <div class="d-flex align-items-center mb-4">
                    <img src="../assets/profile.jpg" class="rounded-circle me-2" width="40" height="40" alt="Profile">
                    <h5 class="mb-0">Donsal Track</h5>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#"><i class="fas fa-home me-2"></i> Dashboard</a>
                    <a class="nav-link" href="#"><i class="fas fa-ticket-alt me-2"></i> Book Ticket</a>
                    <a class="nav-link" href="#"><i class="fas fa-history me-2"></i> Travel History</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <!-- Top Bar -->
                <div class="top-bar d-flex justify-content-between align-items-center">
                    <div class="search-bar">
                        <input type="text" class="form-control" placeholder="Search anything">
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="me-3"><?php echo htmlspecialchars($_SESSION['name']); ?> passenger</span>
                        <a href="../logout.php" class="btn btn-outline-light"><i class="fas fa-sign-out-alt"></i></a>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row px-4">
                    <div class="col-md-4">
                        <div class="stats-card blue-card">
                            <h3><?php echo $stats['total_trips'] ?? 0; ?></h3>
                            <p>Total Trips</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card green-card">
                            <h3><?php echo $stats['total_bookings'] ?? 0; ?></h3>
                            <p>Total Bookings</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card orange-card">
                            <h3>₱<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></h3>
                            <p>Total Amount Spent</p>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row px-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="travelChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="spendingChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Travel History Chart
        new Chart(document.getElementById('travelChart'), {
            type: 'pie',
            data: {
                labels: ['Morning Trips', 'Afternoon Trips', 'Evening Trips', 'Night Trips'],
                datasets: [{
                    data: [40, 30, 20, 10],
                    backgroundColor: ['#36A2EB', '#FF6384', '#4BC0C0', '#FFCE56']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Travel Time Distribution',
                        color: '#000'
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#000'
                        }
                    }
                }
            }
        });

        // Spending Chart
        new Chart(document.getElementById('spendingChart'), {
            type: 'line',
            data: {
                labels: ['Thursday', 'Friday', 'Saturday', 'Sunday', 'Monday'],
                datasets: [{
                    label: 'Daily Spending',
                    data: [150, 200, 300, 250, 180],
                    borderColor: '#36A2EB',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Spending Trend',
                        color: '#000'
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#000'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#000'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#000'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 