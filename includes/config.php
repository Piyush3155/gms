<?php
// Application configuration
define('SITE_URL', 'http://localhost/gms/');
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/');
define('UPLOAD_PATH', 'assets/images/');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Include database connection
require_once 'db.php';

// Get gym settings from database
function get_gym_settings() {
    global $conn;
    $result = $conn->query("SELECT * FROM settings LIMIT 1");
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    // Return default values if no settings found
    return [
        'gym_name' => 'Gym Management System',
        'tagline' => 'Your Fitness Journey Starts Here',
        'contact' => '',
        'address' => '',
        'email' => '',
        'logo' => ''
    ];
}

// Get gym name
function get_gym_name() {
    $settings = get_gym_settings();
    return $settings['gym_name'];
}

// Get gym tagline
function get_gym_tagline() {
    $settings = get_gym_settings();
    return $settings['tagline'];
}

// For backward compatibility, define SITE_NAME as gym name
define('SITE_NAME', get_gym_name());

// Helper functions
function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_user_role() {
    return $_SESSION['user_role'] ?? null;
}

function get_user_role_id() {
    return $_SESSION['user_role_id'] ?? null;
}

function has_permission($module, $action) {
    $role_id = get_user_role_id();
    if (!$role_id) return false;
    
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM role_permissions rp 
                           JOIN permissions p ON rp.permission_id = p.id 
                           WHERE rp.role_id = ? AND p.module = ? AND p.action = ?");
    $stmt->bind_param("iss", $role_id, $module, $action);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();
    return $count > 0;
}

function require_permission($module, $action) {
    if (!is_logged_in() || !has_permission($module, $action)) {
        redirect('login.php');
    }
}

function require_role($role) {
    if (!is_logged_in() || get_user_role() !== $role) {
        redirect('login.php');
    }
}

function log_activity($action, $module = null, $details = null) {
    global $conn;
    $user_id = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, module, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $action, $module, $details, $ip);
    $stmt->execute();
    $stmt->close();
}
?>