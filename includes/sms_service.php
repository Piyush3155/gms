<?php
/**
 * SMS Service Integration
 * Supports multiple SMS providers: Twilio, MSG91, AWS SNS
 * 
 * Features:
 * - Multi-provider support
 * - SMS templates
 * - Bulk SMS sending
 * - Delivery tracking
 * - SMS logging
 * - Scheduled SMS
 * 
 * @package GMS
 * @version 2.0
 */

require_once 'config.php';

class SMSService {
    private $conn;
    private $provider;
    private $config;
    
    // SMS provider configurations
    private $providerConfigs = [
        'twilio' => [
            'account_sid' => 'YOUR_TWILIO_ACCOUNT_SID',
            'auth_token' => 'YOUR_TWILIO_AUTH_TOKEN',
            'from_number' => '+1234567890',
            'enabled' => true
        ],
        'msg91' => [
            'auth_key' => 'YOUR_MSG91_AUTH_KEY',
            'sender_id' => 'GYMGMS',
            'route' => '4', // 4 for transactional
            'enabled' => true
        ],
        'aws_sns' => [
            'access_key' => 'YOUR_AWS_ACCESS_KEY',
            'secret_key' => 'YOUR_AWS_SECRET_KEY',
            'region' => 'us-east-1',
            'enabled' => false
        ]
    ];
    
    // SMS templates
    private $templates = [
        'attendance_reminder' => 'Hi {name}, we missed you at the gym today! Your health journey awaits. See you soon! - {gym_name}',
        'payment_due' => 'Hi {name}, your membership expires on {expiry_date}. Please renew to continue. Amount: Rs.{amount}. - {gym_name}',
        'payment_success' => 'Hi {name}, your payment of Rs.{amount} has been received. Thank you! Receipt: {receipt_number} - {gym_name}',
        'class_booking' => 'Hi {name}, your class "{class_name}" is confirmed for {date} at {time}. See you there! - {gym_name}',
        'class_reminder' => 'Hi {name}, reminder: Your class "{class_name}" starts in 1 hour at {time}. - {gym_name}',
        'membership_welcome' => 'Welcome to {gym_name}, {name}! Your membership is active until {expiry_date}. Let\'s achieve your fitness goals together!',
        'birthday_wish' => 'Happy Birthday {name}! 🎉 Wishing you a year full of health and fitness. Enjoy a free session on us! - {gym_name}',
        'workout_motivation' => 'Hi {name}, stay consistent! Every workout counts. We believe in you! - {gym_name}',
        'overdue_payment' => 'Hi {name}, your membership has expired. Please renew to continue enjoying our services. Visit us or pay online. - {gym_name}'
    ];
    
    public function __construct($provider = 'msg91') {
        global $conn;
        $this->conn = $conn;
        $this->provider = $provider;
        $this->config = $this->providerConfigs[$provider] ?? [];
        
        // Initialize SMS log table
        $this->initializeDatabase();
    }
    
