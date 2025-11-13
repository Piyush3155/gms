<?php
/**
 * Enhanced Backup Management Interface
 * With scheduling, versioning, and cloud storage support
 */

require_once '../includes/config.php';
require_once '../includes/backup_service.php';

require_permission('backup', 'view');

$message = '';
$error = '';

$backupService = new BackupService();

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_backup'])) {
    $encrypt = isset($_POST['encrypt']);
    $cloudUpload = isset($_POST['cloud_upload']);
    $cloudProvider = $_POST['cloud_provider'] ?? null;
    
    $result = $backupService->createFullBackup($encrypt, $cloudUpload, $cloudProvider);
    
    if ($result['success']) {
        $message = "Backup created successfully: {$result['filename']} (" . number_format($result['size'] / 1024, 2) . " KB)";
    } else {
        $error = $result['error'] ?? 'Failed to create backup';
    }
}

// Handle differential backup
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_differential'])) {
    $result = $backupService->createDifferentialBackup();
    
    if ($result['success']) {
        $message = "Differential backup created successfully: {$result['filename']}";
    } else {
        $error = $result['error'] ?? 'Failed to create differential backup';
    }
}

// Handle restore
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['restore_backup'])) {
    $filename = $_POST['backup_file'];
    $isEncrypted = str_ends_with($filename, '.enc');
    
    $result = $backupService->restoreBackup($filename, $isEncrypted);
    
    if ($result['success']) {
        $message = "Database restored successfully from: {$filename}";
    } else {
        $error = $result['error'] ?? 'Failed to restore database';
    }
}

// Handle schedule creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['schedule_backup'])) {
    $type = $_POST['schedule_type'];
    $time = $_POST['schedule_time'];
    $day = intval($_POST['schedule_day'] ?? 1);
    $cloudSync = isset($_POST['schedule_cloud_sync']);
    $cloudProvider = $_POST['schedule_cloud_provider'] ?? null;
    
    $result = $backupService->scheduleBackup($type, $time, $day, $cloudSync, $cloudProvider);
    
    if ($result) {
        $message = "Backup schedule created successfully";
    } else {
        $error = "Failed to create backup schedule";
    }
}

// Get backup statistics
$stats = $backupService->getBackupStats();

