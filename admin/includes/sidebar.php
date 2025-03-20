<div class="sidebar">
    <nav class="nav flex-column">
        <a class="nav-link <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>" href="index.php">
            <i class='bx bxs-dashboard'></i> Dashboard
        </a>
        <a class="nav-link <?php echo ($currentPage === 'passengers') ? 'active' : ''; ?>" href="passengers.php">
            <i class='bx bxs-user-detail'></i> Passengers
        </a>
        <a class="nav-link <?php echo ($currentPage === 'drivers') ? 'active' : ''; ?>" href="drivers.php">
            <i class='bx bxs-truck'></i> Drivers
        </a>
        <a class="nav-link <?php echo ($currentPage === 'routes') ? 'active' : ''; ?>" href="routes.php">
            <i class='bx bxs-map'></i> Routes
        </a>
        <a class="nav-link <?php echo ($currentPage === 'vehicles') ? 'active' : ''; ?>" href="vehicles.php">
            <i class='bx bxs-bus'></i> Vehicles
        </a>
        <a class="nav-link <?php echo ($currentPage === 'reservations') ? 'active' : ''; ?>" href="reservations.php">
            <i class='bx bxs-bookmark'></i> Reservations
        </a>
        <a class="nav-link <?php echo ($currentPage === 'reports') ? 'active' : ''; ?>" href="reports.php">
            <i class='bx bxs-report'></i> Reports
        </a>
        <li class="nav-item">
            <a href="/tryslove/admin/audit-logs.php" class="nav-link <?php echo ($currentPage == 'audit_logs') ? 'active' : ''; ?>">
                <i class='bx bx-history'></i>
                <span>Audit Logs</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="users.php" class="nav-link <?php echo ($currentPage == 'users') ? 'active' : ''; ?>">
                <i class='bx bx-user'></i>
                <span>Users</span>
            </a>
        </li>
    </nav>
</div> 