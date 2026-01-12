<?php
// admin/payments.php
session_start();
require_once '../db/connect.php';

// Security: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Quick summary statistics
$stats = [
    'total_payments'    => $conn->query("SELECT COUNT(*) FROM Payment")->fetch_row()[0],
    'total_amount'      => $conn->query("SELECT COALESCE(SUM(amount), 0) FROM Payment")->fetch_row()[0],
    'paid_amount'       => $conn->query("SELECT COALESCE(SUM(amount), 0) FROM Payment WHERE payment_status = 'Paid'")->fetch_row()[0],
    'pending_payments'  => $conn->query("SELECT COUNT(*) FROM Payment WHERE payment_status = 'Pending'")->fetch_row()[0],
];

// Filters
$status_filter = $_GET['status'] ?? '';
$date_from     = $_GET['date_from'] ?? '';
$date_to       = $_GET['date_to'] ?? '';

$where = [];
$params = [];
$types = '';

if ($status_filter && in_array($status_filter, ['Pending','Paid','Failed'])) {
    $where[] = "pay.payment_status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Main query (departure_time removed from SELECT)
$sql = "
    SELECT 
        pay.payment_id,
        pay.booking_id,
        pay.amount,
        pay.method,
        pay.payment_status,
        b.total_amount AS booking_amount
    FROM Payment pay
    JOIN Booking b ON pay.booking_id = b.booking_id
    JOIN Users u ON b.user_id = u.user_id
    $where_clause
    ORDER BY pay.payment_id DESC
    LIMIT 100
";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$payments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Payment Records - AirLyft Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../admin/admin_style.css">

</head>
<body>

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h2 class="mb-4">Payment Records Management</h2>

    <!-- Quick Stats (unchanged) -->
    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total Transactions</h6>
                    <h4><?= number_format($stats['total_payments']) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total Amount</h6>
                    <h4>₱ <?= number_format($stats['total_amount'], 2) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Paid Amount</h6>
                    <h4 class="text-success">₱ <?= number_format($stats['paid_amount'], 2) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Pending Payments</h6>
                    <h4 class="text-warning"><?= number_format($stats['pending_payments']) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters (unchanged) -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Paid"    <?= $status_filter === 'Paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="Failed"  <?= $status_filter === 'Failed' ? 'selected' : '' ?>>Failed</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payments Table (Departure column removed) -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Payment ID</th>
                            <th>Booking ID</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($payments->num_rows === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                No payments found matching your filters
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = $payments->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $row['payment_id'] ?></td>
                                <td>#<?= $row['booking_id'] ?></td>
                                <td>₱ <?= number_format($row['amount'], 2) ?></td>
                                <td><?= $row['method'] ?: '—' ?></td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $row['payment_status'] === 'Paid' ? 'success' : 
                                        ($row['payment_status'] === 'Pending' ? 'warning' : 'danger') ?>">
                                        <?= $row['payment_status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-center text-muted">
            Showing up to 100 most recent payments
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>