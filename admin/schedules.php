<?php
// admin/schedules.php
session_start();
require_once '../db/connect.php';

// Security check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle NEW schedule creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $lift_id        = (int)$_POST['lift_id'];
    $place_id       = (int)$_POST['place_id'];
    $departure_time = $_POST['departure_time'];
    $arrival_time   = $_POST['arrival_time'];

    $stmt = $conn->prepare("
        INSERT INTO Schedule (lift_id, place_id, departure_time, arrival_time)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiss", $lift_id, $place_id, $departure_time, $arrival_time);

    if ($stmt->execute()) {
        $success_msg = "New schedule created successfully!";
    } else {
        $error_msg = "Error creating schedule: " . $stmt->error;
    }
    $stmt->close();
}

// Handle UPDATE schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $schedule_id    = (int)$_POST['schedule_id'];
    $lift_id        = (int)$_POST['lift_id'];
    $place_id       = (int)$_POST['place_id'];
    $departure_time = $_POST['departure_time'];
    $arrival_time   = $_POST['arrival_time'];

    $stmt = $conn->prepare("
        UPDATE Schedule 
        SET lift_id = ?, place_id = ?, departure_time = ?, arrival_time = ?
        WHERE schedule_id = ?
    ");
    $stmt->bind_param("iissi", $lift_id, $place_id, $departure_time, $arrival_time, $schedule_id);

    if ($stmt->execute()) {
        $success_msg = "Schedule updated successfully!";
    } else {
        $error_msg = "Error updating schedule: " . $stmt->error;
    }
    $stmt->close();
}

// Handle DELETE schedule
if (isset($_GET['delete'])) {
    $schedule_id = (int)$_GET['delete'];

    // Optional: Check if there are bookings before allowing delete
    $check = $conn->query("SELECT COUNT(*) FROM Booking WHERE sched_id = $schedule_id")->fetch_row()[0];
    if ($check > 0) {
        $error_msg = "Cannot delete: This schedule has existing bookings.";
    } else {
        $conn->query("DELETE FROM Schedule WHERE schedule_id = $schedule_id");
        $success_msg = "Schedule deleted successfully!";
    }
}

// Filters
$place_filter  = $_GET['place_id']  ?? '';
$aircraft_filter = $_GET['lift_id'] ?? '';

$where = [];
$params = [];
$types = '';

if ($place_filter) {
    $where[] = "s.place_id = ?";
    $params[] = $place_filter;
    $types .= 'i';
}

