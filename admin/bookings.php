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

    header("Location: bookings.php?msg=Status+updated");
    exit();
}

// Handle DELETE booking
if (isset($_GET['delete'])) {
    $booking_id = (int)$_GET['delete'];
    try {
        if ($conn->query("DELETE FROM Booking WHERE booking_id = $booking_id")) {
            header("Location: bookings.php?msg=Booking+deleted+successfully");
        } else {
            header("Location: bookings.php?error=Could+not+delete+booking");
        }
    } catch (mysqli_sql_exception $e) {
        header("Location: bookings.php?error=Error:+" . urlencode($e->getMessage()));
    }
    exit();
}

// Filters (unchanged)
$status_filter = $_GET['status'] ?? '';
$date_from     = $_GET['date_from'] ?? '';
$date_to       = $_GET['date_to'] ?? '';

$where = [];
$params = [];
$types = '';

if ($status_filter && in_array($status_filter, ['Pending', 'Confirmed', 'Cancelled'])) {
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
        CONCAT(u.first_name, ' ', u.last_name) AS customer_name,
        u.email,
        u.phone,
        p.place_name AS destination,
        l.aircraft_type,
        s.departure_time,
        s.arrival_time,
        b.total_amount,
        b.booking_status,
        pay.payment_status AS payment_status,
        pay.method AS payment_method,
        pay.amount AS paid_amount
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Bookings - AirLyft Admin</title>

    <link rel="icon" href="../assets/img/icon.png" type="image/png">
    <link rel="shortcut icon" href="../assets/img/icon.png" type="image/png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php include 'admin_navbar.php'; ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Manage Bookings</h2>
            <div>
                <a href="bookings.php" class="btn btn-outline-secondary btn-sm">Reset Filters</a>
            </div>
        </div>

        <!-- Filters (unchanged) -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Confirmed" <?= $status_filter === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="Cancelled" <?= $status_filter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
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

        <!-- Messages -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_GET['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Bookings Table -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Destination</th>
                                <th>Departure</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bookings->num_rows === 0): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">No bookings found matching your filters</td>
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
                                        <td>
                                            <?= date('M d, Y', strtotime($row['departure_time'])) ?><br>
                                            <small class="text-muted"><?= date('h:i A', strtotime($row['departure_time'])) ?></small>
                                        </td>
                                        <td>₱ <?= number_format($row['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?=
                                                                    $row['booking_status'] === 'Confirmed' ? 'success' : ($row['booking_status'] === 'Pending' ? 'warning' : 'danger') ?>">
                                                <?= $row['booking_status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?=
                                                                    $row['payment_status'] === 'Paid' ? 'success' : ($row['payment_status'] === 'Pending' ? 'warning' : 'danger') ?>">
                                                <?= $row['payment_status'] ?: 'Unpaid' ?>
                                            </span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button class="btn btn-sm btn-outline-info view-booking"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewBookingModal"
                                                data-id="<?= $row['booking_id'] ?>"
                                                data-customer="<?= htmlspecialchars($row['customer_name']) ?>"
                                                data-email="<?= htmlspecialchars($row['email']) ?>"
                                                data-phone="<?= htmlspecialchars($row['phone']) ?>"
                                                data-destination="<?= htmlspecialchars($row['destination']) ?>"
                                                data-aircraft="<?= htmlspecialchars($row['aircraft_type']) ?>"
                                                data-departure="<?= date('Y-m-d H:i', strtotime($row['departure_time'])) ?>"
                                                data-arrival="<?= date('Y-m-d H:i', strtotime($row['arrival_time'])) ?>"
                                                data-amount="<?= number_format($row['total_amount'], 2) ?>"
                                                data-status="<?= $row['booking_status'] ?>"
                                                data-pstatus="<?= $row['payment_status'] ?: 'Unpaid' ?>"
                                                data-pmethod="<?= $row['payment_method'] ?: 'N/A' ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            <form method="post" class="d-inline mx-1">
                                                <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
                                                <select name="new_status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                                    <option value="Pending" <?= $row['booking_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="Confirmed" <?= $row['booking_status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                    <option value="Cancelled" <?= $row['booking_status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                            </form>

                                            <a href="?delete=<?= $row['booking_id'] ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Delete this booking? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- View Booking Modal -->
    <div class="modal fade" id="viewBookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Booking Details #<span id="v-id"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <p class="mb-1"><strong>Name:</strong> <span id="v-customer"></span></p>
                            <p class="mb-1"><strong>Email:</strong> <span id="v-email"></span></p>
                            <p class="mb-1"><strong>Phone:</strong> <span id="v-phone"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Flight Information</h6>
                            <p class="mb-1"><strong>Destination:</strong> <span id="v-destination"></span></p>
                            <p class="mb-1"><strong>Aircraft:</strong> <span id="v-aircraft"></span></p>
                            <p class="mb-1"><strong>Departure:</strong> <span id="v-departure"></span></p>
                            <p class="mb-1"><strong>Arrival:</strong> <span id="v-arrival"></span></p>
                        </div>
                        <div class="col-md-6 border-top pt-3">
                            <h6>Financial Information</h6>
                            <p class="mb-1"><strong>Total Amount:</strong> ₱ <span id="v-amount"></span></p>
                            <p class="mb-1"><strong>Payment Method:</strong> <span id="v-pmethod"></span></p>
                            <p class="mb-1"><strong>Payment Status:</strong> <span id="v-pstatus" class="badge"></span></p>
                        </div>
                        <div class="col-md-6 border-top pt-3">
                            <h6>Booking Status</h6>
                            <p class="mb-0"><strong>Current Status:</strong> <span id="v-status" class="badge"></span></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.view-booking').forEach(button => {
            button.addEventListener('click', function() {
                const data = this.dataset;
                document.getElementById('v-id').textContent = data.id;
                document.getElementById('v-customer').textContent = data.customer;
                document.getElementById('v-email').textContent = data.email;
                document.getElementById('v-phone').textContent = data.phone;
                document.getElementById('v-destination').textContent = data.destination;
                document.getElementById('v-aircraft').textContent = data.aircraft;
                document.getElementById('v-departure').textContent = data.departure;
                document.getElementById('v-arrival').textContent = data.arrival;
                document.getElementById('v-amount').textContent = data.amount;
                document.getElementById('v-pmethod').textContent = data.pmethod;

                const ps = document.getElementById('v-pstatus');
                ps.textContent = data.pstatus;
                ps.className = 'badge bg-' + (data.pstatus === 'Paid' ? 'success' : (data.pstatus === 'Pending' ? 'warning' : 'danger'));

                const s = document.getElementById('v-status');
                s.textContent = data.status;
                s.className = 'badge bg-' + (data.status === 'Confirmed' ? 'success' : (data.status === 'Pending' ? 'warning' : 'danger'));
            });
        });
    </script>
</body>

</html>

</html>