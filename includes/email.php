<?php
/**
 * Email Notification System
 * Handles all email communications for the Gym Management System
 */

// Email configuration
class EmailConfig {
    public static $smtp_host = 'smtp.gmail.com';
    public static $smtp_port = 587;
    public static $smtp_username = 'your-email@gmail.com';
    public static $smtp_password = 'your-app-password';
    public static $smtp_encryption = 'tls';
    public static $from_email = 'noreply@gymmanagement.com';
    public static $from_name = 'Gym Management System';
}

class EmailService {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Send email using PHP mail function or SMTP
     */
    public function sendEmail($to, $subject, $message, $headers = []) {
        // Get gym settings
        $settings = $this->getGymSettings();
        
        // Build headers
        $default_headers = [
            'MIME-Version' => '1.0',
            'Content-type' => 'text/html; charset=UTF-8',
            'From' => EmailConfig::$from_name . ' <' . EmailConfig::$from_email . '>',
            'Reply-To' => $settings['email'] ?? EmailConfig::$from_email,
            'X-Mailer' => 'PHP/' . phpversion()
        ];
        
        $all_headers = array_merge($default_headers, $headers);
        $header_string = '';
        foreach ($all_headers as $key => $value) {
            $header_string .= "$key: $value\r\n";
        }
        
        // Log email attempt
        $this->logEmail($to, $subject, 'sending');
        
        // Send email
        $result = mail($to, $subject, $message, $header_string);
        
        // Log result
        $this->logEmail($to, $subject, $result ? 'sent' : 'failed');
        
        return $result;
    }
    
    /**
     * Get gym settings
     */
    private function getGymSettings() {
        $result = $this->conn->query("SELECT * FROM settings LIMIT 1");
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return [];
    }
    
