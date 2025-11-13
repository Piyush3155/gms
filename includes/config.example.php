<?php
/**
 * Gym Management System - Configuration Template
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to config.php
 * 2. Update all values with your production settings
 * 3. Never commit config.php to version control
 */

// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST', 'localhost');           // Database host
define('DB_USER', 'your_db_username');    // Database username
define('DB_PASS', 'your_db_password');    // Database password
define('DB_NAME', 'your_db_name');        // Database name
define('DB_CHARSET', 'utf8mb4');          // Database charset

// ============================================
// SITE CONFIGURATION
// ============================================
define('SITE_NAME', 'Your Gym Name');
define('SITE_URL', 'https://yourdomain.com');
define('SITE_TAGLINE', 'Your Fitness Journey Starts Here');

// ============================================
// SECURITY SETTINGS
// ============================================
define('SESSION_TIMEOUT', 3600);          // Session timeout in seconds (1 hour)
define('MAX_LOGIN_ATTEMPTS', 5);          // Maximum login attempts before lockout
define('LOCKOUT_DURATION', 900);          // Lockout duration in seconds (15 min)
define('ENCRYPTION_KEY', 'CHANGE_THIS_TO_RANDOM_64_CHAR_STRING_FOR_PRODUCTION');
define('ENABLE_2FA', false);              // Enable two-factor authentication
define('FORCE_HTTPS', true);              // Force HTTPS in production

// ============================================
// EMAIL CONFIGURATION
// ============================================
define('SMTP_ENABLED', false);            // Use SMTP for emails (recommended)
define('SMTP_HOST', 'smtp.gmail.com');    // SMTP server
define('SMTP_PORT', 587);                 // SMTP port (587 for TLS, 465 for SSL)
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');
define('SMTP_ENCRYPTION', 'tls');         // 'tls' or 'ssl'
define('FROM_EMAIL', 'noreply@yourgym.com');
define('FROM_NAME', 'Your Gym Name');

// ============================================
// SMS CONFIGURATION (Twilio)
// ============================================
define('SMS_ENABLED', false);
define('TWILIO_SID', 'your_twilio_sid');
define('TWILIO_TOKEN', 'your_twilio_token');
define('TWILIO_PHONE', '+1234567890');

// ============================================
// PAYMENT GATEWAY CONFIGURATION
// ============================================
// Razorpay
define('RAZORPAY_ENABLED', false);
define('RAZORPAY_KEY_ID', 'your_razorpay_key_id');
define('RAZORPAY_KEY_SECRET', 'your_razorpay_key_secret');

// Stripe
define('STRIPE_ENABLED', false);
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_...');
define('STRIPE_SECRET_KEY', 'sk_test_...');

// PayPal
define('PAYPAL_ENABLED', false);
define('PAYPAL_CLIENT_ID', 'your_paypal_client_id');
define('PAYPAL_SECRET', 'your_paypal_secret');
define('PAYPAL_MODE', 'sandbox');         // 'sandbox' or 'live'

// ============================================
// BACKUP CONFIGURATION
// ============================================
define('BACKUP_PATH', '../backups/');
define('AUTO_BACKUP_ENABLED', true);
define('BACKUP_RETENTION_DAYS', 30);      // Keep backups for 30 days
define('BACKUP_SCHEDULE', 'daily');       // 'daily', 'weekly', 'monthly'

// ============================================
// FILE UPLOAD SETTINGS
// ============================================
define('UPLOAD_MAX_SIZE', 5242880);       // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);
define('UPLOAD_PATH', '../uploads/');

// ============================================
// TIMEZONE & LOCALE
// ============================================
date_default_timezone_set('Asia/Kolkata');
define('DATE_FORMAT', 'Y-m-d');
define('TIME_FORMAT', 'H:i:s');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('CURRENCY_SYMBOL', '₹');
define('CURRENCY_CODE', 'INR');

// ============================================
// PERFORMANCE & OPTIMIZATION
// ============================================
define('ENABLE_CACHE', true);
define('CACHE_DURATION', 3600);           // Cache duration in seconds
define('ENABLE_COMPRESSION', true);
define('DEBUG_MODE', false);              // Set to false in production

// ============================================
// LOGGING
// ============================================
define('ENABLE_ERROR_LOG', true);
define('LOG_PATH', '../logs/');
define('LOG_LEVEL', 'ERROR');             // 'DEBUG', 'INFO', 'WARNING', 'ERROR'

// ============================================
// DATABASE CONNECTION
// ============================================
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        if (DEBUG_MODE) {
            die("Connection failed: " . $conn->connect_error);
        } else {
            die("Database connection failed. Please contact administrator.");
        }
    }
    
    $conn->set_charset(DB_CHARSET);
    
} catch (Exception $e) {
    if (DEBUG_MODE) {
        die("Database error: " . $e->getMessage());
    } else {
        die("System error. Please contact administrator.");
    }
}

// ============================================
// SESSION CONFIGURATION
// ============================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', FORCE_HTTPS ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// ============================================
// ERROR REPORTING
// ============================================
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_PATH . 'error.log');
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Get gym settings from database
 */
function get_gym_settings() {
    global $conn;
    $settings = [];
    $result = $conn->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

/**
 * Check user role
 */
function require_role($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header('Location: ../login.php');
        exit;
    }
}

/**
 * Check permission
 */
function require_permission($module, $action) {
    // Implement RBAC permission check
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
}

/**
 * Log activity
 */
function log_activity($action, $description, $module = 'general') {
    global $conn;
    $user_id = $_SESSION['user_id'] ?? 0;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, description, module, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issss", $user_id, $action, $description, $module, $ip_address);
    $stmt->execute();
    $stmt->close();
}

/**
 * Sanitize input
 */
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
