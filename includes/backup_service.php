<?php
/**
 * Enhanced Backup Service
 * 
 * Features:
 * - Automated backup scheduling
 * - Backup versioning
 * - Cloud storage integration (AWS S3, Google Cloud, Dropbox)
 * - One-click restore
 * - Backup verification
 * - Differential backups
 * - Backup encryption
 * 
 * @package GMS
 * @version 2.0
 */

require_once 'config.php';

class BackupService {
    private $conn;
    private $backupDir;
    private $maxBackups = 10; // Keep last 10 backups
    private $encryptionKey;
    
    // Cloud storage configurations
    private $cloudConfigs = [
        'aws_s3' => [
            'access_key' => 'YOUR_AWS_ACCESS_KEY',
            'secret_key' => 'YOUR_AWS_SECRET_KEY',
            'bucket' => 'your-gym-backups',
            'region' => 'us-east-1',
            'enabled' => false
        ],
        'google_cloud' => [
            'project_id' => 'YOUR_PROJECT_ID',
            'bucket' => 'your-gym-backups',
            'key_file' => '../config/google-cloud-key.json',
            'enabled' => false
        ],
        'dropbox' => [
            'access_token' => 'YOUR_DROPBOX_ACCESS_TOKEN',
            'folder' => '/GymBackups',
            'enabled' => false
        ]
    ];
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->backupDir = realpath(__DIR__ . '/../backups/');
        $this->encryptionKey = defined('BACKUP_ENCRYPTION_KEY') ? BACKUP_ENCRYPTION_KEY : 'default_encryption_key_change_me';
        