// Get backup history
$backupHistory = $conn->query("
    SELECT * FROM backup_log
    ORDER BY created_at DESC
    LIMIT 20
");

// Get backup schedules
$schedules = $conn->query("
    SELECT * FROM backup_schedule
    ORDER BY created_at DESC
");

// Get available backup files
$backupFiles = [];
$backupDir = realpath(__DIR__ . '/../backups/');
if (is_dir($backupDir)) {
    $files = glob($backupDir . '/*.sql*');
    foreach ($files as $file) {
        $backupFiles[] = [
            'filename' => basename($file),
            'size' => filesize($file),
            'date' => date('Y-m-d H:i:s', filemtime($file)),
            'encrypted' => str_ends_with($file, '.enc')
        ];
    }
    usort($backupFiles, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Backup & Recovery - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css?v=1.0">
    
    <style>
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .schedule-card {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .schedule-card.disabled {
            opacity: 0.6;
            background: #f3f4f6;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <h2 class="mb-4"><i class="fas fa-server me-2"></i>Enhanced Backup & Recovery</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Total Backups</h6>
                        <h3><?php echo $stats['total_backups']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Total Size</h6>
                        <h3><?php echo number_format($stats['total_size'] / (1024 * 1024), 2); ?> MB</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-info">
                    <div class="card-body">
                        <h6 class="text-muted">Success Rate</h6>
                        <h3><?php echo $stats['success_rate']; ?>%</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-warning">
                    <div class="card-body">
                        <h6 class="text-muted">Cloud Synced</h6>
                        <h3><?php echo $stats['cloud_synced']; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Create Backup -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-download me-2"></i>Create New Backup</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="create_backup" value="1">
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="encrypt" id="encrypt">
                                    <label class="form-check-label" for="encrypt">
                                        <i class="fas fa-lock me-2"></i>Encrypt backup
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="cloud_upload" id="cloud_upload">
                                    <label class="form-check-label" for="cloud_upload">
                                        <i class="fas fa-cloud-upload-alt me-2"></i>Upload to cloud storage
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="cloud_provider_div" style="display: none;">
                                <label for="cloud_provider" class="form-label">Cloud Provider</label>
                                <select class="form-select" name="cloud_provider" id="cloud_provider">
                                    <option value="aws_s3">AWS S3</option>
                                    <option value="google_cloud">Google Cloud Storage</option>
                                    <option value="dropbox">Dropbox</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-download me-2"></i>Create Full Backup
                            </button>
                            
                            <button type="submit" name="create_differential" value="1" class="btn btn-secondary ms-2">
                                <i class="fas fa-compress me-2"></i>Create Differential Backup
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Restore Backup -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Restore Database</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Warning: This will overwrite existing data. A safety backup will be created automatically.</p>
                        
                        <form method="POST">
                            <input type="hidden" name="restore_backup" value="1">
                            
                            <div class="mb-3">
                                <label for="backup_file" class="form-label">Select Backup File</label>
                                <select class="form-select" name="backup_file" required>
                                    <option value="">Choose backup...</option>
                                    <?php foreach ($backupFiles as $file): ?>
                                        <option value="<?php echo htmlspecialchars($file['filename']); ?>">
                                            <?php echo htmlspecialchars($file['filename']); ?> 
                                            (<?php echo number_format($file['size'] / 1024, 2); ?> KB - <?php echo $file['date']; ?>)
                                            <?php echo $file['encrypted'] ? ' 🔒' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to restore the database? This action cannot be undone without another backup.');">
                                <i class="fas fa-upload me-2"></i>Restore Database
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Schedule Backup -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Schedule Automated Backups</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="schedule_backup" value="1">
                            
                            <div class="col-md-3">
                                <label for="schedule_type" class="form-label">Frequency</label>
                                <select class="form-select" name="schedule_type" id="schedule_type" required>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="schedule_time" class="form-label">Time</label>
                                <input type="time" class="form-control" name="schedule_time" value="02:00" required>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="schedule_day" class="form-label">Day</label>
                                <input type="number" class="form-control" name="schedule_day" value="1" min="1" max="31">
                                <small class="text-muted">For weekly/monthly</small>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Cloud Sync</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="schedule_cloud_sync" id="schedule_cloud_sync">
                                    <label class="form-check-label" for="schedule_cloud_sync">
                                        Enable
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="schedule_cloud_provider" class="form-label">Provider</label>
                                <select class="form-select" name="schedule_cloud_provider" id="schedule_cloud_provider">
                                    <option value="aws_s3">AWS S3</option>
                                    <option value="google_cloud">Google Cloud</option>
                                    <option value="dropbox">Dropbox</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-calendar-plus me-2"></i>Create Schedule
                                </button>
                            </div>
                        </form>
                        
                        <!-- Active Schedules -->
                        <div class="mt-4">
                            <h6>Active Schedules</h6>
                            <?php if ($schedules->num_rows > 0): ?>
                                <div class="row">
                                    <?php while ($schedule = $schedules->fetch_assoc()): ?>
                                        <div class="col-md-6">
                                            <div class="schedule-card <?php echo $schedule['enabled'] ? '' : 'disabled'; ?>">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6><?php echo ucfirst($schedule['schedule_type']); ?> at <?php echo date('g:i A', strtotime($schedule['schedule_time'])); ?></h6>
                                                        <small class="text-muted">
                                                            Next run: <?php echo $schedule['next_run'] ? date('M j, Y g:i A', strtotime($schedule['next_run'])) : 'Not scheduled'; ?>
                                                        </small>
                                                        <?php if ($schedule['cloud_sync']): ?>
                                                            <span class="badge bg-info ms-2">Cloud Sync</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <span class="badge <?php echo $schedule['enabled'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                            <?php echo $schedule['enabled'] ? 'Active' : 'Disabled'; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No scheduled backups configured</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Backup History -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Backup History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Filename</th>
                                        <th>Type</th>
                                        <th>Size</th>
                                        <th>Status</th>
                                        <th>Cloud</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($backupHistory->num_rows > 0): ?>
                                        <?php while ($backup = $backupHistory->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <code><?php echo htmlspecialchars($backup['filename']); ?></code>
                                                    <?php if (str_ends_with($backup['filename'], '.enc')): ?>
                                                        <i class="fas fa-lock text-warning" title="Encrypted"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="badge bg-info"><?php echo ucfirst($backup['backup_type']); ?></span></td>
                                                <td><?php echo number_format($backup['file_size'] / 1024, 2); ?> KB</td>
                                                <td>
                                                    <?php if ($backup['status'] === 'success'): ?>
                                                        <span class="badge bg-success">Success</span>
                                                    <?php elseif ($backup['status'] === 'failed'): ?>
                                                        <span class="badge bg-danger" title="<?php echo htmlspecialchars($backup['error_message']); ?>">Failed</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">In Progress</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($backup['cloud_uploaded']): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-cloud"></i> <?php echo ucfirst($backup['cloud_provider']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($backup['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($backup['status'] === 'success'): ?>
                                                        <a href="../backups/<?php echo htmlspecialchars($backup['filename']); ?>" class="btn btn-sm btn-primary" download>
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No backup history found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide cloud provider selection
        document.getElementById('cloud_upload').addEventListener('change', function() {
            document.getElementById('cloud_provider_div').style.display = this.checked ? 'block' : 'none';
        });
    </script>
</body>
</html>
