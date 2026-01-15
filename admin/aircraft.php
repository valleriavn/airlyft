<?php
// admin/aircraft.php
session_start();
require_once '../db/connect.php';

// Security: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

$success_msg = $error_msg = '';

// 1. Handle ADD new aircraft
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $aircraft_type = trim($_POST['aircraft_type']);
    $aircraft_name = trim($_POST['aircraft_name']);
    $capacity      = (int)$_POST['capacity'];
    $price         = (float)$_POST['price'];
    $lift_status   = $_POST['lift_status'];

    if (empty($aircraft_type) || empty($aircraft_name) || $capacity <= 0) {
        $error_msg = "Please provide valid aircraft details.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Lift (aircraft_type, aircraft_name, capacity, price, lift_status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssids", $aircraft_type, $aircraft_name, $capacity, $price, $lift_status);

        if ($stmt->execute()) {
            $success_msg = "New aircraft added successfully!";
        } else {
            $error_msg = "Error adding aircraft: " . $stmt->error;
        }
        $stmt->close();
    }
}

// 2. Handle UPDATE aircraft
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $lift_id       = (int)$_POST['lift_id'];
    $aircraft_type = trim($_POST['aircraft_type']);
    $aircraft_name = trim($_POST['aircraft_name']);
    $capacity      = (int)$_POST['capacity'];
    $price         = (float)$_POST['price'];
    $lift_status   = $_POST['lift_status'];

    $stmt = $conn->prepare("UPDATE Lift SET aircraft_type = ?, aircraft_name = ?, capacity = ?, price = ?, lift_status = ? WHERE lift_id = ?");
    $stmt->bind_param("ssidsi", $aircraft_type, $aircraft_name, $capacity, $price, $lift_status, $lift_id);

    if ($stmt->execute()) {
        $success_msg = "Aircraft updated successfully!";
    } else {
        $error_msg = "Error updating aircraft: " . $stmt->error;
    }
    $stmt->close();
}

// 3. Handle DELETE aircraft (with safety check)
if (isset($_GET['delete'])) {
    $lift_id = (int)$_GET['delete'];

    // Check if this aircraft is used in any schedule
    $check = $conn->query("SELECT COUNT(*) FROM Schedule WHERE lift_id = $lift_id")->fetch_row()[0];

    if ($check > 0) {
        $error_msg = "Cannot delete: This aircraft is used in " . $check . " active schedule(s).";
    } else {
        $conn->query("DELETE FROM Lift WHERE lift_id = $lift_id");
        $success_msg = "Aircraft deleted successfully!";
    }
}

