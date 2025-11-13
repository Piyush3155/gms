<?php
/**
 * QR Code Service for Gym Management System
 * Generates QR codes for members and handles QR-based attendance
 */

require_once '../phpqrcode/qrlib.php';

class QRCodeService {
    private $conn;
    private $qr_path = '../assets/images/qrcodes/';
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        
        // Create QR code directory if it doesn't exist
        if (!file_exists($this->qr_path)) {
            mkdir($this->qr_path, 0755, true);
        }
    }
    
    /**
     * Generate QR code for a member
     */
    public function generateMemberQRCode($member_id) {
        // Get member details
        $stmt = $this->conn->prepare("SELECT id, name, email, expiry_date, status FROM members WHERE id = ?");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $member = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$member) {
            return ['success' => false, 'message' => 'Member not found'];
        }
        
        // Generate unique token
        $token = $this->generateUniqueToken($member_id);
        
        // Store token in database
        $this->storeQRToken($member_id, $token);
        
        // Create QR code data (JSON format)
        $qr_data = json_encode([
            'member_id' => $member['id'],
            'token' => $token,
            'type' => 'member_attendance',
            'generated' => time()
        ]);
        
        // Generate QR code image
        $filename = "member_{$member_id}_qr.png";
        $filepath = $this->qr_path . $filename;
        
        QRcode::png($qr_data, $filepath, QR_ECLEVEL_H, 10);
        
        return [
            'success' => true,
            'qr_path' => $filepath,
            'qr_url' => str_replace('../', '', $filepath),
            'token' => $token,
            'member' => $member
        ];
    }
    
    /**
     * Generate unique token for QR code
     */
    private function generateUniqueToken($member_id) {
        return hash('sha256', $member_id . time() . random_bytes(16));
    }
    
    /**
     * Store QR token in database
     */
    private function storeQRToken($member_id, $token) {
        // Create table if not exists
        $sql = "CREATE TABLE IF NOT EXISTS qr_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT NOT NULL,
            token VARCHAR(255) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            status ENUM('active', 'revoked', 'expired') DEFAULT 'active',
            FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_member (member_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->conn->query($sql);
        
        // Revoke old tokens for this member
        $stmt = $this->conn->prepare("UPDATE qr_tokens SET status = 'revoked' WHERE member_id = ? AND status = 'active'");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $stmt->close();
        
        // Insert new token
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 year'));
        $stmt = $this->conn->prepare("INSERT INTO qr_tokens (member_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $member_id, $token, $expires_at);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Validate QR code and mark attendance
     */
    public function validateAndMarkAttendance($qr_data) {
        try {
            $data = json_decode($qr_data, true);
            
            if (!$data || !isset($data['member_id']) || !isset($data['token'])) {
                return ['success' => false, 'message' => 'Invalid QR code format'];
            }
            
            // Verify token
            $stmt = $this->conn->prepare("SELECT qt.*, m.name, m.status, m.expiry_date 
                                         FROM qr_tokens qt 
                                         JOIN members m ON qt.member_id = m.id 
                                         WHERE qt.token = ? AND qt.status = 'active'");
            $stmt->bind_param("s", $data['token']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'Invalid or expired QR code'];
            }
            
            $token_data = $result->fetch_assoc();
            $stmt->close();
            
            // Check if membership is active
            if ($token_data['status'] !== 'active') {
                return ['success' => false, 'message' => 'Membership is not active'];
            }
            
            // Check if membership has expired
            if (strtotime($token_data['expiry_date']) < time()) {
                return ['success' => false, 'message' => 'Membership has expired'];
            }
            
            // Check if token has expired
            if ($token_data['expires_at'] && strtotime($token_data['expires_at']) < time()) {
                return ['success' => false, 'message' => 'QR code has expired'];
            }
            
            // Mark attendance
            $attendance_result = $this->markAttendance($data['member_id']);
            
            if ($attendance_result['success']) {
                return [
                    'success' => true,
                    'message' => 'Attendance marked successfully',
                    'member_name' => $token_data['name'],
                    'member_id' => $data['member_id'],
                    'check_in_time' => $attendance_result['check_in_time'],
                    'action' => $attendance_result['action']
                ];
            } else {
                return $attendance_result;
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error processing QR code: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mark attendance for member
     */
    private function markAttendance($member_id) {
        $today = date('Y-m-d');
        $current_time = date('H:i:s');
        
        // Get user_id from member_id
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE id IN (SELECT id FROM members WHERE id = ?)");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $user_result = $stmt->get_result();
        
        if ($user_result->num_rows === 0) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        $user_id = $user_result->fetch_assoc()['id'];
        $stmt->close();
        
        // Check if already checked in today
        $stmt = $this->conn->prepare("SELECT id, check_in, check_out FROM attendance 
                                      WHERE user_id = ? AND date = ? AND role = 'member'");
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $attendance = $result->fetch_assoc();
            $stmt->close();
            
            // If already checked in but not checked out, mark check out
            if ($attendance['check_in'] && !$attendance['check_out']) {
                $update_stmt = $this->conn->prepare("UPDATE attendance SET check_out = ? WHERE id = ?");
                $update_stmt->bind_param("si", $current_time, $attendance['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                return [
                    'success' => true,
                    'action' => 'check_out',
                    'check_in_time' => $attendance['check_in'],
                    'check_out_time' => $current_time
                ];
            } else {
                return ['success' => false, 'message' => 'Already checked out for today'];
            }
        } else {
            $stmt->close();
            
            // New check-in
            $insert_stmt = $this->conn->prepare("INSERT INTO attendance (user_id, date, check_in, role, status) 
                                                VALUES (?, ?, ?, 'member', 'present')");
            $insert_stmt->bind_param("iss", $user_id, $today, $current_time);
            $insert_stmt->execute();
            $insert_stmt->close();
            
            return [
                'success' => true,
                'action' => 'check_in',
                'check_in_time' => $current_time
            ];
        }
    }
    
    /**
     * Generate member ID card with QR code
     */
    public function generateMemberIDCard($member_id) {
        require_once('../fpdf/fpdf.php');
        
        // Get member details
        $stmt = $this->conn->prepare("SELECT m.*, p.name as plan_name 
                                      FROM members m 
                                      LEFT JOIN plans p ON m.plan_id = p.id 
                                      WHERE m.id = ?");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $member = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$member) {
            return ['success' => false, 'message' => 'Member not found'];
        }
        
        // Generate QR code
        $qr_result = $this->generateMemberQRCode($member_id);
        
        if (!$qr_result['success']) {
            return $qr_result;
        }
        
        // Create ID card PDF
        $pdf = new FPDF('P', 'mm', [85.6, 53.98]); // Credit card size
        $pdf->AddPage();
        
        // Background
        $pdf->SetFillColor(102, 126, 234);
        $pdf->Rect(0, 0, 85.6, 20, 'F');
        
        // Gym name
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(5, 5);
        $pdf->Cell(50, 10, 'GYM MEMBER ID', 0, 1);
        
        // Member info
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY(5, 22);
        $pdf->Cell(50, 5, $member['name'], 0, 1);
        
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetX(5);
        $pdf->Cell(50, 4, 'ID: ' . $member['id'], 0, 1);
        $pdf->SetX(5);
        $pdf->Cell(50, 4, 'Plan: ' . ($member['plan_name'] ?? 'N/A'), 0, 1);
        $pdf->SetX(5);
        $pdf->Cell(50, 4, 'Expires: ' . date('d/m/Y', strtotime($member['expiry_date'])), 0, 1);
        
        // QR Code
        $pdf->Image($qr_result['qr_path'], 58, 22, 25, 25);
        
        // Save PDF
        $id_card_path = $this->qr_path . "member_{$member_id}_id_card.pdf";
        $pdf->Output('F', $id_card_path);
        
        return [
            'success' => true,
            'id_card_path' => $id_card_path,
            'id_card_url' => str_replace('../', '', $id_card_path),
            'qr_code_url' => $qr_result['qr_url']
        ];
    }
    
    /**
     * Get attendance history for member via QR
     */
    public function getAttendanceHistory($member_id, $limit = 30) {
        $stmt = $this->conn->prepare("SELECT date, check_in, check_out, status 
                                      FROM attendance 
                                      WHERE user_id = ? AND role = 'member' 
                                      ORDER BY date DESC 
                                      LIMIT ?");
        $stmt->bind_param("ii", $member_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        $stmt->close();
        
        return $history;
    }
    
    /**
     * Revoke QR code token
     */
    public function revokeQRCode($member_id) {
        $stmt = $this->conn->prepare("UPDATE qr_tokens SET status = 'revoked' WHERE member_id = ?");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        return [
            'success' => $affected > 0,
            'message' => $affected > 0 ? 'QR code revoked successfully' : 'No active QR code found'
        ];
    }
    
    /**
     * Bulk generate QR codes for all active members
     */
    public function bulkGenerateQRCodes() {
        $result = $this->conn->query("SELECT id FROM members WHERE status = 'active'");
        $generated = 0;
        $failed = 0;
        
        while ($row = $result->fetch_assoc()) {
            $qr_result = $this->generateMemberQRCode($row['id']);
            if ($qr_result['success']) {
                $generated++;
            } else {
                $failed++;
            }
        }
        
        return [
            'success' => true,
            'generated' => $generated,
            'failed' => $failed
        ];
    }
}

// Initialize QR service if database connection exists
if (isset($conn)) {
    $qrService = new QRCodeService($conn);
}
?>
