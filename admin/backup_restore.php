<?php
session_start();
require_once '../db/connect.php';

// Security: Only Admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Directory for backups
$backup_dir = __DIR__ . '/backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Check permissions
if (!is_writable($backup_dir)) {
    $permission_error = "Backup directory is not writable. Please check server permissions.";
}

// Handle Create Backup
if (isset($_POST['create_backup'])) {
    $filename = 'airlyft_backup_' . date('Ymd_His') . '.sql';
    $filepath = $backup_dir . $filename;
    
    $cmd = sprintf(
        '"C:\xampp\mysql\bin\mysqldump" --user=%s --password=%s --host=%s %s > %s',
        escapeshellarg($dbuser),
        escapeshellarg($dbpass),
        escapeshellarg($dbhost),
        escapeshellarg($db),
        escapeshellarg($filepath)
    );
    
    exec($cmd . " 2>&1", $output, $return_var);
    
    if ($return_var === 0) {
        $_SESSION['success'] = "Backup created successfully!";
    } else {
        $_SESSION['error'] = "Backup failed. Code: $return_var. " . implode("\n", $output);
    }
    header("Location: ../admin/backup_restore.php");
    exit();
}

// Handle Restore
if (isset($_FILES['restore_file']) && $_FILES['restore_file']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['restore_file']['name'], PATHINFO_EXTENSION);
    
    if (strtolower($ext) === 'sql') {
        $cmd = sprintf(
            '"C:\xampp\mysql\bin\mysql" --user=%s --password=%s --host=%s %s < %s',
            escapeshellarg($dbuser),
            escapeshellarg($dbpass),
            escapeshellarg($dbhost),
            escapeshellarg($db),
            escapeshellarg($_FILES['restore_file']['tmp_name'])
        );
        
        exec($cmd . " 2>&1", $output, $return_var);
        
        if ($return_var === 0) {
            $_SESSION['success'] = "Database restored successfully!";
        } else {
            $_SESSION['error'] = "Restore failed. Code: $return_var. " . implode("\n", $output);
        }
    } else {
        $_SESSION['error'] = "Invalid file format. Please upload a .sql file.";
    }
    header("Location: ../admin/backup_restore.php");
    exit();
}

// Handle Delete
if (isset($_POST['delete_backup'])) {
    $file = $backup_dir . basename($_POST['delete_backup']);
    if (file_exists($file) && is_writable($file)) {
        if (unlink($file)) {
            $_SESSION['success'] = "Backup deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete backup.";
        }
    } else {
        $_SESSION['error'] = "Backup file not found.";
    }
    header("Location: ../admin/backup_restore.php");
    exit();
}

// Get list of backups
$backups = array_filter(glob($backup_dir . '*.sql'), 'is_file');
usort($backups, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Backup & Restore | AirLyft Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <?php include 'sidebar.php'; ?>
</head>
<body>

<div class="main-content">
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <h2 class="mb-4">Backup & Restore</h2>
        
        <?php if (isset($permission_error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($permission_error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Create Backup -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 pt-3">
                        <h5 class="mb-0"><i class="fas fa-download text-primary me-2"></i>Create Backup</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Create a full backup of the AirLyft database. The file will be saved on the server.</p>
                        <form method="POST">
                            <button type="submit" name="create_backup" class="btn btn-primary">
                                <i class="fas fa-database me-2"></i>Generate Backup
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Restore Backup -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 pt-3">
                        <h5 class="mb-0"><i class="fas fa-upload text-danger me-2"></i>Restore Database</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Warning: experimental</p>
                        <p class="text-muted small">Restoring from a backup will <strong>overwrite</strong> all current data. This action cannot be undone.</p>
                        
                        <form method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                             <input type="file" name="restore_file" class="form-control" accept=".sql" required>
                             <button type="submit" class="btn btn-danger text-nowrap" onclick="return confirm('Are you sure? Current data will be LOST.')">
                                 Restore
                             </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Existing Backups List -->
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-white border-0 pt-3">
                <h5 class="mb-0">Existing Backups</h5>
            </div>
            <div class="card-body">
                <?php if (empty($backups)): ?>
                    <p class="text-muted text-center my-4">No backups found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>File Name</th>
                                    <th>Size</th>
                                    <th>Created At</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $file): 
                                    $basename = basename($file);
                                    $size = round(filesize($file) / 1024, 2);
                                    $time = date('M d, Y h:i A', filemtime($file));
                                ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-file-code text-muted me-2"></i>
                                        <?= htmlspecialchars($basename) ?>
                                    </td>
                                    <td><?= $size ?> KB</td>
                                    <td><?= $time ?></td>
                                    <td class="text-end">
                                        <!-- Download -->
                                        <a href="backups/<?= $basename ?>" download class="btn btn-sm btn-outline-secondary me-1" title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        
                                        <!-- Delete -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="delete_backup" value="<?= htmlspecialchars($basename) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this backup?')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
