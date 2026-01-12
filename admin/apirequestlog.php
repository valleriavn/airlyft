<?php
// admin/api_request_log.php
session_start();
require_once '../db/connect.php';

// Security: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Quick stats
$stats = [
    'total_requests'    => $conn->query("SELECT COUNT(*) FROM APIRequestLog")->fetch_row()[0],
    'create_user'       => $conn->query("SELECT COUNT(*) FROM APIRequestLog WHERE request_type = 'CreateUser'")->fetch_row()[0],
    'update_user'       => $conn->query("SELECT COUNT(*) FROM APIRequestLog WHERE request_type = 'UpdateUser'")->fetch_row()[0],
    'today_requests'    => $conn->query("SELECT COUNT(*) FROM APIRequestLog WHERE DATE(time_stamp) = CURDATE()")->fetch_row()[0],
];

// Filters
$source_filter = $_GET['source'] ?? '';
$target_filter = $_GET['target'] ?? '';
$type_filter   = $_GET['type']   ?? '';
$date_from     = $_GET['date_from'] ?? '';
$date_to       = $_GET['date_to'] ?? '';

$where = [];
$params = [];
$types = '';

if ($source_filter) {
    $where[] = "source_system LIKE ?";
    $params[] = "%$source_filter%";
    $types .= 's';
}

if ($target_filter) {
    $where[] = "target_system LIKE ?";
    $params[] = "%$target_filter%";
    $types .= 's';
}

if ($type_filter && in_array($type_filter, ['CreateUser','UpdateUser'])) {
    $where[] = "request_type = ?";
    $params[] = $type_filter;
    $types .= 's';
}

if ($date_from) {
    $where[] = "time_stamp >= ?";
    $params[] = $date_from . ' 00:00:00';
    $types .= 's';
}

if ($date_to) {
    $where[] = "time_stamp <= ?";
    $params[] = $date_to . ' 23:59:59';
    $types .= 's';
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Main query
$sql = "
    SELECT 
        request_id,
        source_system,
        target_system,
        request_type,
        time_stamp
    FROM APIRequestLog
    $where_clause
    ORDER BY request_id DESC
    LIMIT 200
";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>API Request Log - AirLyft Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../admin/admin_style.css">

</head>
<body>

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h2 class="mb-4">API Request Log</h2>

    <!-- Quick Stats -->
    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total Requests</h6>
                    <h4><?= number_format($stats['total_requests']) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">CreateUser Requests</h6>
                    <h4><?= number_format($stats['create_user']) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">UpdateUser Requests</h6>
                    <h4><?= number_format($stats['update_user']) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Today's Requests</h6>
                    <h4><?= number_format($stats['today_requests']) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Source System</label>
                    <input type="text" name="source" class="form-control" placeholder="e.g. PartnerApp" value="<?= htmlspecialchars($source_filter) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Target System</label>
                    <input type="text" name="target" class="form-control" placeholder="e.g. AirLyftAPI" value="<?= htmlspecialchars($target_filter) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Request Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="CreateUser" <?= $type_filter === 'CreateUser' ? 'selected' : '' ?>>CreateUser</option>
                        <option value="UpdateUser" <?= $type_filter === 'UpdateUser' ? 'selected' : '' ?>>UpdateUser</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                </div>

                <div class="col-md-2">
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

    <!-- Log Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Source System</th>
                            <th>Target System</th>
                            <th>Request Type</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($logs->num_rows === 0): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                No API request logs found matching your filters
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = $logs->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $row['request_id'] ?></td>
                                <td><?= htmlspecialchars($row['source_system'] ?: '—') ?></td>
                                <td><?= htmlspecialchars($row['target_system'] ?: '—') ?></td>
                                <td>
                                    <span class="badge bg-<?= $row['request_type'] === 'CreateUser' ? 'primary' : 'info' ?>">
                                        <?= $row['request_type'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?= date('M d, Y h:i:s A', strtotime($row['time_stamp'])) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-center text-muted">
            Showing up to 200 most recent API request logs
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>