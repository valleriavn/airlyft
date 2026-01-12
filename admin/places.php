<?php
// admin/places.php
session_start();
require_once '../db/connect.php';

// Security: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

$success_msg = $error_msg = '';

// 1. Handle ADD new place
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $place_name        = trim($_POST['place_name']);
    $location    = trim($_POST['location']);
    $description = trim($_POST['description']);

    if (empty($place_name) || empty($location)) {
        $error_msg = "Name and Location are required fields.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO Place (place_name, location, description) 
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("sss", $place_name, $location, $description);
        
        if ($stmt->execute()) {
            $success_msg = "New destination added successfully!";
        } else {
            $error_msg = "Error adding destination: " . $stmt->error;
        }
        $stmt->close();
    }
}

// 2. Handle UPDATE place
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $place_id    = (int)$_POST['place_id'];
    $place_name        = trim($_POST['place_name']);
    $location    = trim($_POST['location']);
    $description = trim($_POST['description']);

    $stmt = $conn->prepare("
        UPDATE Place 
        SET place_name = ?, location = ?, description = ? 
        WHERE place_id = ?
    ");
    $stmt->bind_param("sssi", $place_name, $location, $description, $place_id);
    
    if ($stmt->execute()) {
        $success_msg = "Destination updated successfully!";
    } else {
        $error_msg = "Error updating destination: " . $stmt->error;
    }
    $stmt->close();
}

// 3. Handle DELETE place (with safety check)
if (isset($_GET['delete'])) {
    $place_id = (int)$_GET['delete'];
    
    // Check if this place is used in any schedule
    $check = $conn->query("SELECT COUNT(*) FROM Schedule WHERE place_id = $place_id")->fetch_row()[0];
    
    if ($check > 0) {
        $error_msg = "Cannot delete: This destination is used in " . $check . " active schedule(s).";
    } else {
        $conn->query("DELETE FROM Place WHERE place_id = $place_id");
        $success_msg = "Destination deleted successfully!";
    }
}

// Get all places
$places = $conn->query("
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM Schedule s WHERE s.place_id = p.place_id) AS schedule_count
    FROM Place p 
    ORDER BY p.place_name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Manage Destinations - AirLyft Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../admin/admin_style.css">

</head>
<body>

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Manage Destinations</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPlaceModal">
            <i class="fas fa-plus me-2"></i>Add New Destination
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

    <!-- Destinations Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if ($places->num_rows === 0): ?>
                <p class="text-center text-muted py-5">No destinations added yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Destination Name</th>
                                <th>Location</th>
                                <th>Description</th>
                                <th>Used in Schedules</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $places->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $row['place_id'] ?></td>
                                <td><?= htmlspecialchars($row['place_name']) ?></td>
                                <td><?= htmlspecialchars($row['location']) ?></td>
                                <td>
                                    <?= $row['description'] 
                                        ? htmlspecialchars(substr($row['description'], 0, 80)) . (strlen($row['description']) > 80 ? '...' : '') 
                                        : '<span class="text-muted">No description</span>' ?>
                                </td>
                                <td>
                                    <span class="badge <?= $row['schedule_count'] > 0 ? 'bg-info' : 'bg-secondary' ?>">
                                        <?= $row['schedule_count'] ?> schedule<?= $row['schedule_count'] !== 1 ? 's' : '' ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- Edit button -->
                                    <button class="btn btn-sm btn-outline-primary me-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal"
                                            data-id="<?= $row['place_id'] ?>"
                                            data-name="<?= htmlspecialchars($row['place_name'], ENT_QUOTES) ?>"
                                            data-location="<?= htmlspecialchars($row['location'], ENT_QUOTES) ?>"
                                            data-description="<?= htmlspecialchars($row['description'] ?? '', ENT_QUOTES) ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>

                                    <!-- Delete -->
                                    <a href="?delete=<?= $row['place_id'] ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Delete this destination?\nThis cannot be undone.')">
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

    <!-- Add New Destination Modal -->
    <div class="modal fade" id="addPlaceModal" tabindex="-1" aria-labelledby="addPlaceLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPlaceLabel">Add New Destination</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Destination Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Location (City/Country) *</label>
                            <input type="text" name="location" class="form-control" required 
                                   placeholder="e.g. Boracay, Philippines">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4"
                                      placeholder="Brief description of the destination, attractions, etc..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Destination</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Destination Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Destination</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="place_id" id="edit_place_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Destination Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" id="edit_location" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="5"></textarea>
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

<!-- JavaScript for Edit Modal Auto-fill -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        
        document.getElementById('edit_place_id').value = button.getAttribute('data-id');
        document.getElementById('edit_place_name').value     = button.getAttribute('data-name');
        document.getElementById('edit_location').value = button.getAttribute('data-location');
        document.getElementById('edit_description').value = button.getAttribute('data-description');
    });
});
</script>
</body>
</html>