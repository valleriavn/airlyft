<?php
// admin/payments.php
session_start();
require_once '../db/connect.php';

// Security: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Quick summary statistics (unchanged)
$stats = [
    'total_payments'    => $conn->query("SELECT COUNT(*) FROM Payment")->fetch_row()[0],
    'total_amount'      => $conn->query("SELECT COALESCE(SUM(amount), 0) FROM Payment")->fetch_row()[0],
    'paid_amount'       => $conn->query("SELECT COALESCE(SUM(amount), 0) FROM Payment WHERE payment_status = 'Paid'")->fetch_row()[0],
    'pending_payments'  => $conn->query("SELECT COUNT(*) FROM Payment WHERE payment_status = 'Pending'")->fetch_row()[0],
];

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'], $_POST['new_status'])) {
    $pay_id = (int)$_POST['payment_id'];
    $status = $_POST['new_status'];

    $stmt = $conn->prepare("UPDATE Payment SET payment_status = ? WHERE payment_id = ?");
    $stmt->bind_param("si", $status, $pay_id);
    if ($stmt->execute()) {
        header("Location: payments.php?msg=Payment+status+updated");
    } else {
        header("Location: payments.php?error=Update+failed");
    }
    exit();
}

// Filters (unchanged)
$status_filter = $_GET['status'] ?? '';

$where = [];
$params = [];
$types = '';

if ($status_filter && in_array($status_filter, ['Pending', 'Paid', 'Failed'])) {
    $where[] = "pay.payment_status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Main query
$sql = "
    SELECT 
        pay.payment_id,
        pay.booking_id,
        pay.amount,
        pay.method,
        pay.payment_status,
        b.total_amount AS booking_amount,
        CONCAT(u.first_name, ' ', u.last_name) AS customer_name
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payment Records - AirLyft Admin</title>

    <link rel="icon" href="../assets/img/icon.png" type="image/png">
    <link rel="shortcut icon" href="../assets/img/icon.png" type="image/png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php include 'admin_navbar.php'; ?>
        <h2 class="mb-4">Payment Records Management</h2>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Transactions</h6>
                        <h4 class="mb-0"><?= number_format($stats['total_payments']) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Volume</h6>
                        <h4 class="mb-0 text-primary">₱ <?= number_format($stats['total_amount'], 2) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Revenue Collected</h6>
                        <h4 class="mb-0 text-success">₱ <?= number_format($stats['paid_amount'], 2) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Pending</h6>
                        <h4 class="mb-0 text-warning"><?= number_format($stats['pending_payments']) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Paid" <?= $status_filter === 'Paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="Failed" <?= $status_filter === 'Failed' ? 'selected' : '' ?>>Failed</option>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>Apply Filter
                        </button>
                        <a href="payments.php" class="btn btn-outline-secondary ms-2">Reset</a>
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

        <!-- Payments Table -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Payment ID</th>
                                <th>Booking/Customer</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Quick Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($payments->num_rows === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        No payments found matching your filters
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($row = $payments->fetch_assoc()): ?>
                                    <tr class="align-middle">
                                        <td class="fw-bold">#<?= $row['payment_id'] ?></td>
                                        <td>
                                            <a href="bookings.php?id=<?= $row['booking_id'] ?>" class="text-decoration-none">#<?= $row['booking_id'] ?></a><br>
                                            <small class="text-muted"><?= htmlspecialchars($row['customer_name']) ?></small>
                                        </td>
                                        <td>₱ <?= number_format($row['amount'], 2) ?></td>
                                        <td><?= $row['method'] ?: '—' ?></td>
                                        <td>
                                            <span class="badge bg-<?=
                                                                    $row['payment_status'] === 'Paid' ? 'success' : ($row['payment_status'] === 'Pending' ? 'warning' : 'danger') ?>">
                                                <?= $row['payment_status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                                                <select name="new_status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                                    <option value="Pending" <?= $row['payment_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="Paid" <?= $row['payment_status'] === 'Paid' ? 'selected' : '' ?>>Mark Paid</option>
                                                    <option value="Failed" <?= $row['payment_status'] === 'Failed' ? 'selected' : '' ?>>Mark Failed</option>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>