<?php
require_once 'config.php';

$key_data = authenticate_api();

if (!check_api_permission($key_data, 'members')) {
    send_response(['error' => 'Insufficient permissions'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        if ($id) {
            // Get single member
            $stmt = $conn->prepare("SELECT id, name, email, contact, join_date, expiry_date, plan_id, trainer_id, status FROM members WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $member = $result->fetch_assoc();
            $stmt->close();
            
            if ($member) {
                send_response($member);
            } else {
                send_response(['error' => 'Member not found'], 404);
            }
        } else {
            // Get all members
            $result = $conn->query("SELECT id, name, email, contact, join_date, expiry_date, status FROM members ORDER BY name");
            $members = $result->fetch_all(MYSQLI_ASSOC);
            send_response($members);
        }
        break;
        
    case 'POST':
        $data = get_request_data();
        
        if (!$data || !isset($data['name']) || !isset($data['email'])) {
            send_response(['error' => 'Name and email are required'], 400);
        }
        
        $stmt = $conn->prepare("INSERT INTO members (name, email, contact, join_date, plan_id, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("ssssi", $data['name'], $data['email'], $data['contact'] ?? '', $data['join_date'] ?? date('Y-m-d'), $data['plan_id'] ?? null);
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            
            // Generate QR code
            $qr_code = 'GMS_MEMBER_' . $new_id . '_' . md5($new_id . $data['email']);
            $update_stmt = $conn->prepare("UPDATE members SET qr_code = ? WHERE id = ?");
            $update_stmt->bind_param("si", $qr_code, $new_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            $stmt->close();
            send_response(['id' => $new_id, 'message' => 'Member created successfully'], 201);
        } else {
            send_response(['error' => 'Failed to create member'], 500);
        }
        break;
        
    case 'PUT':
        if (!$id) {
            send_response(['error' => 'Member ID required'], 400);
        }
        
        $data = get_request_data();
        
        if (!$data) {
            send_response(['error' => 'No data provided'], 400);
        }
        
        $updates = [];
        $types = '';
        $params = [];
        
        if (isset($data['name'])) {
            $updates[] = "name = ?";
            $types .= 's';
            $params[] = $data['name'];
        }
        if (isset($data['email'])) {
            $updates[] = "email = ?";
            $types .= 's';
            $params[] = $data['email'];
        }
        if (isset($data['contact'])) {
            $updates[] = "contact = ?";
            $types .= 's';
            $params[] = $data['contact'];
        }
        if (isset($data['status'])) {
            $updates[] = "status = ?";
            $types .= 's';
            $params[] = $data['status'];
        }
        
        if (empty($updates)) {
            send_response(['error' => 'No valid fields to update'], 400);
        }
        
        $sql = "UPDATE members SET " . implode(', ', $updates) . " WHERE id = ?";
        $types .= 'i';
        $params[] = $id;
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $stmt->close();
            send_response(['message' => 'Member updated successfully']);
        } else {
            send_response(['error' => 'Failed to update member'], 500);
        }
        break;
        
    case 'DELETE':
        if (!$id) {
            send_response(['error' => 'Member ID required'], 400);
        }
        
        $stmt = $conn->prepare("DELETE FROM members WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            send_response(['message' => 'Member deleted successfully']);
        } else {
            send_response(['error' => 'Failed to delete member'], 500);
        }
        break;
        
    default:
        send_response(['error' => 'Method not allowed'], 405);
}
?>