        // Ensure backup directory exists
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        
        // Initialize backup log table
        $this->initializeDatabase();
    }
    
    /**
     * Initialize backup log table
     */
    private function initializeDatabase() {
        $sql = "CREATE TABLE IF NOT EXISTS backup_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            file_size BIGINT,
            backup_type ENUM('full', 'differential', 'incremental') DEFAULT 'full',
            status ENUM('success', 'failed', 'in_progress') DEFAULT 'in_progress',
            cloud_uploaded BOOLEAN DEFAULT FALSE,
            cloud_provider VARCHAR(50),
            error_message TEXT,
            checksum VARCHAR(64),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_by INT,
            INDEX idx_created_at (created_at),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->query($sql);
        
        // Create backup schedule table
        $sql = "CREATE TABLE IF NOT EXISTS backup_schedule (
            id INT AUTO_INCREMENT PRIMARY KEY,
            schedule_type ENUM('daily', 'weekly', 'monthly') DEFAULT 'daily',
            schedule_time TIME DEFAULT '02:00:00',
            schedule_day INT DEFAULT 1,
            backup_type ENUM('full', 'differential') DEFAULT 'full',
            cloud_sync BOOLEAN DEFAULT FALSE,
            cloud_provider VARCHAR(50),
            enabled BOOLEAN DEFAULT TRUE,
            last_run TIMESTAMP NULL,
            next_run TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->query($sql);
    }
    
    /**
     * Create full database backup
     * 
     * @param bool $encrypt Whether to encrypt the backup
     * @param bool $uploadToCloud Whether to upload to cloud storage
     * @param string $cloudProvider Cloud provider to use
     * @return array Result with filename and status
     */
    public function createFullBackup($encrypt = false, $uploadToCloud = false, $cloudProvider = null) {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_full_{$timestamp}.sql";
            $filepath = $this->backupDir . '/' . $filename;
            
            // Log backup start
            $logId = $this->logBackupStart($filename, 'full');
            
            // Get all tables
            $tables = $this->getAllTables();
            
            // Generate SQL backup
            $sql = $this->generateBackupSQL($tables);
            
            // Encrypt if requested
            if ($encrypt) {
                $sql = $this->encryptData($sql);
                $filename = str_replace('.sql', '.sql.enc', $filename);
                $filepath = $this->backupDir . '/' . $filename;
            }
            
            // Write to file
            file_put_contents($filepath, $sql);
            
            // Calculate checksum
            $checksum = hash_file('sha256', $filepath);
            $fileSize = filesize($filepath);
            
            // Verify backup integrity
            if (!$this->verifyBackup($filepath)) {
                throw new Exception("Backup verification failed");
            }
            
            // Update backup log
            $this->logBackupSuccess($logId, $fileSize, $checksum);
            
            // Upload to cloud if requested
            if ($uploadToCloud && $cloudProvider) {
                $this->uploadToCloud($filepath, $cloudProvider, $logId);
            }
            
            // Clean old backups
            $this->cleanOldBackups();
            
            log_activity('backup_created', "Full backup created: {$filename}", 'backup');
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => $fileSize,
                'checksum' => $checksum
            ];
            
        } catch (Exception $e) {
            error_log("Backup creation failed: " . $e->getMessage());
            
            if (isset($logId)) {
                $this->logBackupFailure($logId, $e->getMessage());
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create differential backup (only changes since last full backup)
     */
    public function createDifferentialBackup() {
        try {
            // Get last full backup timestamp
            $lastFullBackup = $this->getLastFullBackupTimestamp();
            
            if (!$lastFullBackup) {
                throw new Exception("No full backup found. Please create a full backup first.");
            }
            
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_diff_{$timestamp}.sql";
            $filepath = $this->backupDir . '/' . $filename;
            
            $logId = $this->logBackupStart($filename, 'differential');
            
            // Get tables with changes since last full backup
            $tables = $this->getAllTables();
            $sql = $this->generateDifferentialBackupSQL($tables, $lastFullBackup);
            
            file_put_contents($filepath, $sql);
            
            $checksum = hash_file('sha256', $filepath);
            $fileSize = filesize($filepath);
            
            $this->logBackupSuccess($logId, $fileSize, $checksum);
            
            log_activity('backup_created', "Differential backup created: {$filename}", 'backup');
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => $fileSize
            ];
            
        } catch (Exception $e) {
            error_log("Differential backup failed: " . $e->getMessage());
            
            if (isset($logId)) {
                $this->logBackupFailure($logId, $e->getMessage());
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Restore database from backup file
     * 
     * @param string $filename Backup filename
     * @param bool $isEncrypted Whether the backup is encrypted
     * @return array Result
     */
    public function restoreBackup($filename, $isEncrypted = false) {
        try {
            $filepath = $this->backupDir . '/' . $filename;
            
            if (!file_exists($filepath)) {
                throw new Exception("Backup file not found: {$filename}");
            }
            
            // Read backup file
            $sql = file_get_contents($filepath);
            
            // Decrypt if necessary
            if ($isEncrypted) {
                $sql = $this->decryptData($sql);
            }
            
            // Verify backup before restore
            if (!$this->verifyBackupIntegrity($filepath)) {
                throw new Exception("Backup file integrity check failed");
            }
            
            // Create safety backup before restore
            $this->createFullBackup(false, false);
            
            // Execute restore
            $this->executeRestore($sql);
            
            log_activity('backup_restored', "Database restored from: {$filename}", 'backup');
            
            return [
                'success' => true,
                'message' => 'Database restored successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Restore failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Schedule automated backups
     * 
     * @param string $type daily, weekly, monthly
     * @param string $time Time in H:i:s format
     * @param int $dayOfWeekOrMonth Day of week (1-7) or month (1-31)
     * @param bool $cloudSync Whether to sync to cloud
     * @param string $cloudProvider Cloud provider
     * @return bool Success status
     */
    public function scheduleBackup($type, $time, $dayOfWeekOrMonth = 1, $cloudSync = false, $cloudProvider = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO backup_schedule (schedule_type, schedule_time, schedule_day, cloud_sync, cloud_provider, enabled)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        
        $stmt->bind_param("ssiss", $type, $time, $dayOfWeekOrMonth, $cloudSync, $cloudProvider);
        
        $success = $stmt->execute();
        
        if ($success) {
            $this->calculateNextRun($this->conn->insert_id);
            log_activity('backup_scheduled', "Backup scheduled: {$type} at {$time}", 'backup');
        }
        
        return $success;
    }
    
    /**
     * Process scheduled backups (run via cron)
     */
    public function processScheduledBackups() {
        $now = date('Y-m-d H:i:s');
        
        $stmt = $this->conn->prepare("
            SELECT * FROM backup_schedule
            WHERE enabled = 1
            AND next_run <= ?
            ORDER BY next_run ASC
        ");
        
        $stmt->bind_param("s", $now);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $processedCount = 0;
        
        while ($schedule = $result->fetch_assoc()) {
            // Create backup
            $backupResult = $this->createFullBackup(
                false,
                $schedule['cloud_sync'],
                $schedule['cloud_provider']
            );
            
            if ($backupResult['success']) {
                $processedCount++;
                
                // Update last run and calculate next run
                $updateStmt = $this->conn->prepare("
                    UPDATE backup_schedule
                    SET last_run = NOW()
                    WHERE id = ?
                ");
                $updateStmt->bind_param("i", $schedule['id']);
                $updateStmt->execute();
                
                $this->calculateNextRun($schedule['id']);
            }
        }
        
        log_activity('scheduled_backups_processed', "Processed {$processedCount} scheduled backups", 'backup');
        
        return $processedCount;
    }
    
    /**
     * Upload backup to cloud storage
     */
    private function uploadToCloud($filepath, $provider, $logId) {
        try {
            $config = $this->cloudConfigs[$provider] ?? null;
            
            if (!$config || !$config['enabled']) {
                throw new Exception("Cloud provider not configured or disabled: {$provider}");
            }
            
            switch ($provider) {
                case 'aws_s3':
                    $this->uploadToAWSS3($filepath, $config);
                    break;
                case 'google_cloud':
                    $this->uploadToGoogleCloud($filepath, $config);
                    break;
                case 'dropbox':
                    $this->uploadToDropbox($filepath, $config);
                    break;
                default:
                    throw new Exception("Unsupported cloud provider: {$provider}");
            }
            
            // Update log
            $stmt = $this->conn->prepare("UPDATE backup_log SET cloud_uploaded = 1, cloud_provider = ? WHERE id = ?");
            $stmt->bind_param("si", $provider, $logId);
            $stmt->execute();
            
            log_activity('backup_cloud_uploaded', "Backup uploaded to {$provider}", 'backup');
            
            return true;
            
        } catch (Exception $e) {
            error_log("Cloud upload failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Upload to AWS S3
     */
    private function uploadToAWSS3($filepath, $config) {
        // Requires AWS SDK
        // Simplified implementation
        
        $filename = basename($filepath);
        $s3 = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => $config['region'],
            'credentials' => [
                'key' => $config['access_key'],
                'secret' => $config['secret_key']
            ]
        ]);
        
        $result = $s3->putObject([
            'Bucket' => $config['bucket'],
            'Key' => 'backups/' . $filename,
            'SourceFile' => $filepath,
            'ServerSideEncryption' => 'AES256'
        ]);
        
        return true;
    }
    
    /**
     * Upload to Google Cloud Storage
     */
    private function uploadToGoogleCloud($filepath, $config) {
        // Requires Google Cloud SDK
        // Simplified implementation
        
        $storage = new \Google\Cloud\Storage\StorageClient([
            'projectId' => $config['project_id'],
            'keyFilePath' => $config['key_file']
        ]);
        
        $bucket = $storage->bucket($config['bucket']);
        $filename = basename($filepath);
        
        $bucket->upload(
            fopen($filepath, 'r'),
            ['name' => 'backups/' . $filename]
        );
        
        return true;
    }
    
    /**
     * Upload to Dropbox
     */
    private function uploadToDropbox($filepath, $config) {
        $filename = basename($filepath);
        $url = 'https://content.dropboxapi.com/2/files/upload';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($filepath));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $config['access_token'],
            'Content-Type: application/octet-stream',
            'Dropbox-API-Arg: ' . json_encode([
                'path' => $config['folder'] . '/' . $filename,
                'mode' => 'add',
                'autorename' => true
            ])
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Dropbox upload failed: " . $response);
        }
        
        return true;
    }
    
    /**
     * Helper methods
     */
    
    private function getAllTables() {
        $tables = [];
        $result = $this->conn->query("SHOW TABLES");
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        return $tables;
    }
    
    private function generateBackupSQL($tables) {
        $sql = "-- Gym Management System Database Backup\n";
        $sql .= "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $sql .= "SET time_zone = '+00:00';\n\n";
        
        foreach ($tables as $table) {
            $sql .= "-- Table structure for table `{$table}`\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            
            $result = $this->conn->query("SHOW CREATE TABLE `{$table}`");
            $row = $result->fetch_row();
            $sql .= $row[1] . ";\n\n";
            
            $sql .= "-- Dumping data for table `{$table}`\n";
            $result = $this->conn->query("SELECT * FROM `{$table}`");
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $values = array_map(function($value) {
                        return is_null($value) ? 'NULL' : "'" . $this->conn->real_escape_string($value) . "'";
                    }, $row);
                    
                    $sql .= "INSERT INTO `{$table}` VALUES (" . implode(", ", $values) . ");\n";
                }
            }
            
            $sql .= "\n";
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        return $sql;
    }
    
    private function generateDifferentialBackupSQL($tables, $lastBackupTime) {
        $sql = "-- Differential Backup (changes since {$lastBackupTime})\n\n";
        
        foreach ($tables as $table) {
            // Check if table has timestamp column
            $hasTimestamp = false;
            $timestampColumn = null;
            
            $result = $this->conn->query("SHOW COLUMNS FROM `{$table}`");
            while ($column = $result->fetch_assoc()) {
                if (in_array($column['Field'], ['created_at', 'updated_at', 'modified_at'])) {
                    $hasTimestamp = true;
                    $timestampColumn = $column['Field'];
                    break;
                }
            }
            
            if ($hasTimestamp) {
                $result = $this->conn->query("SELECT * FROM `{$table}` WHERE `{$timestampColumn}` > '{$lastBackupTime}'");
                
                if ($result->num_rows > 0) {
                    $sql .= "-- Modified records in table `{$table}`\n";
                    
                    while ($row = $result->fetch_assoc()) {
                        $values = array_map(function($value) {
                            return is_null($value) ? 'NULL' : "'" . $this->conn->real_escape_string($value) . "'";
                        }, $row);
                        
                        $sql .= "REPLACE INTO `{$table}` VALUES (" . implode(", ", $values) . ");\n";
                    }
                    
                    $sql .= "\n";
                }
            }
        }
        
        return $sql;
    }
    
    private function executeRestore($sql) {
        // Split SQL into individual commands
        $commands = array_filter(array_map('trim', explode(';', $sql)));
        
        $this->conn->autocommit(false);
        
        try {
            foreach ($commands as $command) {
                if (!empty($command) && !preg_match('/^--/', $command)) {
                    if (!$this->conn->query($command)) {
                        throw new Exception("SQL Error: " . $this->conn->error);
                    }
                }
            }
            
            $this->conn->commit();
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        } finally {
            $this->conn->autocommit(true);
        }
    }
    
    private function encryptData($data) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->encryptionKey, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    private function decryptData($data) {
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt($encrypted, 'aes-256-cbc', $this->encryptionKey, 0, $iv);
    }
    
    private function verifyBackup($filepath) {
        // Check if file exists and is readable
        if (!file_exists($filepath) || !is_readable($filepath)) {
            return false;
        }
        
        // Check if file size is greater than 0
        if (filesize($filepath) === 0) {
            return false;
        }
        
        return true;
    }
    
    private function verifyBackupIntegrity($filepath) {
        // Get stored checksum from database
        $filename = basename($filepath);
        $stmt = $this->conn->prepare("SELECT checksum FROM backup_log WHERE filename = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("s", $filename);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return true; // No checksum stored, assume valid
        }
        
        $row = $result->fetch_assoc();
        $storedChecksum = $row['checksum'];
        
        // Calculate current checksum
        $currentChecksum = hash_file('sha256', $filepath);
        
        return $storedChecksum === $currentChecksum;
    }
    
    private function cleanOldBackups() {
        $backups = glob($this->backupDir . '/backup_*.sql*');
        
        if (count($backups) > $this->maxBackups) {
            // Sort by modification time
            usort($backups, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Delete oldest backups
            $toDelete = array_slice($backups, 0, count($backups) - $this->maxBackups);
            
            foreach ($toDelete as $file) {
                unlink($file);
                log_activity('backup_deleted', "Old backup deleted: " . basename($file), 'backup');
            }
        }
    }
    
    private function getLastFullBackupTimestamp() {
        $stmt = $this->conn->query("SELECT created_at FROM backup_log WHERE backup_type = 'full' AND status = 'success' ORDER BY created_at DESC LIMIT 1");
        
        if ($stmt->num_rows > 0) {
            $row = $stmt->fetch_assoc();
            return $row['created_at'];
        }
        
        return null;
    }
    
    private function calculateNextRun($scheduleId) {
        $stmt = $this->conn->prepare("SELECT * FROM backup_schedule WHERE id = ?");
        $stmt->bind_param("i", $scheduleId);
        $stmt->execute();
        $schedule = $stmt->get_result()->fetch_assoc();
        
        $now = new DateTime();
        $nextRun = clone $now;
        
        switch ($schedule['schedule_type']) {
            case 'daily':
                $nextRun->modify('+1 day');
                break;
            case 'weekly':
                $nextRun->modify('+1 week');
                break;
            case 'monthly':
                $nextRun->modify('+1 month');
                break;
        }
        
        $nextRun->setTime(
            (int)date('H', strtotime($schedule['schedule_time'])),
            (int)date('i', strtotime($schedule['schedule_time'])),
            0
        );
        
        $updateStmt = $this->conn->prepare("UPDATE backup_schedule SET next_run = ? WHERE id = ?");
        $nextRunStr = $nextRun->format('Y-m-d H:i:s');
        $updateStmt->bind_param("si", $nextRunStr, $scheduleId);
        $updateStmt->execute();
    }
    
    private function logBackupStart($filename, $type) {
        $stmt = $this->conn->prepare("INSERT INTO backup_log (filename, backup_type, status) VALUES (?, ?, 'in_progress')");
        $stmt->bind_param("ss", $filename, $type);
        $stmt->execute();
        return $this->conn->insert_id;
    }
    
    private function logBackupSuccess($logId, $fileSize, $checksum) {
        $stmt = $this->conn->prepare("UPDATE backup_log SET status = 'success', file_size = ?, checksum = ? WHERE id = ?");
        $stmt->bind_param("isi", $fileSize, $checksum, $logId);
        $stmt->execute();
    }
    
    private function logBackupFailure($logId, $errorMessage) {
        $stmt = $this->conn->prepare("UPDATE backup_log SET status = 'failed', error_message = ? WHERE id = ?");
        $stmt->bind_param("si", $errorMessage, $logId);
        $stmt->execute();
    }
    
    /**
     * Get backup statistics
     */
    public function getBackupStats() {
        $stats = [
            'total_backups' => 0,
            'total_size' => 0,
            'last_backup' => null,
            'success_rate' => 0,
            'cloud_synced' => 0
        ];
        
        $result = $this->conn->query("
            SELECT 
                COUNT(*) as total,
                SUM(file_size) as total_size,
                MAX(created_at) as last_backup,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN cloud_uploaded = 1 THEN 1 ELSE 0 END) as cloud_synced
            FROM backup_log
        ");
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stats['total_backups'] = $row['total'];
            $stats['total_size'] = $row['total_size'];
            $stats['last_backup'] = $row['last_backup'];
            $stats['success_rate'] = $row['total'] > 0 ? round(($row['successful'] / $row['total']) * 100, 1) : 0;
            $stats['cloud_synced'] = $row['cloud_synced'];
        }
        
        return $stats;
    }
}
