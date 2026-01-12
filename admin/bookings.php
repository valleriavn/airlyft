<?php
// admin/bookings.php
session_start();
require_once '../db/connect.php';

// Security: Only Admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['new_status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = $_POST['new_status'];

    $stmt = $conn->prepare("UPDATE Booking SET booking_status = ? WHERE booking_id = ?");
    $stmt->bind_param("si", $new_status, $booking_id);
    $stmt->execute();
    $stmt->close();

    // Optional: redirect with success message
    header("Location: bookings.php?msg=Status+updated");
    exit();
}

// Filters
$status_filter = $_GET['status'] ?? '';
$date_from     = $_GET['date_from'] ?? '';
$date_to       = $_GET['date_to'] ?? '';

$where = [];
$params = [];
$types = '';

if ($status_filter && in_array($status_filter, ['Pending','Confirmed','Cancelled'])) {
    $where[] = "b.booking_status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($date_from) {
    $where[] = "s.departure_time >= ?";
    $params[] = $date_from . ' 00:00:00';
    $types .= 's';
}

if ($date_to) {
    $where[] = "s.departure_time <= ?";
    $params[] = $date_to . ' 23:59:59';
    $types .= 's';
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Main query
$sql = "
    SELECT 
        b.booking_id,
        u.name AS customer_name,
        u.email,
        p.place_name AS destination,
        l.aircraft_type,
        s.departure_time,
        s.arrival_time,
        b.total_amount,
        b.booking_status,
        pay.payment_status AS payment_status,
        pay.method AS payment_method
    FROM Booking b
    JOIN Users u ON b.user_id = u.user_id
    JOIN Schedule s ON b.sched_id = s.schedule_id
    JOIN Place p ON s.place_id = p.place_id
    JOIN Lift l ON s.lift_id = l.lift_id
    LEFT JOIN Payment pay ON b.payment_id = pay.payment_id
    $where_clause
    ORDER BY b.booking_id DESC
    LIMIT 100
";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$bookings = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Manage Bookings - AirLyft Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../admin/admin_style.css">
    
</head>
<body>

<!-- Sidebar (you should extract this to sidebar.php and include it) -->
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Manage Bookings</h2>
        <div>
            <a href="bookings.php" class="btn btn-outline-secondary btn-sm">Reset Filters</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Pending"    <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Confirmed"  <?= $status_filter === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="Cancelled"  <?= $status_filter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Success message -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_GET['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Bookings Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Destination</th>
                            <th>Aircraft</th>
                            <th>Departure</th>
                            <th>Amount</th>
                            <th>Booking Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($bookings->num_rows === 0): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">No bookings found matching your filters</td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = $bookings->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold">#<?= $row['booking_id'] ?></td>
                                <td>
                                    <?= htmlspecialchars($row['customer_name']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($row['email']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['destination']) ?></td>
                                <td><?= htmlspecialchars($row['aircraft_type']) ?></td>
                                <td>
                                    <?= date('M d, Y', strtotime($row['departure_time'])) ?><br>
                                    <small><?= date('h:i A', strtotime($row['departure_time'])) ?></small>
                                </td>
                                <td>₱ <?= number_format($row['total_amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $row['booking_status'] === 'Confirmed' ? 'success' : 
                                        ($row['booking_status'] === 'Pending' ? 'warning' : 'danger') ?>">
                                        <?= $row['booking_status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $row['payment_status'] === 'Paid' ? 'success' : 
                                        ($row['payment_status'] === 'Pending' ? 'warning' : 'danger') ?>">
                                        <?= $row['payment_status'] ?: 'N/A' ?> 
                                        <?= $row['payment_method'] ? "({$row['payment_method']})" : '' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
                                        <select name="new_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="Pending"    <?= $row['booking_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Confirmed"  <?= $row['booking_status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="Cancelled"  <?= $row['booking_status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted text-center">
            Showing up to 100 most recent bookings • <?= $bookings->num_rows ?> records found
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>