if ($aircraft_filter) {
    $where[] = "s.lift_id = ?";
    $params[] = $aircraft_filter;
    $types .= 'i';
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get all schedules
$sql = "
    SELECT 
        s.schedule_id,
        s.place_id,
        s.lift_id,
        p.place_name AS destination,
        l.aircraft_type,
        l.capacity,
        s.departure_time,
        s.arrival_time,
        (SELECT COUNT(*) FROM Booking b WHERE b.sched_id = s.schedule_id) AS booking_count
    FROM Schedule s
    JOIN Place p ON s.place_id = p.place_id
    JOIN Lift l ON s.lift_id = l.lift_id
    $where_clause
    ORDER BY s.departure_time DESC
";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$schedules = $stmt->get_result();

// Get dropdown data
$aircrafts = $conn->query("SELECT lift_id, aircraft_type, capacity FROM Lift ORDER BY aircraft_type");
$destinations = $conn->query("SELECT place_id, place_name FROM Place ORDER BY place_name");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Flight Schedules - AirLyft Admin</title>

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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Flight Schedules Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                <i class="fas fa-plus me-2"></i>Add New Schedule
            </button>
        </div>

        <!-- Messages -->
        <?php if (isset($success_msg) && $success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($error_msg) && $error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Destination</label>
                        <select name="place_id" class="form-select">
                            <option value="">All Destinations</option>
                            <?php
                            $destinations->data_seek(0);
                            while ($p = $destinations->fetch_assoc()): ?>
                                <option value="<?= $p['place_id'] ?>" <?= $place_filter == $p['place_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['place_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Aircraft Type</label>
                        <select name="lift_id" class="form-select">
                            <option value="">All Aircraft</option>
                            <?php
                            $aircrafts->data_seek(0);
                            while ($a = $aircrafts->fetch_assoc()): ?>
                                <option value="<?= $a['lift_id'] ?>" <?= $aircraft_filter == $a['lift_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['aircraft_type']) ?> (<?= $a['capacity'] ?> seats)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Schedules Table -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Destination</th>
                                <th>Aircraft</th>
                                <th>Capacity</th>
                                <th>Departure</th>
                                <th>Arrival</th>
                                <th>Bookings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($schedules->num_rows === 0): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">No schedules found</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($row = $schedules->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold">#<?= $row['schedule_id'] ?></td>
                                        <td><?= htmlspecialchars($row['destination']) ?></td>
                                        <td><?= htmlspecialchars($row['aircraft_type']) ?></td>
                                        <td><?= $row['capacity'] ?> seats</td>
                                        <td>
                                            <?= date('M d, Y', strtotime($row['departure_time'])) ?><br>
                                            <small class="text-muted"><?= date('h:i A', strtotime($row['departure_time'])) ?></small>
                                        </td>
                                        <td>
                                            <?= date('M d, Y', strtotime($row['arrival_time'])) ?><br>
                                            <small class="text-muted"><?= date('h:i A', strtotime($row['arrival_time'])) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?= $row['booking_count'] > 0 ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $row['booking_count'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1 edit-schedule"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editScheduleModal"
                                                data-id="<?= $row['schedule_id'] ?>"
                                                data-lift="<?= $row['lift_id'] ?>"
                                                data-place="<?= $row['place_id'] ?>"
                                                data-departure="<?= date('Y-m-d\TH:i', strtotime($row['departure_time'])) ?>"
                                                data-arrival="<?= date('Y-m-d\TH:i', strtotime($row['arrival_time'])) ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?= $row['schedule_id'] ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Delete this schedule? This action cannot be undone.')">
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

        <!-- Add New Schedule Modal -->
        <div class="modal fade" id="addScheduleModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Flight Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="create">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Aircraft</label>
                                    <select name="lift_id" class="form-select" required>
                                        <option value="">Select aircraft...</option>
                                        <?php
                                        $aircrafts->data_seek(0);
                                        while ($a = $aircrafts->fetch_assoc()): ?>
                                            <option value="<?= $a['lift_id'] ?>">
                                                <?= htmlspecialchars($a['aircraft_type']) ?> (<?= $a['capacity'] ?> seats)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Destination</label>
                                    <select name="place_id" class="form-select" required>
                                        <option value="">Select destination...</option>
                                        <?php
                                        $destinations->data_seek(0);
                                        while ($p = $destinations->fetch_assoc()): ?>
                                            <option value="<?= $p['place_id'] ?>">
                                                <?= htmlspecialchars($p['place_name']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Departure Time</label>
                                    <input type="datetime-local" name="departure_time" class="form-control" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Arrival Time</label>
                                    <input type="datetime-local" name="arrival_time" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Schedule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Schedule Modal -->
        <div class="modal fade" id="editScheduleModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Flight Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="schedule_id" id="edit_schedule_id">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Aircraft</label>
                                    <select name="lift_id" id="edit_lift_id" class="form-select" required>
                                        <?php
                                        $aircrafts->data_seek(0);
                                        while ($a = $aircrafts->fetch_assoc()): ?>
                                            <option value="<?= $a['lift_id'] ?>">
                                                <?= htmlspecialchars($a['aircraft_type']) ?> (<?= $a['capacity'] ?> seats)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Destination</label>
                                    <select name="place_id" id="edit_place_id" class="form-select" required>
                                        <?php
                                        $destinations->data_seek(0);
                                        while ($p = $destinations->fetch_assoc()): ?>
                                            <option value="<?= $p['place_id'] ?>">
                                                <?= htmlspecialchars($p['place_name']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Departure Time</label>
                                    <input type="datetime-local" name="departure_time" id="edit_departure_time" class="form-control" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Arrival Time</label>
                                    <input type="datetime-local" name="arrival_time" id="edit_arrival_time" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.edit-schedule').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_schedule_id').value = this.dataset.id;
                document.getElementById('edit_lift_id').value = this.dataset.lift;
                document.getElementById('edit_place_id').value = this.dataset.place;
                document.getElementById('edit_departure_time').value = this.dataset.departure;
                document.getElementById('edit_arrival_time').value = this.dataset.arrival;
            });
        });
    </script>
</body>

</html>

</html>