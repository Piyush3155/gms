<?php
// Application configuration
define('SITE_NAME', 'Gym Management System');
define('SITE_URL', 'http://localhost/gms/');
define('UPLOAD_PATH', 'assets/images/');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Include database connection
require_once 'db.php';

// Helper functions
function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

function redirect($url) {
    header("Location: $url");
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