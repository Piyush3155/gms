<?php
/**
 * Automated Backup Cron Job
 * Run daily at 2 AM
 * 
 * Add to crontab:
 * 0 2 * * * /usr/bin/php /var/www/gms/admin/cron_backup.php
 */

// Prevent direct browser access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/backup_service.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting automated backup...\n";

try {
    $backupService = new BackupService($conn);
    
    // Create full backup
    $result = $backupService->createFullBackup();
    
    if ($result['success']) {
        echo "[" . date('Y-m-d H:i:s') . "] Backup created: " . $result['filename'] . "\n";
        echo "[" . date('Y-m-d H:i:s') . "] Size: " . number_format($result['size'] / 1024 / 1024, 2) . " MB\n";
        
        // Clean old backups (keep last 30 days)
        $cleanupResult = $backupService->cleanupOldBackups(30);
        echo "[" . date('Y-m-d H:i:s') . "] Cleaned " . $cleanupResult['deleted_count'] . " old backups\n";
        
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $result['error'] . "\n";
        
        // Send alert email to admin
        $admin_email = 'admin@yourgym.com'; // Update this
        mail($admin_email, 'Backup Failed', 'Automated backup failed: ' . $result['error']);
    }
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] EXCEPTION: " . $e->getMessage() . "\n";
    
    // Send alert email
    $admin_email = 'admin@yourgym.com'; // Update this
    mail($admin_email, 'Backup Exception', 'Backup script error: ' . $e->getMessage());
}

echo "[" . date('Y-m-d H:i:s') . "] Backup process completed\n";
?>
