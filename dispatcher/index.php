<?php
session_start();
require_once('../includes/config.php');

// Check if user is logged in and is a dispatcher
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'dispatcher') {
    header("Location: ../index.php");
    exit();
}

// Get today's statistics (you'll need to implement these queries)
$sql = "SELECT 
    COUNT(DISTINCT trip_id) as total_trips,
    COUNT(DISTINCT passenger_id) as total_passengers,
    SUM(fare) as total_income
FROM bookings 
WHERE DATE(booking_date) = CURRENT_DATE";
try {
    $stmt = $conn->query($sql);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $stats = ['total_trips' => 0, 'total_passengers' => 0, 'total_income' => 0];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Donsal Track - Dispatcher</title>
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
                    <a class="nav-link" href="#"><i class="fas fa-tasks me-2"></i> Assign</a>
                    <a class="nav-link" href="#"><i class="fas fa-bus me-2"></i> Bus Moderate</a>
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
                        <span class="me-3"><?php echo htmlspecialchars($_SESSION['name']); ?> dispatcher</span>
                        <a href="../logout.php" class="btn btn-outline-light"><i class="fas fa-sign-out-alt"></i></a>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row px-4">
                    <div class="col-md-4">
                        <div class="stats-card blue-card">
                            <h3><?php echo $stats['total_trips'] ?? 0; ?></h3>
                            <p>Total Trip Today's</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card green-card">
                            <h3><?php echo $stats['total_passengers'] ?? 0; ?></h3>
                            <p>Total passenger Today's</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card orange-card">
                            <h3>₱<?php echo number_format($stats['total_income'] ?? 0, 2); ?></h3>
                            <p>Total Income Today's</p>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row px-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="passengerChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="incomeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Passenger Distribution Chart
        new Chart(document.getElementById('passengerChart'), {
            type: 'pie',
            data: {
                labels: ['Regular', 'Student', 'Senior', 'PWD'],
                datasets: [{
                    data: [40, 30, 15, 15],
                    backgroundColor: ['#36A2EB', '#FF6384', '#4BC0C0', '#FFCE56']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Passenger Distribution',
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

        // Income Chart
        new Chart(document.getElementById('incomeChart'), {
            type: 'line',
            data: {
                labels: ['Thursday', 'Friday', 'Saturday', 'Sunday', 'Monday'],
                datasets: [{
                    label: 'Daily Income',
                    data: [12, 19, 25, 35, 10],
                    borderColor: '#36A2EB',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Income Trend',
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