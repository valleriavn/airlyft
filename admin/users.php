<?php
// users.php
session_start();
require_once '../db/connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['new_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'];
    $stmt = $conn->prepare("UPDATE Users SET role = ? WHERE user_id = ?");
    $stmt->bind_param("si", $new_role, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: ../admin/users.php?msg=Role updated");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    try {
        // 1. Delete related chat messages first (linked to sessions)
        $conn->query("DELETE FROM ai_chat_messages WHERE session_id IN (SELECT session_id FROM ai_chat_sessions WHERE user_id = $user_id)");

        // 2. Delete related chat sessions
        $conn->query("DELETE FROM ai_chat_sessions WHERE user_id = $user_id");

        // 3. Then delete the user
        if ($conn->query("DELETE FROM Users WHERE user_id = $user_id")) {
            header("Location: ../admin/users.php?msg=User deleted successfully");
        } else {
            header("Location: ../admin/users.php?error=Could not delete user. They might have active bookings or other records.");
        }
    } catch (mysqli_sql_exception $e) {
        header("Location: ../admin/users.php?error=Database error: " . $e->getMessage());
    }
    exit();
}


$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where = $search ? "WHERE first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%'" : "";
$users = $conn->query("SELECT *, CONCAT(first_name, ' ', last_name) AS name FROM Users $where ORDER BY user_id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Users Management - AirLyft Admin</title>

    <link rel="icon" href="../assets/img/icon.png" type="image/png">
    <link rel="shortcut icon" href="../assets/img/icon.png" type="image/png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2 class="mb-4">Manage Users</h2>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_GET['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="get" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search by name or email" value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['user_id'] ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone'] ?: '-') ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                        <select name="new_role" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                            <option value="Customer" <?= $row['role'] === 'Customer' ? 'selected' : '' ?>>Customer</option>
                                            <option value="Staff" <?= $row['role'] === 'Staff' ? 'selected' : '' ?>>Staff</option>
                                            <option value="Admin" <?= $row['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info text-white view-user"
                                        data-bs-toggle="modal"
                                        data-bs-target="#userModal"
                                        data-id="<?= $row['user_id'] ?>"
                                        data-name="<?= htmlspecialchars($row['name']) ?>"
                                        data-email="<?= htmlspecialchars($row['email']) ?>"
                                        data-phone="<?= htmlspecialchars($row['phone'] ?: 'N/A') ?>"
                                        data-role="<?= $row['role'] ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="?delete=<?= $row['user_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>User ID:</strong> <span id="view-id"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Full Name:</strong> <span id="view-name"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Email:</strong> <span id="view-email"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Phone:</strong> <span id="view-phone"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Role:</strong> <span id="view-role" class="badge bg-primary"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.view-user').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('view-id').textContent = this.dataset.id;
                document.getElementById('view-name').textContent = this.dataset.name;
                document.getElementById('view-email').textContent = this.dataset.email;
                document.getElementById('view-phone').textContent = this.dataset.phone;
                document.getElementById('view-role').textContent = this.dataset.role;
            });
        });
    </script>
</body>

</html>