<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="sidebar-header text-center">
        <img src="../assets/img/logo.png" alt="AirLyft" style="height: 60px;"/>
        <h4 class="mt-3 mb-0">Admin Panel</h4>
    </div>
    
    <nav class="nav flex-column mt-3">
        <a class="nav-link <?= $current_page === 'admin_dashboard.php' ? 'active' : '' ?>" href="admin_dashboard.php">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>

        <a class="nav-link <?= $current_page === 'users.php' ? 'active' : '' ?>" href="users.php">
            <i class="fas fa-users me-2"></i> Users
        </a>

        <a class="nav-link <?= $current_page === 'bookings.php' ? 'active' : '' ?>" href="bookings.php">
            <i class="fas fa-ticket-alt me-2"></i> Bookings
        </a>

        <a class="nav-link <?= $current_page === 'schedules.php' ? 'active' : '' ?>" href="schedules.php">
            <i class="fas fa-calendar-alt me-2"></i> Schedules
        </a>

        <a class="nav-link <?= $current_page === 'aircraft.php' ? 'active' : '' ?>" href="aircraft.php">
            <i class="fas fa-plane me-2"></i> Aircrafts
        </a>

        <a class="nav-link <?= $current_page === 'places.php' ? 'active' : '' ?>" href="places.php">
            <i class="fas fa-map-marker-alt me-2"></i> Destinations
        </a>

        <a class="nav-link <?= $current_page === 'payments.php' ? 'active' : '' ?>" href="payments.php">
            <i class="fas fa-money-bill-wave me-2"></i> Payments
        </a>

        <a class="nav-link <?= $current_page === 'notifications.php' ? 'active' : '' ?>" href="notifications.php">
            <i class="fas fa-bell me-2"></i> Notifications
        </a>

         <a class="nav-link <?= $current_page === 'apirequestlog.php' ? 'active' : '' ?>" href="apirequestlog.php">
            <i class="fas fa-bell me-2"></i> API Request Log
        </a>

        <hr class="bg-white opacity-25 my-4 mx-3"/>

        <a class="nav-link text-danger" href="../auth/logout.php">
            <i class="fas fa-sign-out-alt me-2"></i> Logout
        </a>
    </nav>
</div>
