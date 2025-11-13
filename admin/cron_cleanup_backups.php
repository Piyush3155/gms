<?php
/**
 * Cleanup Old Backups Cron Job
 * Run weekly on Sunday at 3 AM
 * 
 * Add to crontab:
 * 0 3 * * 0 /usr/bin/php /var/www/gms/admin/cron_cleanup_backups.php
 */

// Prevent direct browser access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

require_once __DIR__ . '/../includes/config.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting backup cleanup...\n";

try {
    $backup_path = __DIR__ . '/../backups/';
    $retention_days = BACKUP_RETENTION_DAYS ?? 30;
    $cutoff_time = time() - ($retention_days * 24 * 60 * 60);
    
    $deleted_count = 0;
    $deleted_size = 0;
    
    if (is_dir($backup_path)) {
        $files = glob($backup_path . '*.{sql,zip,tar.gz}', GLOB_BRACE);
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $file_time = filemtime($file);
                
                if ($file_time < $cutoff_time) {
                    $file_size = filesize($file);
                    
                    if (unlink($file)) {
                        $deleted_count++;
                        $deleted_size += $file_size;
                        echo "[" . date('Y-m-d H:i:s') . "] Deleted: " . basename($file) . " (Age: " . round((time() - $file_time) / 86400) . " days)\n";
                    }
                }
            }
        }
        
        // Also clean phpqrcode cache
        $qr_cache_path = __DIR__ . '/../phpqrcode/cache/';
        if (is_dir($qr_cache_path)) {
            $qr_files = glob($qr_cache_path . '*');
            $qr_deleted = 0;
            
            foreach ($qr_files as $file) {
                if (is_file($file) && filemtime($file) < (time() - 7 * 86400)) {
                    if (unlink($file)) {
                        $qr_deleted++;
                    }
                }
            }
            
            if ($qr_deleted > 0) {
                echo "[" . date('Y-m-d H:i:s') . "] Cleaned $qr_deleted QR code cache files\n";
            }
        }
    }
    
    echo "\n[" . date('Y-m-d H:i:s') . "] Cleanup Summary:\n";
    echo "  - Retention period: $retention_days days\n";
    echo "  - Backups deleted: $deleted_count\n";
    echo "  - Space freed: " . number_format($deleted_size / 1024 / 1024, 2) . " MB\n";
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Cleanup process completed\n";
?>
