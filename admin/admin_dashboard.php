<?php
// admin_dashboard.php
session_start();

require_once '../db/connect.php';

// Security: Only Admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

$admin_name = $_SESSION['name'] ?? 'Admin';

// Quick stats queries
$stats = [
    'total_users'     => $conn->query("SELECT COUNT(*) FROM Users")->fetch_row()[0],
    'total_bookings'  => $conn->query("SELECT COUNT(*) FROM Booking")->fetch_row()[0],
    'pending_bookings' => $conn->query("SELECT COUNT(*) FROM Booking WHERE booking_status = 'Pending'")->fetch_row()[0],
    'confirmed_bookings' => $conn->query("SELECT COUNT(*) FROM Booking WHERE booking_status = 'Confirmed'")->fetch_row()[0],
    'total_revenue'   => $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM Booking WHERE booking_status = 'Confirmed'")->fetch_row()[0],
    'active_schedules' => $conn->query("SELECT COUNT(*) FROM Schedule WHERE departure_time > NOW()")->fetch_row()[0],
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AirLyft Admin Dashboard</title>

 <!-- Favicon -->
    <link rel="icon" href="../assets/img/icon.png" type="image/png">
    <link rel="shortcut icon" href="../assets/img/icon.png" type="image/png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="../assets/css/admin.css" />

    <?php include 'sidebar.php'; ?>
</head>


<!-- Main Content -->
<div class="main-content">
    <!-- Top Navbar -->
    <!-- Top Navbar -->
    <?php include 'admin_navbar.php'; ?>

    <!-- Quick Stats -->
    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="card card-stat bg-white border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Users</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_users']) ?></h3>
                        </div>
                        <i class="fas fa-users fa-3x text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-stat bg-white border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Bookings</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_bookings']) ?></h3>
                        </div>
                        <i class="fas fa-ticket-alt fa-3x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-stat bg-white border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Pending Bookings</h6>
                            <h3 class="mb-0 text-warning"><?= number_format($stats['pending_bookings']) ?></h3>
                        </div>
                        <i class="fas fa-clock fa-3x text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-stat bg-white border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Revenue (Confirmed)</h6>
                            <h3 class="mb-0">₱ <?= number_format($stats['total_revenue'], 2) ?></h3>
                        </div>
                        <i class="fas fa-peso-sign fa-3x text-info opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity / Quick Actions -->
    <div class="row g-4">
        <!-- Recent Bookings -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Recent Bookings</h5>
                </div>
                <div class="card-body">
                    <?php
                    $recent = $conn->query("
                        SELECT b.booking_id, u.first_name, u.last_name, p.place_name, s.departure_time, b.booking_status
                        FROM Booking b
                        JOIN Users u ON b.user_id = u.user_id
                        JOIN Schedule s ON b.sched_id = s.schedule_id
                        JOIN Place p ON s.place_id = p.place_id
                        ORDER BY b.booking_id DESC LIMIT 8
                    ");

                    if ($recent->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Destination</th>
                                        <th>Departure</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $recent->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?= $row['booking_id'] ?></td>
                                            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                            <td><?= htmlspecialchars($row['place_name']) ?></td>
                                            <td><?= date('M d, Y h:i A', strtotime($row['departure_time'])) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $row['booking_status'] === 'Confirmed' ? 'success' : ($row['booking_status'] === 'Pending' ? 'warning' : 'danger') ?>">
                                                    <?= $row['booking_status'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted my-5">No recent bookings</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body d-flex flex-column gap-3">
                    <a href="bookings.php" class="btn btn-lg btn-outline-primary d-flex align-items-center justify-content-between">
                        <span>Manage Bookings</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="schedules.php" class="btn btn-lg btn-outline-success d-flex align-items-center justify-content-between">
                        <span>Create New Schedule</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="aircraft.php" class="btn btn-lg btn-outline-info d-flex align-items-center justify-content-between">
                        <span>Add Aircraft</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="users.php" class="btn btn-lg btn-outline-warning d-flex align-items-center justify-content-between">
                        <span>View Users</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>