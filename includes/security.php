<?php
/**
 * Security Helper Functions
 * Enhanced security features for the Gym Management System
 */

// Password strength validation
function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if (!preg_match("/[A-Z]/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match("/[a-z]/", $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must contain at least one number";
    }
    if (!preg_match("/[@$!%*?&#]/", $password)) {
        $errors[] = "Password must contain at least one special character (@$!%*?&#)";
    }
    
    return $errors;
}

// Rate limiting for login attempts
class RateLimiter {
    private $conn;
    private $max_attempts = 5;
    private $lockout_time = 900; // 15 minutes in seconds
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->createTableIfNotExists();
    }
    
    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            email VARCHAR(100),
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip (ip_address),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->conn->query($sql);
    }
    
    public function isBlocked($identifier, $type = 'ip') {
        $column = ($type === 'email') ? 'email' : 'ip_address';
        $time_limit = date('Y-m-d H:i:s', time() - $this->lockout_time);
        
        $stmt = $this->conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts 
                                      WHERE $column = ? AND attempt_time > ?");
        $stmt->bind_param("ss", $identifier, $time_limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['attempts'] >= $this->max_attempts;
    }
    
    public function recordAttempt($ip, $email = null) {
        $stmt = $this->conn->prepare("INSERT INTO login_attempts (ip_address, email) VALUES (?, ?)");
        $stmt->bind_param("ss", $ip, $email);
        $stmt->execute();
        $stmt->close();
    }
    
    public function clearAttempts($identifier, $type = 'ip') {
        $column = ($type === 'email') ? 'email' : 'ip_address';
        $stmt = $this->conn->prepare("DELETE FROM login_attempts WHERE $column = ?");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $stmt->close();
    }
    
    public function getRemainingAttempts($identifier, $type = 'ip') {
        $column = ($type === 'email') ? 'email' : 'ip_address';
        $time_limit = date('Y-m-d H:i:s', time() - $this->lockout_time);
        
        $stmt = $this->conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts 
                                      WHERE $column = ? AND attempt_time > ?");
        $stmt->bind_param("ss", $identifier, $time_limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return max(0, $this->max_attempts - $row['attempts']);
    }
    
    public function cleanupOldAttempts() {
        $time_limit = date('Y-m-d H:i:s', time() - $this->lockout_time);
        $this->conn->query("DELETE FROM login_attempts WHERE attempt_time < '$time_limit'");
    }
}

// Session security enhancements
function regenerate_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
        $_SESSION['regenerated'] = time();
    }
}

function validate_session() {
    // Check if session was regenerated recently (every 30 minutes)
    if (!isset($_SESSION['regenerated']) || (time() - $_SESSION['regenerated']) > 1800) {
        regenerate_session();
    }
    
    // Validate IP address (optional - can cause issues with mobile networks)
    if (isset($_SESSION['ip_address'])) {
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            session_destroy();
            return false;
        }
    } else {
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    }
    
    // Validate user agent
    if (isset($_SESSION['user_agent'])) {
        if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            session_destroy();
            return false;
        }
    } else {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }
    
    return true;
}

