<?php
require_once '../includes/config.php';
require_permission('backup', 'view');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['backup'])) {
        // Create backup
        $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_path = '../backups/' . $backup_file;

        // Ensure backups directory exists
        if (!is_dir('../backups')) {
            mkdir('../backups', 0755, true);
        }

        // Get all tables
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }

        $sql = "-- Gym Management System Database Backup\n";
        $sql .= "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            // Get table structure
            $result = $conn->query("SHOW CREATE TABLE `$table`");
            $row = $result->fetch_row();
            $sql .= $row[1] . ";\n\n";

            // Get table data
            $result = $conn->query("SELECT * FROM `$table`");
            if ($result->num_rows > 0) {
                $sql .= "INSERT INTO `$table` VALUES\n";
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $values = [];
                    foreach ($row as $value) {
                        $values[] = $conn->real_escape_string($value);
                    }
                    $rows[] = "('" . implode("','", $values) . "')";
                }
                $sql .= implode(",\n", $rows) . ";\n\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        if (file_put_contents($backup_path, $sql)) {
            log_activity('backup_created', "Database backup created: $backup_file", 'backup');
            $message = "Backup created successfully: $backup_file";
        } else {
            $error = "Failed to create backup file.";
        }

    } elseif (isset($_POST['restore']) && isset($_FILES['backup_file'])) {
        $file = $_FILES['backup_file'];

        if ($file['error'] == 0) {
            $content = file_get_contents($file['tmp_name']);

            // Split SQL commands
            $commands = array_filter(array_map('trim', explode(';', $content)));

            $conn->autocommit(false);
            $success = true;

            try {
                foreach ($commands as $command) {
                    if (!empty($command) && !preg_match('/^--/', $command)) {
                        if (!$conn->query($command)) {
                            throw new Exception("SQL Error: " . $conn->error);
                        }
                    }
                }
                $conn->commit();
                log_activity('backup_restored', "Database restored from file: " . $file['name'], 'backup');
                $message = "Database restored successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Restore failed: " . $e->getMessage();
                $success = false;
            }

            $conn->autocommit(true);
        } else {
            $error = "File upload error.";
        }
    }
}

// Get existing backups
$backups = [];
if (is_dir('../backups')) {
    $files = glob('../backups/*.sql');
    foreach ($files as $file) {
        $backups[] = [
            'filename' => basename($file),
            'size' => filesize($file),
            'date' => date('Y-m-d H:i:s', filemtime($file))
        ];
    }
    usort($backups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-database me-2"></i>Backup & Restore</h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-download me-2"></i>Create Backup</h5>
                    </div>
                    <div class="card-body">
                        <p>Create a complete backup of the database including all tables and data.</p>
                        <form method="POST">
                            <button type="submit" name="backup" class="btn btn-primary">
                                <i class="fas fa-download me-2"></i>Create Database Backup
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-upload me-2"></i>Restore Database</h5>
                    </div>
                    <div class="card-body">
                        <p>Restore database from a backup file. <strong>Warning:</strong> This will overwrite existing data.</p>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="backup_file" class="form-label">Select Backup File</label>
                                <input type="file" class="form-control" id="backup_file" name="backup_file" accept=".sql" required>
                            </div>
                            <button type="submit" name="restore" class="btn btn-warning"
                                    onclick="return confirm('Are you sure you want to restore the database? This will overwrite existing data.')">
                                <i class="fas fa-upload me-2"></i>Restore Database
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-history me-2"></i>Backup History</h5>
            </div>
            <div class="card-body">
                <?php if (empty($backups)): ?>
                    <p class="text-muted">No backups found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Size</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td><?php echo $backup['filename']; ?></td>
                                        <td><?php echo number_format($backup['size'] / 1024, 1) . ' KB'; ?></td>
                                        <td><?php echo $backup['date']; ?></td>
                                        <td>
                                            <a href="../backups/<?php echo $backup['filename']; ?>" class="btn btn-sm btn-outline-primary" download>
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteBackup('<?php echo $backup['filename']; ?>')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
    <script>
        function deleteBackup(filename) {
            if (confirm('Are you sure you want to delete this backup file?')) {
                // Create a form to submit delete request
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="delete_backup" value="${filename}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

    <?php
    // Handle backup deletion
    if (isset($_POST['delete_backup'])) {
        $filename = $_POST['delete_backup'];
        $filepath = '../backups/' . $filename;

        if (file_exists($filepath) && unlink($filepath)) {
            log_activity('backup_deleted', "Backup file deleted: $filename", 'backup');
            echo "<script>window.location.reload();</script>";
        }
    }
    ?>
</body>
</html>