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

// Helper function for safe counts
function getCount($conn, $sql)
{
    $result = $conn->query($sql);
    return $result ? (int)$result->fetch_row()[0] : 0;
}

// Quick stats
$stats = [
    'total_users'          => getCount($conn, "SELECT COUNT(*) FROM Users"),
    'total_bookings'       => getCount($conn, "SELECT COUNT(*) FROM Booking"),
    'pending_bookings'     => getCount($conn, "SELECT COUNT(*) FROM Booking WHERE booking_status='Pending'"),
    'confirmed_bookings'   => getCount($conn, "SELECT COUNT(*) FROM Booking WHERE booking_status='Confirmed'"),
    'total_revenue'        => getCount($conn, "SELECT COALESCE(SUM(total_amount),0) FROM Booking WHERE booking_status='Confirmed'"),
    'active_schedules'     => getCount($conn, "SELECT COUNT(*) FROM Schedule WHERE departure_time > NOW()"),
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

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <?php include 'admin_navbar.php'; ?>

    <!-- Quick Stats -->
    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Users</h6>
                        <h3><?= number_format($stats['total_users']) ?></h3>
                    </div>
                    <i class="fas fa-users fa-3x text-primary opacity-50"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Bookings</h6>
                        <h3><?= number_format($stats['total_bookings']) ?></h3>
                    </div>
                    <i class="fas fa-ticket-alt fa-3x text-success opacity-50"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Pending Bookings</h6>
                        <h3 class="text-warning"><?= number_format($stats['pending_bookings']) ?></h3>
                    </div>
                    <i class="fas fa-clock fa-3x text-warning opacity-50"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Revenue (Confirmed)</h6>
                        <h3>₱ <?= number_format($stats['total_revenue'], 2) ?></h3>
                    </div>
                    <i class="fas fa-peso-sign fa-3x text-info opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Bookings & Quick Actions -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Recent Bookings</h5>
                </div>
                <div class="card-body">
                    <?php
                    $recent = $conn->query("
                        SELECT b.booking_id, u.first_name, u.last_name,
                               p.place_name, s.departure_time, b.booking_status
                        FROM Booking b
                        JOIN Users u ON b.user_id = u.user_id
                        JOIN Schedule s ON b.sched_id = s.schedule_id
                        JOIN Place p ON s.place_id = p.place_id
                        ORDER BY b.booking_id DESC
                        LIMIT 8
                    ");
                    ?>

                    <?php if ($recent && $recent->num_rows > 0): ?>
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
                                <?php while ($row = $recent->fetch_assoc()):
                                    $statusClass = match ($row['booking_status']) {
                                        'Confirmed' => 'success',
                                        'Pending'   => 'warning',
                                        default     => 'danger',
                                    };
                                ?>
                                    <tr>
                                        <td>#<?= $row['booking_id'] ?></td>
                                        <td><?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?></td>
                                        <td><?= htmlspecialchars($row['place_name']) ?></td>
                                        <td><?= date('M d, Y h:i A', strtotime($row['departure_time'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $statusClass ?>">
                                                <?= htmlspecialchars($row['booking_status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center my-4">No recent bookings</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body d-grid gap-3">
                    <a href="bookings.php" class="btn btn-outline-primary btn-lg">Manage Bookings</a>
                    <a href="schedules.php" class="btn btn-outline-success btn-lg">Create Schedule</a>
                    <a href="aircraft.php" class="btn btn-outline-info btn-lg">Add Aircraft</a>
                    <a href="users.php" class="btn btn-outline-warning btn-lg">View Users</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent SMS Notifications -->
    <div class="row g-4 mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Recent SMS Notifications</h5>
                </div>
                <div class="card-body">
                    <?php
                    $sms = $conn->query("
                        SELECT sms_id, booking_id, message, sms_status
                        FROM smsnotification
                        ORDER BY sms_id DESC
                        LIMIT 8
                    ");
                    ?>

                    <?php if ($sms && $sms->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Booking</th>
                                        <th>Status</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while ($row = $sms->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?= $row['sms_id'] ?></td>
                                        <td>
                                            <?= $row['booking_id']
                                                ? '<a href="bookings.php?booking_id='.$row['booking_id'].'">#'.$row['booking_id'].'</a>'
                                                : '<span class="text-muted">N/A</span>' ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['sms_status']) ?></td>
                                        <td title="<?= htmlspecialchars($row['message']) ?>">
                                            <?= htmlspecialchars(mb_strimwidth($row['message'], 0, 80, '...')) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center my-3">No SMS notifications</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
