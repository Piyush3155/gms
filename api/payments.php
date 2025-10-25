<?php
require_once 'config.php';

$key_data = authenticate_api();

if (!check_api_permission($key_data, 'payments')) {
    send_response(['error' => 'Insufficient permissions'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        if ($id) {
            // Get single payment
            $stmt = $conn->prepare("SELECT p.*, m.name as member_name FROM payments p JOIN members m ON p.member_id = m.id WHERE p.id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $payment = $result->fetch_assoc();
            $stmt->close();
            
            if ($payment) {
                send_response($payment);
            } else {
                send_response(['error' => 'Payment not found'], 404);
            }
        } else {
            // Get payments with optional filters
            $where = "";
            $params = [];
            $types = "";
            
            if (isset($_GET['member_id'])) {
                $where .= " AND p.member_id = ?";
                $types .= "i";
                $params[] = $_GET['member_id'];
            }
            
            if (isset($_GET['status'])) {
                $where .= " AND p.status = ?";
                $types .= "s";
                $params[] = $_GET['status'];
            }
            
            $sql = "SELECT p.*, m.name as member_name FROM payments p JOIN members m ON p.member_id = m.id WHERE 1=1 $where ORDER BY p.payment_date DESC";
            
            if (!empty($params)) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $conn->query($sql);
            }
            
            $payments = $result->fetch_all(MYSQLI_ASSOC);
            send_response($payments);
        }
        break;
        
    case 'POST':
        $data = get_request_data();
        
        if (!$data || !isset($data['member_id']) || !isset($data['amount'])) {
            send_response(['error' => 'Member ID and amount are required'], 400);
        }
        
        $stmt = $conn->prepare("INSERT INTO payments (member_id, plan_id, amount, payment_date, method, status) VALUES (?, ?, ?, ?, ?, 'paid')");
        $stmt->bind_param("iidss", 
            $data['member_id'], 
            $data['plan_id'] ?? null, 
            $data['amount'], 
            $data['payment_date'] ?? date('Y-m-d'), 
            $data['method'] ?? 'api'
        );
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            $stmt->close();
            send_response(['id' => $new_id, 'message' => 'Payment recorded successfully'], 201);
        } else {
            send_response(['error' => 'Failed to record payment'], 500);
        }
        break;
        
    default:
        send_response(['error' => 'Method not allowed'], 405);
}
?>