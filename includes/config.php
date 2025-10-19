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

function require_role($role) {
    if (!is_logged_in() || get_user_role() !== $role) {
        redirect('login.php');
    }
}
?>