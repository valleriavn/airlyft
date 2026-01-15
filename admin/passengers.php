<?php
// admin/passengers.php
session_start();
require_once '../db/connect.php';

// Security: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle UPDATE passenger
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $passenger_id = (int)$_POST['passenger_id'];
    $f_name       = trim($_POST['passenger_f_name']);
    $l_name       = trim($_POST['passenger_l_name']);
    $phone        = trim($_POST['passenger_phone_number']);
    $address      = trim($_POST['address']);
    $insurance    = $_POST['insurance'];

    $stmt = $conn->prepare("UPDATE passenger SET passenger_f_name = ?, passenger_l_name = ?, passenger_phone_number = ?, address = ?, insurance = ? WHERE passenger_id = ?");
    $stmt->bind_param("sssssi", $f_name, $l_name, $phone, $address, $insurance, $passenger_id);

    if ($stmt->execute()) {
        $success_msg = "Passenger updated successfully!";
    } else {
        $error_msg = "Error updating passenger: " . $stmt->error;
    }
    $stmt->close();
}

// Handle DELETE passenger
if (isset($_GET['delete'])) {
    $passenger_id = (int)$_GET['delete'];
    if ($conn->query("DELETE FROM passenger WHERE passenger_id = $passenger_id")) {
        $success_msg = "Passenger deleted successfully!";
    } else {
        $error_msg = "Error deleting passenger.";
    }
}

// Main Query
$sql = "
    SELECT 
        p.*, 
        CONCAT(u.first_name, ' ', u.last_name) AS linked_user,
        b.booking_status
    FROM passenger p
    LEFT JOIN Users u ON p.user_id = u.user_id
    LEFT JOIN Booking b ON p.booking_id = b.booking_id
    ORDER BY p.passenger_id DESC
    LIMIT 100
";
$passengers = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Passengers - AirLyft Admin</title>

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
        <h2 class="mb-4">Passenger List</h2>

        <!-- Messages -->
        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Address</th>
                                <th>Booking Ref</th>
                                <th>Insurance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($passengers->num_rows === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">No passengers found.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($row = $passengers->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold">#<?= $row['passenger_id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['passenger_f_name'] . ' ' . $row['passenger_l_name']) ?></strong>
                                            <?php if ($row['linked_user']): ?>
                                                <br><small class="text-muted">User: <?= htmlspecialchars($row['linked_user']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['passenger_phone_number']) ?></td>
                                        <td><?= htmlspecialchars($row['address']) ?></td>
                                        <td>
                                            <?php if ($row['booking_id']): ?>
                                                <a href="bookings.php?id=<?= $row['booking_id'] ?>" class="text-decoration-none">
                                                    #<?= $row['booking_id'] ?>
                                                </a>
                                                <span class="badge bg-secondary"><?= $row['booking_status'] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['insurance'] === 'yes'): ?>
                                                <span class="badge bg-success">Yes</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-nowrap">
                                            <button class="btn btn-sm btn-outline-primary me-1 edit-passenger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editPassengerModal"
                                                data-id="<?= $row['passenger_id'] ?>"
                                                data-fname="<?= htmlspecialchars($row['passenger_f_name']) ?>"
                                                data-lname="<?= htmlspecialchars($row['passenger_l_name']) ?>"
                                                data-phone="<?= htmlspecialchars($row['passenger_phone_number']) ?>"
                                                data-address="<?= htmlspecialchars($row['address']) ?>"
                                                data-insurance="<?= $row['insurance'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?= $row['passenger_id'] ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Delete this passenger record?')">
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

    <!-- Edit Passenger Modal -->
    <div class="modal fade" id="editPassengerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Passenger</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="passenger_id" id="edit_passenger_id">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" name="passenger_f_name" id="edit_fname" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="passenger_l_name" id="edit_lname" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="passenger_phone_number" id="edit_phone" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" id="edit_address" class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Insurance</label>
                                <select name="insurance" id="edit_insurance" class="form-select">
                                    <option value="yes">Yes (Insured)</option>
                                    <option value="no">No</option>
                                </select>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.edit-passenger').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_passenger_id').value = this.dataset.id;
                document.getElementById('edit_fname').value = this.dataset.fname;
                document.getElementById('edit_lname').value = this.dataset.lname;
                document.getElementById('edit_phone').value = this.dataset.phone;
                document.getElementById('edit_address').value = this.dataset.address;
                document.getElementById('edit_insurance').value = this.dataset.insurance;
            });
        });
    </script>
</body>

</html>