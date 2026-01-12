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
    $capacity      = (int)$_POST['capacity'];

    if (empty($aircraft_type) || $capacity <= 0) {
        $error_msg = "Please provide valid aircraft type and capacity (must be > 0)";
    } else {
        $stmt = $conn->prepare("INSERT INTO Lift (aircraft_type, capacity) VALUES (?, ?)");
        $stmt->bind_param("si", $aircraft_type, $capacity);
        
        if ($stmt->execute()) {
            $success_msg = "New aircraft type added successfully!";
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
    $capacity      = (int)$_POST['capacity'];

    $stmt = $conn->prepare("UPDATE Lift SET aircraft_type = ?, capacity = ? WHERE lift_id = ?");
    $stmt->bind_param("sii", $aircraft_type, $capacity, $lift_id);
    
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
        $success_msg = "Aircraft type deleted successfully!";
    }
}

// Get all aircraft
$aircrafts = $conn->query("SELECT * FROM Lift ORDER BY aircraft_type");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Manage Fleet - AirLyft Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../admin/admin_style.css">

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
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if ($aircrafts->num_rows === 0): ?>
                <p class="text-center text-muted py-5">No aircraft types registered yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Aircraft Type</th>
                                <th>Capacity (seats)</th>
                                <th>Used in Schedules</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $aircrafts->fetch_assoc()): ?>
                            <?php
                            $used_count = $conn->query("SELECT COUNT(*) FROM Schedule WHERE lift_id = {$row['lift_id']}")->fetch_row()[0];
                            ?>
                            <tr>
                                <td>#<?= $row['lift_id'] ?></td>
                                <td><?= htmlspecialchars($row['aircraft_type']) ?></td>
                                <td><?= $row['capacity'] ?></td>
                                <td>
                                    <span class="badge <?= $used_count > 0 ? 'bg-info' : 'bg-secondary' ?>">
                                        <?= $used_count ?> schedule<?= $used_count !== 1 ? 's' : '' ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- Edit button - opens modal -->
                                    <button class="btn btn-sm btn-outline-primary me-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal"
                                            data-id="<?= $row['lift_id'] ?>"
                                            data-type="<?= htmlspecialchars($row['aircraft_type'], ENT_QUOTES) ?>"
                                            data-capacity="<?= $row['capacity'] ?>">
                                        <i class="fas fa-edit"></i> Edit
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

    <!-- Add New Aircraft Modal -->
    <div class="modal fade" id="addAircraftModal" tabindex="-1" aria-labelledby="addAircraftLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAircraftLabel">Add New Aircraft Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Aircraft Type / Model</label>
                            <input type="text" name="aircraft_type" class="form-control" required 
                                   placeholder="e.g. Gulfstream G650, Bombardier Global 7500">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Passenger Capacity</label>
                            <input type="number" name="capacity" class="form-control" required min="1" 
                                   placeholder="e.g. 12, 19">
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
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Aircraft</h5>
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
                            <label class="form-label">Capacity (seats)</label>
                            <input type="number" name="capacity" id="edit_capacity" class="form-control" required min="1">
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

<!-- Edit Modal Auto-fill -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        
        const id       = button.getAttribute('data-id');
        const type     = button.getAttribute('data-type');
        const capacity = button.getAttribute('data-capacity');
        
        document.getElementById('edit_lift_id').value       = id;
        document.getElementById('edit_aircraft_type').value = type;
        document.getElementById('edit_capacity').value      = capacity;
    });
});
</script>
</body>
</html>