    /**
     * Initialize SMS logs table
     */
    private function initializeDatabase() {
        $sql = "CREATE TABLE IF NOT EXISTS sms_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT,
            phone VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            template_name VARCHAR(100),
            provider VARCHAR(50),
            status ENUM('sent', 'failed', 'pending', 'delivered') DEFAULT 'pending',
            message_id VARCHAR(255),
            error_message TEXT,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            delivered_at TIMESTAMP NULL,
            FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
            INDEX idx_phone (phone),
            INDEX idx_status (status),
            INDEX idx_sent_at (sent_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->query($sql);
        
        // Create scheduled SMS table
        $sql = "CREATE TABLE IF NOT EXISTS scheduled_sms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT,
            phone VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            template_name VARCHAR(100),
            scheduled_for DATETIME NOT NULL,
            status ENUM('pending', 'sent', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
            INDEX idx_scheduled_for (scheduled_for),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->query($sql);
    }
    
    /**
     * Send SMS
     * 
     * @param string $phone Phone number with country code
     * @param string $message Message content
     * @param int $memberId Optional member ID for logging
     * @param string $templateName Optional template name for tracking
     * @return array Result with success status and message ID
     */
    public function sendSMS($phone, $message, $memberId = null, $templateName = null) {
        try {
            // Validate phone number
            $phone = $this->formatPhoneNumber($phone);
            
            if (!$this->config['enabled']) {
                throw new Exception("SMS provider {$this->provider} is not enabled");
            }
            
            // Send SMS based on provider
            $result = null;
            
            switch ($this->provider) {
                case 'twilio':
                    $result = $this->sendViaTwilio($phone, $message);
                    break;
                case 'msg91':
                    $result = $this->sendViaMSG91($phone, $message);
                    break;
                case 'aws_sns':
                    $result = $this->sendViaAWSSNS($phone, $message);
                    break;
                default:
                    throw new Exception("Unsupported SMS provider: {$this->provider}");
            }
            
            // Log SMS
            $this->logSMS([
                'member_id' => $memberId,
                'phone' => $phone,
                'message' => $message,
                'template_name' => $templateName,
                'provider' => $this->provider,
                'status' => 'sent',
                'message_id' => $result['message_id'] ?? null
            ]);
            
            log_activity('sms_sent', "SMS sent to {$phone}: " . substr($message, 0, 50), 'sms');
            
            return [
                'success' => true,
                'message_id' => $result['message_id'] ?? null,
                'provider' => $this->provider
            ];
            
        } catch (Exception $e) {
            error_log("SMS sending failed: " . $e->getMessage());
            
            // Log failed SMS
            $this->logSMS([
                'member_id' => $memberId,
                'phone' => $phone,
                'message' => $message,
                'template_name' => $templateName,
                'provider' => $this->provider,
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send SMS using template
     * 
     * @param string $templateName Template name
     * @param array $member Member data
     * @param array $variables Additional template variables
     * @return array Result
     */
    public function sendTemplatedSMS($templateName, $member, $variables = []) {
        if (!isset($this->templates[$templateName])) {
            return [
                'success' => false,
                'error' => 'Template not found: ' . $templateName
            ];
        }
        
        // Prepare template variables
        $vars = array_merge([
            'name' => $member['name'] ?? '',
            'gym_name' => SITE_NAME,
            'phone' => $member['phone'] ?? ''
        ], $variables);
        
        // Replace placeholders in template
        $message = $this->templates[$templateName];
        foreach ($vars as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        return $this->sendSMS($member['phone'], $message, $member['id'] ?? null, $templateName);
    }
    
    /**
     * Send bulk SMS
     * 
     * @param array $recipients Array of recipient data [['phone' => '', 'message' => '', 'member_id' => '']]
     * @return array Results
     */
    public function sendBulkSMS($recipients) {
        $results = [
            'total' => count($recipients),
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        foreach ($recipients as $recipient) {
            $phone = $recipient['phone'];
            $message = $recipient['message'];
            $memberId = $recipient['member_id'] ?? null;
            
            $result = $this->sendSMS($phone, $message, $memberId);
            
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
            
            $results['details'][] = [
                'phone' => $phone,
                'success' => $result['success'],
                'error' => $result['error'] ?? null
            ];
            
            // Rate limiting: wait 100ms between messages
            usleep(100000);
        }
        
        log_activity('bulk_sms_sent', "Bulk SMS sent: {$results['success']} successful, {$results['failed']} failed", 'sms');
        
        return $results;
    }
    
    /**
     * Schedule SMS for later delivery
     * 
     * @param string $phone Phone number
     * @param string $message Message content
     * @param string $scheduledFor DateTime string (Y-m-d H:i:s)
     * @param int $memberId Member ID
     * @param string $templateName Template name
     * @return bool Success status
     */
    public function scheduleSMS($phone, $message, $scheduledFor, $memberId = null, $templateName = null) {
        $stmt = $this->conn->prepare("INSERT INTO scheduled_sms (member_id, phone, message, template_name, scheduled_for) VALUES (?, ?, ?, ?, ?)");
        
        $stmt->bind_param("issss", $memberId, $phone, $message, $templateName, $scheduledFor);
        
        $success = $stmt->execute();
        
        if ($success) {
            log_activity('sms_scheduled', "SMS scheduled for {$scheduledFor} to {$phone}", 'sms');
        }
        
        return $success;
    }
    
    /**
     * Process scheduled SMS (run via cron)
     */
    public function processScheduledSMS() {
        $now = date('Y-m-d H:i:s');
        
        $stmt = $this->conn->prepare("SELECT * FROM scheduled_sms WHERE scheduled_for <= ? AND status = 'pending' ORDER BY scheduled_for ASC");
        $stmt->bind_param("s", $now);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sentCount = 0;
        
        while ($sms = $result->fetch_assoc()) {
            $sendResult = $this->sendSMS($sms['phone'], $sms['message'], $sms['member_id'], $sms['template_name']);
            
            if ($sendResult['success']) {
                $updateStmt = $this->conn->prepare("UPDATE scheduled_sms SET status = 'sent' WHERE id = ?");
                $updateStmt->bind_param("i", $sms['id']);
                $updateStmt->execute();
                $sentCount++;
            }
        }
        
        return $sentCount;
    }
    
    /**
     * Send attendance reminder to members who haven't checked in
     */
    public function sendAttendanceReminders() {
        // Get members who haven't attended in the last 3 days
        $stmt = $this->conn->prepare("
            SELECT m.id, m.name, m.phone
            FROM members m
            WHERE m.status = 'active'
            AND m.phone IS NOT NULL
            AND m.phone != ''
            AND NOT EXISTS (
                SELECT 1 FROM attendance a
                WHERE a.user_id = m.id
                AND a.role = 'member'
                AND a.date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
            )
            AND NOT EXISTS (
                SELECT 1 FROM sms_log sl
                WHERE sl.member_id = m.id
                AND sl.template_name = 'attendance_reminder'
                AND sl.sent_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)
            )
        ");
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $remindersSent = 0;
        
        while ($member = $result->fetch_assoc()) {
            $sendResult = $this->sendTemplatedSMS('attendance_reminder', $member);
            
            if ($sendResult['success']) {
                $remindersSent++;
            }
            
            // Rate limiting
            usleep(100000);
        }
        
        log_activity('attendance_reminders', "Sent {$remindersSent} attendance reminders via SMS", 'sms');
        
        return $remindersSent;
    }
    
    /**
     * Send payment reminders via SMS
     */
    public function sendPaymentReminders($daysBefore = 7) {
        $expiryDate = date('Y-m-d', strtotime("+{$daysBefore} days"));
        
        $stmt = $this->conn->prepare("
            SELECT m.id, m.name, m.phone, m.expiry_date, p.price
            FROM members m
            LEFT JOIN plans p ON m.plan_id = p.id
            WHERE m.expiry_date = ?
            AND m.status = 'active'
            AND m.phone IS NOT NULL
            AND m.phone != ''
        ");
        
        $stmt->bind_param("s", $expiryDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $remindersSent = 0;
        
        while ($member = $result->fetch_assoc()) {
            $variables = [
                'expiry_date' => date('d M Y', strtotime($member['expiry_date'])),
                'amount' => number_format($member['price'] ?? 0, 2)
            ];
            
            $sendResult = $this->sendTemplatedSMS('payment_due', $member, $variables);
            
            if ($sendResult['success']) {
                $remindersSent++;
            }
            
            usleep(100000);
        }
        
        log_activity('payment_reminders_sms', "Sent {$remindersSent} payment reminders via SMS", 'sms');
        
        return $remindersSent;
    }
    
    /**
     * Send class reminder 1 hour before class
     */
    public function sendClassReminders() {
        $oneHourLater = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $oneHourLaterEnd = date('Y-m-d H:i:s', strtotime('+1 hour 10 minutes'));
        
        // Assuming you have a class_bookings table
        $stmt = $this->conn->prepare("
            SELECT cb.*, m.name, m.phone, gc.name as class_name, gc.schedule_time
            FROM class_bookings cb
            JOIN members m ON cb.member_id = m.id
            JOIN group_classes gc ON cb.class_id = gc.id
            WHERE gc.schedule_date = CURDATE()
            AND gc.schedule_time BETWEEN ? AND ?
            AND cb.status = 'confirmed'
            AND m.phone IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 FROM sms_log sl
                WHERE sl.member_id = m.id
                AND sl.template_name = 'class_reminder'
                AND DATE(sl.sent_at) = CURDATE()
            )
        ");
        
        $stmt->bind_param("ss", $oneHourLater, $oneHourLaterEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $remindersSent = 0;
        
        while ($booking = $result->fetch_assoc()) {
            $variables = [
                'class_name' => $booking['class_name'],
                'time' => date('g:i A', strtotime($booking['schedule_time']))
            ];
            
            $member = [
                'id' => $booking['member_id'],
                'name' => $booking['name'],
                'phone' => $booking['phone']
            ];
            
            $sendResult = $this->sendTemplatedSMS('class_reminder', $member, $variables);
            
            if ($sendResult['success']) {
                $remindersSent++;
            }
            
            usleep(100000);
        }
        
        log_activity('class_reminders_sms', "Sent {$remindersSent} class reminders via SMS", 'sms');
        
        return $remindersSent;
    }
    
    /**
     * Provider-specific implementations
     */
    
    private function sendViaTwilio($phone, $message) {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->config['account_sid']}/Messages.json";
        
        $data = [
            'From' => $this->config['from_number'],
            'To' => $phone,
            'Body' => $message
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, $this->config['account_sid'] . ':' . $this->config['auth_token']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($response, true);
            return ['message_id' => $responseData['sid'] ?? null];
        }
        
        throw new Exception("Twilio API error: " . $response);
    }
    
    private function sendViaMSG91($phone, $message) {
        $url = "https://api.msg91.com/api/v5/flow/";
        
        // Remove +91 if present for Indian numbers
        $mobileNumber = str_replace('+91', '', $phone);
        
        $data = [
            'sender' => $this->config['sender_id'],
            'route' => $this->config['route'],
            'country' => '91',
            'sms' => [[
                'message' => $message,
                'to' => [$mobileNumber]
            ]]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'authkey: ' . $this->config['auth_key']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($response, true);
            return ['message_id' => $responseData['request_id'] ?? null];
        }
        
        throw new Exception("MSG91 API error: " . $response);
    }
    
    private function sendViaAWSSNS($phone, $message) {
        // AWS SNS implementation would require AWS SDK
        // Simplified example:
        
        // Try to load Composer autoloader first (common case), then fallback to the bundled AWS autoloader.
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        } elseif (file_exists(__DIR__ . '/aws-sdk/aws-autoloader.php')) {
            require_once __DIR__ . '/aws-sdk/aws-autoloader.php';
        }
        
        // Ensure the AWS SNS client class exists before instantiating to avoid undefined type errors.
        if (!class_exists('\Aws\Sns\SnsClient')) {
            throw new Exception("AWS SDK for PHP not found: please install aws/aws-sdk-php via Composer or include the AWS SDK autoloader.");
        }
        
        $snsClass = '\\Aws\\Sns\\SnsClient';
        if (!class_exists($snsClass)) {
            throw new Exception("AWS SDK for PHP not found: Aws\\Sns\\SnsClient is not available.");
        }
        $sns = new $snsClass([
            'region' => $this->config['region'],
            'version' => 'latest',
            'credentials' => [
                'key' => $this->config['access_key'],
                'secret' => $this->config['secret_key']
            ]
        ]);
        
        $result = $sns->publish([
            'Message' => $message,
            'PhoneNumber' => $phone
        ]);
        
        return ['message_id' => $result['MessageId'] ?? null];
    }
    
    /**
     * Utility methods
     */
    
    private function formatPhoneNumber($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Add country code if not present
        if (!str_starts_with($phone, '+')) {
            // Assume Indian number if 10 digits
            if (strlen($phone) === 10) {
                $phone = '+91' . $phone;
            }
        }
        
        return $phone;
    }
    
    private function logSMS($data) {
        $stmt = $this->conn->prepare("INSERT INTO sms_log (member_id, phone, message, template_name, provider, status, message_id, error_message) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("isssssss",
            $data['member_id'],
            $data['phone'],
            $data['message'],
            $data['template_name'],
            $data['provider'],
            $data['status'],
            $data['message_id'],
            $data['error_message']
        );
        
        return $stmt->execute();
    }
    
    /**
     * Get SMS statistics
     */
    public function getSMSStats($startDate = null, $endDate = null) {
        $where = "1=1";
        $params = [];
        
        if ($startDate) {
            $where .= " AND sent_at >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $where .= " AND sent_at <= ?";
            $params[] = $endDate;
        }
        
        $sql = "SELECT 
            COUNT(*) as total_sent,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as successful,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
            template_name,
            provider
        FROM sms_log
        WHERE {$where}
        GROUP BY template_name, provider";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
