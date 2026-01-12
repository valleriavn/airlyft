<?php
// admin/notifications.php
session_start();
require_once '../db/connect.php';

// Security: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Quick stats
$stats = [
    'total_email'     => $conn->query("SELECT COUNT(*) FROM EmailNotification")->fetch_row()[0],
    'pending_email'   => $conn->query("SELECT COUNT(*) FROM EmailNotification WHERE email_notif_status = 'Pending'")->fetch_row()[0],
    'sent_email'      => $conn->query("SELECT COUNT(*) FROM EmailNotification WHERE email_notif_status = 'Sent'")->fetch_row()[0],
    'failed_email'    => $conn->query("SELECT COUNT(*) FROM EmailNotification WHERE email_notif_status = 'Failed'")->fetch_row()[0],
    
    'total_sms'       => $conn->query("SELECT COUNT(*) FROM SMSNotification")->fetch_row()[0],
    'pending_sms'     => $conn->query("SELECT COUNT(*) FROM SMSNotification WHERE sms_status = 'Pending'")->fetch_row()[0],
    'sent_sms'        => $conn->query("SELECT COUNT(*) FROM SMSNotification WHERE sms_status = 'Sent'")->fetch_row()[0],
    'failed_sms'      => $conn->query("SELECT COUNT(*) FROM SMSNotification WHERE sms_status = 'Failed'")->fetch_row()[0],
];

// Filters
$type_filter   = $_GET['type']   ?? 'all'; // all, email, sms
$status_filter = $_GET['status'] ?? 'all'; // all, Pending, Sent, Failed

$tables = ($type_filter === 'email') ? "EmailNotification" : 
          (($type_filter === 'sms') ? "SMSNotification" : 
          "(SELECT 'Email' AS type, email_id AS id, booking_id, recipient AS target, subject AS title, email_notif_status AS status FROM EmailNotification 
            UNION ALL 
            SELECT 'SMS' AS type, sms_id AS id, booking_id, NULL AS target, message AS title, sms_status AS status FROM SMSNotification)");

$where = [];
$params = [];
$types = '';

if ($status_filter !== 'all' && in_array($status_filter, ['Pending','Sent','Failed'])) {
    $where[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Main query
$sql = "
    SELECT 
        type, id, n.booking_id, target, title, status,
        b.total_amount, u.name AS customer_name, p.place_name AS destination
    FROM $tables n
    LEFT JOIN Booking b ON n.booking_id = b.booking_id
    LEFT JOIN Users u ON b.user_id = u.user_id
    LEFT JOIN Schedule s ON b.sched_id = s.schedule_id
    LEFT JOIN Place p ON s.place_id = p.place_id
    $where_clause
    ORDER BY id DESC
    LIMIT 100
";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$notifications = $stmt->get_result();

// Handle manual resend (very basic - real implementation needs actual sending logic)
if (isset($_GET['resend'])) {
    $id = (int)$_GET['resend'];
    $type = $_GET['type'] ?? 'email';

    // In real system: call your email/SMS sending function here
    // For demo: just mark as Pending again
    if ($type === 'email') {
        $conn->query("UPDATE EmailNotification SET email_notif_status = 'Pending' WHERE email_id = $id");
    } else {
        $conn->query("UPDATE SMSNotification SET sms_status = 'Pending' WHERE sms_id = $id");
    }

    header("Location: ../admin/notifications.php?msg=Notification+marked+for+resend");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Notifications Management - AirLyft Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../admin/admin_style.css">
    
</head>
<body>

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h2 class="mb-4">Notifications Queue & Management</h2>

    <!-- Quick Stats -->
    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total Emails</h6>
                    <h4><?= number_format($stats['total_email']) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Pending Emails</h6>
                    <h4 class="text-warning"><?= number_format($stats['pending_email']) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total SMS</h6>
                    <h4><?= number_format($stats['total_sms']) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Pending SMS</h6>
                    <h4 class="text-warning"><?= number_format($stats['pending_sms']) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="all"    <?= $type_filter === 'all' ? 'selected' : '' ?>>All (Email + SMS)</option>
                        <option value="email"  <?= $type_filter === 'email' ? 'selected' : '' ?>>Email Only</option>
                        <option value="sms"    <?= $type_filter === 'sms' ? 'selected' : '' ?>>SMS Only</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all">All Statuses</option>
                        <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Sent"    <?= $status_filter === 'Sent' ? 'selected' : '' ?>>Sent</option>
                        <option value="Failed"  <?= $status_filter === 'Failed' ? 'selected' : '' ?>>Failed</option>
                    </select>
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>Destination</th>
                            <th>Subject / Message</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($notifications->num_rows === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                No notifications found matching your filters
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = $notifications->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $row['id'] ?></td>
                                <td>
                                    <span class="badge <?= $row['type'] === 'Email' ? 'bg-primary' : 'bg-success' ?>">
                                        <?= $row['type'] ?>
                                    </span>
                                </td>
                                <td>#<?= $row['booking_id'] ?></td>
                                <td><?= htmlspecialchars($row['customer_name'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['destination'] ?? '—') ?></td>
                                <td title="<?= htmlspecialchars($row['title']) ?>">
                                    <?= htmlspecialchars(substr($row['title'], 0, 60)) . (strlen($row['title']) > 60 ? '...' : '') ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $row['status'] === 'Sent' ? 'success' : 
                                        ($row['status'] === 'Pending' ? 'warning' : 'danger') ?>">
                                        <?= $row['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'Failed' || $row['status'] === 'Pending'): ?>
                                        <a href="?resend=<?= $row['id'] ?>&type=<?= strtolower($row['type']) ?>" 
                                           class="btn btn-sm btn-warning">
                                            <i class="fas fa-redo"></i> Resend
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-center text-muted">
            Showing up to 100 most recent notifications
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>