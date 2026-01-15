<?php
// admin/ai_chat_history.php
session_start();
require_once '../db/connect.php';

// Security: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle View Transcript
$view_session_id = $_GET['view'] ?? null;
$chat_transcript = null;
if ($view_session_id) {
    $stmt = $conn->prepare("
        SELECT m.*, s.session_token 
        FROM ai_chat_messages m
        JOIN ai_chat_sessions s ON m.session_id = s.session_id
        WHERE m.session_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param("i", $view_session_id);
    $stmt->execute();
    $chat_transcript = $stmt->get_result();
}

// Main Session List Query
$sql = "
    SELECT 
        s.session_id, s.session_token, s.start_time, s.last_activity, s.message_count,
        CONCAT(u.first_name, ' ', u.last_name) AS user_name, u.email
    FROM ai_chat_sessions s
    LEFT JOIN Users u ON s.user_id = u.user_id
    ORDER BY s.last_activity DESC
    LIMIT 50
";
$sessions = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AI Chat History - AirLyft Admin</title>

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
        <h2 class="mb-4">AI Chat History</h2>

        <?php if ($view_session_id && $chat_transcript): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Transcript: Session #<?= htmlspecialchars($view_session_id) ?></h5>
                    <a href="ai_chat_history.php" class="btn btn-sm btn-light">Close</a>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <?php while ($msg = $chat_transcript->fetch_assoc()): ?>
                        <div class="mb-3 <?= $msg['sender_type'] === 'user' ? 'text-end' : 'text-start' ?>">
                            <div class="d-inline-block p-3 rounded 
                            <?= $msg['sender_type'] === 'user' ? 'bg-primary text-white' : 'bg-light border' ?>"
                                style="max-width: 80%;">
                                <small class="d-block mb-1 opacity-75">
                                    <?= $msg['sender_type'] === 'user' ? 'User' : 'AI Agent' ?>
                                    • <?= date('M d H:i', strtotime($msg['created_at'])) ?>
                                </small>
                                <?= nl2br(htmlspecialchars($msg['message'])) ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Session ID</th>
                                <th>User</th>
                                <th>Started</th>
                                <th>Last Activity</th>
                                <th>Msgs</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($sessions->num_rows === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">No chat sessions found.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($row = $sessions->fetch_assoc()): ?>
                                    <tr class="<?= $view_session_id == $row['session_id'] ? 'table-warning' : '' ?>">
                                        <td>#<?= $row['session_id'] ?></td>
                                        <td>
                                            <?php if ($row['user_name']): ?>
                                                <?= htmlspecialchars($row['user_name']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($row['email']) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">Guest</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('M d, Y H:i', strtotime($row['start_time'])) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($row['last_activity'])) ?></td>
                                        <td><?= $row['message_count'] ?></td>
                                        <td>
                                            <a href="?view=<?= $row['session_id'] ?>" class="btn btn-sm btn-info text-white">
                                                <i class="fas fa-eye"></i> View
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>