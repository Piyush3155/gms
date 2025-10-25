<?php
require_once 'config.php';

$key_data = authenticate_api();

if (!check_api_permission($key_data, 'attendance')) {
    send_response(['error' => 'Insufficient permissions'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $user_id = $_GET['user_id'] ?? null;
        $date = $_GET['date'] ?? null;
        
        if ($user_id && $date) {
            // Get attendance for specific user and date
            $stmt = $conn->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
            $stmt->bind_param("is", $user_id, $date);
            $stmt->execute();
            $result = $stmt->get_result();
            $attendance = $result->fetch_assoc();
            $stmt->close();
            
            if ($attendance) {
                send_response($attendance);
            } else {
                send_response(['error' => 'Attendance record not found'], 404);
            }
        } elseif ($user_id) {
            // Get attendance for specific user
            $stmt = $conn->prepare("SELECT * FROM attendance WHERE user_id = ? ORDER BY date DESC");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $attendance = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            send_response($attendance);
        } else {
            // Get recent attendance records
            $result = $conn->query("SELECT a.*, u.name as user_name FROM attendance a JOIN users u ON a.user_id = u.id ORDER BY a.date DESC, a.check_in DESC LIMIT 100");
            $attendance = $result->fetch_all(MYSQLI_ASSOC);
            send_response($attendance);
        }
        break;
        
    case 'POST':
        $data = get_request_data();
        
        if (!$data || !isset($data['user_id']) || !isset($data['date'])) {
            send_response(['error' => 'User ID and date are required'], 400);
        }
        
        $user_id = $data['user_id'];
        $date = $data['date'];
        $role = $data['role'] ?? 'member';
        $check_in = $data['check_in'] ?? date('H:i:s');
        $status = $data['status'] ?? 'present';
        
        // Check if attendance already exists for this user and date
        $stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND date = ?");
        $stmt->bind_param("is", $user_id, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing
            $stmt = $conn->prepare("UPDATE attendance SET check_in = ?, check_out = ?, status = ? WHERE user_id = ? AND date = ?");
            $stmt->bind_param("sssis", $check_in, $data['check_out'] ?? null, $status, $user_id, $date);
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO attendance (user_id, role, date, check_in, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $role, $date, $check_in, $status);
        }
        
        if ($stmt->execute()) {
            $stmt->close();
            send_response(['message' => 'Attendance recorded successfully'], 201);
        } else {
            send_response(['error' => 'Failed to record attendance'], 500);
        }
        break;
        
    default:
        send_response(['error' => 'Method not allowed'], 405);
}
?>