// Enhanced input sanitization
function sanitize_input($data, $type = 'string') {
    if (is_array($data)) {
        return array_map(function($item) use ($type) {
            return sanitize_input($item, $type);
        }, $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    
    switch ($type) {
        case 'email':
            return filter_var($data, FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'url':
            return filter_var($data, FILTER_SANITIZE_URL);
        case 'html':
            return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        default:
            return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

// XSS Protection
function prevent_xss($data) {
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// SQL Injection Prevention - Prepared Statement Wrapper
class SecureDB {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function select($table, $columns = '*', $where = [], $orderBy = null, $limit = null) {
        $sql = "SELECT $columns FROM $table";
        
        if (!empty($where)) {
            $conditions = [];
            $types = '';
            $values = [];
            
            foreach ($where as $column => $value) {
                $conditions[] = "$column = ?";
                $types .= $this->getParamType($value);
                $values[] = $value;
            }
            
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($where)) {
            $stmt->bind_param($types, ...$values);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }
    
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $types = '';
        $values = [];
        
        foreach ($data as $value) {
            $types .= $this->getParamType($value);
            $values[] = $value;
        }
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }
    
    public function update($table, $data, $where) {
        $set = [];
        $types = '';
        $values = [];
        
        foreach ($data as $column => $value) {
            $set[] = "$column = ?";
            $types .= $this->getParamType($value);
            $values[] = $value;
        }
        
        $conditions = [];
        foreach ($where as $column => $value) {
            $conditions[] = "$column = ?";
            $types .= $this->getParamType($value);
            $values[] = $value;
        }
        
        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $conditions);
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }
    
    public function delete($table, $where) {
        $conditions = [];
        $types = '';
        $values = [];
        
        foreach ($where as $column => $value) {
            $conditions[] = "$column = ?";
            $types .= $this->getParamType($value);
            $values[] = $value;
        }
        
        $sql = "DELETE FROM $table WHERE " . implode(' AND ', $conditions);
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }
    
    private function getParamType($value) {
        if (is_int($value)) return 'i';
        if (is_float($value)) return 'd';
        return 's';
    }
}

// File upload security
function secure_file_upload($file, $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'], $max_size = 5242880) {
    $errors = [];
    
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = "No file uploaded or invalid upload";
        return ['success' => false, 'errors' => $errors];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $errors[] = "File size exceeds maximum allowed size of " . ($max_size / 1048576) . "MB";
    }
    
    // Get file extension
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Check extension
    if (!in_array($ext, $allowed_extensions)) {
        $errors[] = "File type not allowed. Allowed types: " . implode(', ', $allowed_extensions);
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'pdf' => 'application/pdf'
    ];
    
    if (isset($allowed_mimes[$ext]) && $mime !== $allowed_mimes[$ext]) {
        $errors[] = "File MIME type does not match extension";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Generate secure filename
    $new_filename = bin2hex(random_bytes(16)) . '.' . $ext;
    
    return [
        'success' => true,
        'filename' => $new_filename,
        'original_name' => $filename,
        'extension' => $ext,
        'mime_type' => $mime
    ];
}

// Audit logging
function log_security_event($event_type, $description, $severity = 'info') {
    global $conn;
    $user_id = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $sql = "CREATE TABLE IF NOT EXISTS security_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        event_type VARCHAR(50) NOT NULL,
        description TEXT,
        severity ENUM('info', 'warning', 'critical') DEFAULT 'info',
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_event_type (event_type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
    
    $stmt = $conn->prepare("INSERT INTO security_log (user_id, event_type, description, severity, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $event_type, $description, $severity, $ip);
    $stmt->execute();
    $stmt->close();
}

// Two-factor authentication token generation
function generate_2fa_token() {
    return sprintf("%06d", mt_rand(0, 999999));
}

// Token expiration check
function is_token_expired($timestamp, $validity_minutes = 10) {
    return (time() - $timestamp) > ($validity_minutes * 60);
}

// Generate secure random token
function generate_secure_token($length = 32) {
    return bin2hex(random_bytes($length));
}

// API key generation and validation
function generate_api_key() {
    return 'gms_' . bin2hex(random_bytes(32));
}

function validate_api_key($api_key) {
    global $conn;
    $stmt = $conn->prepare("SELECT user_id, permissions FROM api_keys WHERE api_key = ? AND status = 'active'");
    $stmt->bind_param("s", $api_key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        // Update last used timestamp
        $update = $conn->prepare("UPDATE api_keys SET last_used = NOW() WHERE api_key = ?");
        $update->bind_param("s", $api_key);
        $update->execute();
        $update->close();
        
        $stmt->close();
        return $data;
    }
    
    $stmt->close();
    return false;
}

// Content Security Policy header
function set_security_headers() {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    
    // Content Security Policy
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
           "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
           "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; " .
           "img-src 'self' data: https:; " .
           "connect-src 'self';";
    
    header("Content-Security-Policy: $csp");
}

// Password hashing with enhanced security
function hash_password_secure($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

// Verify password with timing attack protection
function verify_password_secure($password, $hash) {
    return password_verify($password, $hash);
}

// Check if password needs rehash (for upgrading algorithms)
function password_needs_rehash_check($hash) {
    return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}
?>
