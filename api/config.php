<?php
// API Base Configuration
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../includes/config.php';

// API Key Authentication
function authenticate_api() {
    $api_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
    
    if (!$api_key) {
        http_response_code(401);
        echo json_encode(['error' => 'API key required']);
        exit;
    }
    
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM api_keys WHERE api_key = ? AND status = 'active'");
    $stmt->bind_param("s", $api_key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key']);
        exit;
    }
    
    $key_data = $result->fetch_assoc();
    $stmt->close();
    
    // Update last used
    $conn->query("UPDATE api_keys SET last_used = NOW() WHERE id = {$key_data['id']}");
    
    return $key_data;
}

// Check permissions
function check_api_permission($key_data, $endpoint) {
    $permissions = json_decode($key_data['permissions'], true);
    return in_array($endpoint, $permissions) || in_array('all', $permissions);
}

// Send JSON response
function send_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Get request data
function get_request_data() {
    return json_decode(file_get_contents('php://input'), true);
}
?>