    /**
     * Log email activity
     */
    private function logEmail($to, $subject, $status) {
        // Create email_log table if not exists
        $sql = "CREATE TABLE IF NOT EXISTS email_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            status ENUM('sending', 'sent', 'failed') NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_recipient (recipient),
            INDEX idx_sent_at (sent_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->conn->query($sql);
        
        $stmt = $this->conn->prepare("INSERT INTO email_log (recipient, subject, status) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $to, $subject, $status);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Send welcome email to new member
     */
    public function sendWelcomeEmail($member_data) {
        $settings = $this->getGymSettings();
        $gym_name = $settings['gym_name'] ?? 'Gym Management System';
        
        $subject = "Welcome to $gym_name!";
        
        $message = $this->getEmailTemplate('welcome', [
            'member_name' => $member_data['name'],
            'gym_name' => $gym_name,
            'membership_plan' => $member_data['plan_name'] ?? 'N/A',
            'join_date' => $member_data['join_date'],
            'expiry_date' => $member_data['expiry_date'],
            'contact' => $settings['contact'] ?? '',
            'address' => $settings['address'] ?? '',
            'email' => $settings['email'] ?? ''
        ]);
        
        return $this->sendEmail($member_data['email'], $subject, $message);
    }
    
    /**
     * Send membership expiry reminder
     */
    public function sendExpiryReminder($member_data, $days_remaining) {
        $settings = $this->getGymSettings();
        $gym_name = $settings['gym_name'] ?? 'Gym Management System';
        
        $subject = "Membership Expiry Reminder - $gym_name";
        
        $message = $this->getEmailTemplate('expiry_reminder', [
            'member_name' => $member_data['name'],
            'gym_name' => $gym_name,
            'days_remaining' => $days_remaining,
            'expiry_date' => $member_data['expiry_date'],
            'renewal_link' => SITE_URL . 'admin/renew_membership.php?id=' . $member_data['id'],
            'contact' => $settings['contact'] ?? '',
            'email' => $settings['email'] ?? ''
        ]);
        
        return $this->sendEmail($member_data['email'], $subject, $message);
    }
    
    /**
     * Send payment receipt
     */
    public function sendPaymentReceipt($payment_data) {
        $settings = $this->getGymSettings();
        $gym_name = $settings['gym_name'] ?? 'Gym Management System';
        
        $subject = "Payment Receipt - $gym_name";
        
        $message = $this->getEmailTemplate('payment_receipt', [
            'member_name' => $payment_data['member_name'],
            'gym_name' => $gym_name,
            'invoice_no' => $payment_data['invoice_no'],
            'payment_date' => $payment_data['payment_date'],
            'amount' => $payment_data['amount'],
            'payment_method' => $payment_data['method'],
            'plan_name' => $payment_data['plan_name'],
            'contact' => $settings['contact'] ?? '',
            'email' => $settings['email'] ?? ''
        ]);
        
        return $this->sendEmail($payment_data['member_email'], $subject, $message);
    }
    
    /**
     * Send class booking confirmation
     */
    public function sendClassBookingConfirmation($booking_data) {
        $settings = $this->getGymSettings();
        $gym_name = $settings['gym_name'] ?? 'Gym Management System';
        
        $subject = "Class Booking Confirmation - $gym_name";
        
        $message = $this->getEmailTemplate('class_booking', [
            'member_name' => $booking_data['member_name'],
            'gym_name' => $gym_name,
            'class_name' => $booking_data['class_name'],
            'class_date' => $booking_data['class_date'],
            'start_time' => $booking_data['start_time'],
            'end_time' => $booking_data['end_time'],
            'trainer_name' => $booking_data['trainer_name'] ?? 'TBA',
            'contact' => $settings['contact'] ?? '',
            'email' => $settings['email'] ?? ''
        ]);
        
        return $this->sendEmail($booking_data['member_email'], $subject, $message);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordReset($user_data, $reset_token) {
        $settings = $this->getGymSettings();
        $gym_name = $settings['gym_name'] ?? 'Gym Management System';
        
        $subject = "Password Reset Request - $gym_name";
        
        $reset_link = SITE_URL . "reset_password.php?token=$reset_token";
        
        $message = $this->getEmailTemplate('password_reset', [
            'user_name' => $user_data['name'],
            'gym_name' => $gym_name,
            'reset_link' => $reset_link,
            'expiry_time' => '1 hour',
            'contact' => $settings['contact'] ?? '',
            'email' => $settings['email'] ?? ''
        ]);
        
        return $this->sendEmail($user_data['email'], $subject, $message);
    }
    
    /**
     * Send bulk emails
     */
    public function sendBulkEmail($recipients, $subject, $message) {
        $success_count = 0;
        $failed_count = 0;
        
        foreach ($recipients as $recipient) {
            if ($this->sendEmail($recipient, $subject, $message)) {
                $success_count++;
            } else {
                $failed_count++;
            }
            
            // Add small delay to prevent spam filtering
            usleep(100000); // 0.1 second
        }
        
        return [
            'success' => $success_count,
            'failed' => $failed_count,
            'total' => count($recipients)
        ];
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($template_name, $data) {
        ob_start();
        
        switch ($template_name) {
            case 'welcome':
                include 'email_templates/welcome.php';
                break;
            case 'expiry_reminder':
                include 'email_templates/expiry_reminder.php';
                break;
            case 'payment_receipt':
                include 'email_templates/payment_receipt.php';
                break;
            case 'class_booking':
                include 'email_templates/class_booking.php';
                break;
            case 'password_reset':
                include 'email_templates/password_reset.php';
                break;
            default:
                return $this->getDefaultTemplate($data);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Get default email template
     */
    private function getDefaultTemplate($data) {
        $settings = $this->getGymSettings();
        $gym_name = $settings['gym_name'] ?? 'Gym Management System';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .content { background: #f9f9f9; padding: 30px; }
                .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>$gym_name</h1>
                </div>
                <div class='content'>
                    " . ($data['message'] ?? '') . "
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " $gym_name. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

// Initialize email service
$emailService = new EmailService($conn);
?>