// Get all aircraft
$aircrafts = $conn->query("SELECT * FROM Lift ORDER BY lift_id ASC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Fleet - AirLyft Admin</title>

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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Manage Aircraft Fleet</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAircraftModal">
                <i class="fas fa-plus me-2"></i>Add New Aircraft
            </button>
        </div>

        <!-- Messages -->
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Aircraft List -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <?php if ($aircrafts->num_rows === 0): ?>
                    <p class="text-center text-muted py-5">No aircraft types registered yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Model</th>
                                    <th>Name</th>
                                    <th>Capacity</th>
                                    <th>Base Price</th>
                                    <th>Status</th>
                                    <th>Schedules</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $aircrafts->fetch_assoc()): ?>
                                    <?php
                                    $used_count = $conn->query("SELECT COUNT(*) FROM Schedule WHERE lift_id = {$row['lift_id']}")->fetch_row()[0];
                                    ?>
                                    <tr class="align-middle">
                                        <td class="fw-bold">#<?= $row['lift_id'] ?></td>
                                        <td class="fw-bold text-primary"><?= htmlspecialchars($row['aircraft_type']) ?></td>
                                        <td><?= htmlspecialchars($row['aircraft_name']) ?></td>
                                        <td><i class="fas fa-users text-muted me-1"></i><?= $row['capacity'] ?></td>
                                        <td>₱<?= number_format($row['price'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?=
                                                                    $row['lift_status'] === 'available' ? 'success' : ($row['lift_status'] === 'maintenance' ? 'warning' : 'danger')
                                                                    ?>">
                                                <?= ucfirst($row['lift_status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $used_count > 0 ? 'bg-info' : 'bg-light text-dark border' ?>">
                                                <?= $used_count ?>
                                            </span>
                                        </td>
                                        <td class="text-nowrap">
                                            <!-- View button -->
                                            <button class="btn btn-sm btn-outline-info me-1 view-aircraft"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewAircraftModal"
                                                data-id="<?= $row['lift_id'] ?>"
                                                data-type="<?= htmlspecialchars($row['aircraft_type'], ENT_QUOTES) ?>"
                                                data-name="<?= htmlspecialchars($row['aircraft_name'], ENT_QUOTES) ?>"
                                                data-capacity="<?= $row['capacity'] ?>"
                                                data-price="<?= number_format($row['price'], 2) ?>"
                                                data-status="<?= ucfirst($row['lift_status']) ?>"
                                                data-badge="<?= $row['lift_status'] === 'available' ? 'success' : ($row['lift_status'] === 'maintenance' ? 'warning' : 'danger') ?>"
                                                data-schedules="<?= $used_count ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            <!-- Edit button -->
                                            <button class="btn btn-sm btn-outline-primary me-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editModal"
                                                data-id="<?= $row['lift_id'] ?>"
                                                data-type="<?= htmlspecialchars($row['aircraft_type'], ENT_QUOTES) ?>"
                                                data-name="<?= htmlspecialchars($row['aircraft_name'], ENT_QUOTES) ?>"
                                                data-capacity="<?= $row['capacity'] ?>"
                                                data-price="<?= $row['price'] ?>"
                                                data-status="<?= $row['lift_status'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <!-- Delete -->
                                            <a href="?delete=<?= $row['lift_id'] ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Delete this aircraft type?\nThis cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- View Aircraft Modal -->
        <div class="modal fade" id="viewAircraftModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Aircraft Specifications</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <div class="mb-4">
                            <i class="fas fa-plane fa-4x text-primary shadow-sm p-3 rounded-circle bg-light"></i>
                        </div>
                        <h3 id="v-type" class="mb-1"></h3>
                        <p id="v-name" class="text-muted mb-3"></p>

                        <div class="row g-3 text-start mt-2">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <small class="text-muted d-block">Passenger Capacity</small>
                                    <span class="fw-bold h5 mb-0" id="v-capacity"></span> Seats
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <small class="text-muted d-block">Base Pricing</small>
                                    <span class="fw-bold h5 mb-0">₱ <span id="v-price"></span></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <small class="text-muted d-block">Operational Status</small>
                                    <span id="v-status" class="badge"></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <small class="text-muted d-block">Active Schedules</small>
                                    <span class="fw-bold h5 mb-0" id="v-schedules"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add New Aircraft Modal -->
        <div class="modal fade" id="addAircraftModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Aircraft</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add">

                            <div class="mb-3">
                                <label class="form-label">Aircraft Type (Model)</label>
                                <input type="text" name="aircraft_type" class="form-control" required
                                    placeholder="e.g. Cessna 206-1">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Display Name</label>
                                <input type="text" name="aircraft_name" class="form-control" required
                                    placeholder="e.g. Cessna 206">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Capacity</label>
                                    <input type="number" name="capacity" class="form-control" required min="1" placeholder="5">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Base Price (₱)</label>
                                    <input type="number" name="price" class="form-control" required min="0" step="0.01" placeholder="20000">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="lift_status" class="form-select">
                                    <option value="available">Available</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Aircraft</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Aircraft Modal -->
        <div class="modal fade" id="editModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Aircraft</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="lift_id" id="edit_lift_id">

                            <div class="mb-3">
                                <label class="form-label">Aircraft Type</label>
                                <input type="text" name="aircraft_type" id="edit_aircraft_type" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Display Name</label>
                                <input type="text" name="aircraft_name" id="edit_aircraft_name" class="form-control" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Capacity</label>
                                    <input type="number" name="capacity" id="edit_capacity" class="form-control" required min="1">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Base Price (₱)</label>
                                    <input type="number" name="price" id="edit_price" class="form-control" required min="0" step="0.01">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="lift_status" id="edit_lift_status" class="form-select">
                                    <option value="available">Available</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="inactive">Inactive</option>
                                </select>
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

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit Modal Auto-fill
            const editModal = document.getElementById('editModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;

                document.getElementById('edit_lift_id').value = button.getAttribute('data-id');
                document.getElementById('edit_aircraft_type').value = button.getAttribute('data-type');
                document.getElementById('edit_aircraft_name').value = button.getAttribute('data-name');
                document.getElementById('edit_capacity').value = button.getAttribute('data-capacity');
                document.getElementById('edit_price').value = button.getAttribute('data-price');
                document.getElementById('edit_lift_status').value = button.getAttribute('data-status');
            });

            // View Modal Auto-fill
            const viewModal = document.getElementById('viewAircraftModal');
            viewModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;

                document.getElementById('v-type').textContent = button.getAttribute('data-type');
                document.getElementById('v-name').textContent = button.getAttribute('data-name');
                document.getElementById('v-capacity').textContent = button.getAttribute('data-capacity');
                document.getElementById('v-price').textContent = button.getAttribute('data-price');
                document.getElementById('v-schedules').textContent = button.getAttribute('data-schedules');

                const status = document.getElementById('v-status');
                status.textContent = button.getAttribute('data-status');
                status.className = 'badge bg-' + button.getAttribute('data-badge');
            });
        });
    </script>
</body>

</html>
</body>

</html>