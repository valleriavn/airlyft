<?php
// admin/booking_history.php
session_start();
require_once '../db/connect.php';

// Security: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Main Query
$sql = "
    SELECT 
        h.*, 
        CONCAT(u.first_name, ' ', u.last_name) AS user_name,
        u.email AS user_email
    FROM booking_history h
    LEFT JOIN Users u ON h.user_id = u.user_id
    ORDER BY h.changed_at DESC
    LIMIT 100
";
$history = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Booking Audit Log - AirLyft Admin</title>

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
        <h2 class="mb-4">Booking Audit Logs</h2>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Log ID</th>
                                <th>Booking Ref</th>
                                <th>Action</th>
                                <th>Status Snapshot</th>
                                <th>Amount Snapshot</th>
                                <th>User (Actor)</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($history->num_rows === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">No history logs found.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($row = $history->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?= $row['history_id'] ?></td>
                                        <td>
                                            <a href="bookings.php?id=<?= $row['booking_id'] ?>" class="text-decoration-none fw-bold">
                                                #<?= $row['booking_id'] ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark"><?= htmlspecialchars($row['action']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?=
                                                                    $row['booking_status'] === 'Confirmed' ? 'success' : ($row['booking_status'] === 'Cancelled' ? 'danger' : 'secondary') ?>">
                                                <?= $row['booking_status'] ?>
                                            </span>
                                        </td>
                                        <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                        <td>
                                            <?= htmlspecialchars($row['user_name']) ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($row['user_email']) ?></small>
                                        </td>
                                        <td><?= date('M d, Y H:i:s', strtotime($row['changed_at'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-muted text-center">
                Showing last 100 system events
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>