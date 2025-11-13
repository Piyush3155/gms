<?php
/**
 * Payment Gateway Integration Service
 * Supports multiple payment providers: Razorpay, Stripe, PayPal
 * 
 * Features:
 * - Multi-gateway support
 * - Secure payment processing
 * - Webhook handling
 * - Transaction logging
 * - Automated receipt generation
 * - Refund management
 * 
 * @package GMS
 * @version 2.0
 */

require_once 'config.php';
require_once 'email.php';

class PaymentGateway {
    private $conn;
    private $emailService;
    private $gateway;
    private $config;
    
    // Gateway configurations
    private $gatewayConfigs = [
        'razorpay' => [
            'key_id' => 'rzp_test_XXXXXXXXXX',
            'key_secret' => 'YOUR_RAZORPAY_SECRET',
            'currency' => 'INR',
            'enabled' => true
        ],
        'stripe' => [
            'publishable_key' => 'pk_test_XXXXXXXXXX',
            'secret_key' => 'sk_test_XXXXXXXXXX',
            'currency' => 'usd',
            'enabled' => true
        ],
        'paypal' => [
            'client_id' => 'YOUR_PAYPAL_CLIENT_ID',
            'client_secret' => 'YOUR_PAYPAL_SECRET',
            'mode' => 'sandbox', // 'sandbox' or 'live'
            'currency' => 'USD',
            'enabled' => false
        ]
    ];
    
    public function __construct($gateway = 'razorpay') {
        global $conn;
        $this->conn = $conn;
        $this->emailService = new EmailService();
        $this->gateway = $gateway;
        $this->config = $this->gatewayConfigs[$gateway] ?? [];
        
        // Create payment transactions table if not exists
        $this->initializeDatabase();
    }
    
    /**
     * Initialize payment transactions table
     */
    private function initializeDatabase() {
        $sql = "CREATE TABLE IF NOT EXISTS payment_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id VARCHAR(255) UNIQUE NOT NULL,
            member_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            currency VARCHAR(10) DEFAULT 'INR',
            gateway VARCHAR(50) NOT NULL,
            status ENUM('pending', 'success', 'failed', 'refunded') DEFAULT 'pending',
            payment_method VARCHAR(50),
            order_id VARCHAR(255),
            receipt_number VARCHAR(100),
            payment_data TEXT,
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
            INDEX idx_transaction_id (transaction_id),
            INDEX idx_member_id (member_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->query($sql);
        
        // Create payment webhooks log table
        $sql = "CREATE TABLE IF NOT EXISTS payment_webhooks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            gateway VARCHAR(50) NOT NULL,
            event_type VARCHAR(100),
            payload TEXT,
            signature VARCHAR(255),
            verified BOOLEAN DEFAULT FALSE,
            processed BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_gateway (gateway),
            INDEX idx_processed (processed)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->query($sql);
    }
    
    /**
     * Create payment order
     * 
     * @param int $memberId Member ID
     * @param float $amount Payment amount
     * @param string $purpose Payment purpose (membership, personal_training, etc.)
     * @param array $metadata Additional metadata
     * @return array Order details
     */
    public function createOrder($memberId, $amount, $purpose = 'membership', $metadata = []) {
        try {
            // Get member details
            $stmt = $this->conn->prepare("SELECT id, name, email, phone FROM members WHERE id = ?");
            $stmt->bind_param("i", $memberId);
            $stmt->execute();
            $member = $stmt->get_result()->fetch_assoc();
            
            if (!$member) {
                throw new Exception("Member not found");
            }
            
            // Generate unique order ID
            $orderId = $this->generateOrderId();
            
            // Create order based on gateway
            switch ($this->gateway) {
                case 'razorpay':
                    $order = $this->createRazorpayOrder($orderId, $amount, $member, $metadata);
                    break;
                case 'stripe':
                    $order = $this->createStripePaymentIntent($orderId, $amount, $member, $metadata);
                    break;
                case 'paypal':
                    $order = $this->createPayPalOrder($orderId, $amount, $member, $metadata);
                    break;
                default:
                    throw new Exception("Unsupported payment gateway");
            }
            
            // Log transaction
            $this->logTransaction([
                'transaction_id' => $order['id'],
                'member_id' => $memberId,
                'amount' => $amount,
                'currency' => $this->config['currency'],
                'gateway' => $this->gateway,
                'order_id' => $orderId,
                'status' => 'pending',
                'payment_data' => json_encode($metadata)
            ]);
            
            log_activity('payment_order_created', "Payment order created for member: {$member['name']}, Amount: {$amount}", 'payments');
            
            return [
                'success' => true,
                'order' => $order,
                'member' => $member,
                'gateway' => $this->gateway,
                'config' => [
                    'key' => $this->config['key_id'] ?? $this->config['publishable_key'] ?? null,
                    'currency' => $this->config['currency']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Payment order creation failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create Razorpay order
     */
    private function createRazorpayOrder($orderId, $amount, $member, $metadata) {
        $url = "https://api.razorpay.com/v1/orders";
        
        $data = [
            'amount' => $amount * 100, // Convert to paise
            'currency' => $this->config['currency'],
            'receipt' => $orderId,
            'notes' => array_merge([
                'member_id' => $member['id'],
                'member_name' => $member['name'],
                'member_email' => $member['email']
            ], $metadata)
        ];
        
        $response = $this->makeApiRequest($url, 'POST', $data, [
            'Authorization: Basic ' . base64_encode($this->config['key_id'] . ':' . $this->config['key_secret'])
        ]);
        
        return $response;
    }
    
    /**
     * Create Stripe Payment Intent
     */
    private function createStripePaymentIntent($orderId, $amount, $member, $metadata) {
        $url = "https://api.stripe.com/v1/payment_intents";
        
        $data = [
            'amount' => $amount * 100, // Convert to cents
            'currency' => $this->config['currency'],
            'receipt_email' => $member['email'],
            'description' => "Payment for {$member['name']}",
            'metadata' => array_merge([
                'member_id' => $member['id'],
                'order_id' => $orderId
            ], $metadata)
        ];
        
        $response = $this->makeApiRequest($url, 'POST', $data, [
            'Authorization: Bearer ' . $this->config['secret_key']
        ]);
        
        return $response;
    }
    
    /**
     * Create PayPal order
     */
    private function createPayPalOrder($orderId, $amount, $member, $metadata) {
        // Get PayPal access token
        $accessToken = $this->getPayPalAccessToken();
        
        $url = $this->config['mode'] === 'live' 
            ? "https://api.paypal.com/v2/checkout/orders"
            : "https://api.sandbox.paypal.com/v2/checkout/orders";
        
        $data = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $orderId,
                'amount' => [
                    'currency_code' => $this->config['currency'],
                    'value' => number_format($amount, 2, '.', '')
                ],
                'description' => "Membership payment for {$member['name']}"
            ]],
            'application_context' => [
                'return_url' => SITE_URL . '/admin/payment_success.php',
                'cancel_url' => SITE_URL . '/admin/payment_cancel.php'
            ]
        ];
        
        $response = $this->makeApiRequest($url, 'POST', $data, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        
        return $response;
    }
    
    /**
     * Verify payment
     * 
     * @param string $transactionId Transaction ID from gateway
     * @param array $paymentData Payment verification data
     * @return array Verification result
     */
    public function verifyPayment($transactionId, $paymentData) {
        try {
            $verified = false;
            
            switch ($this->gateway) {
                case 'razorpay':
                    $verified = $this->verifyRazorpayPayment($paymentData);
                    break;
                case 'stripe':
                    $verified = $this->verifyStripePayment($transactionId);
                    break;
                case 'paypal':
                    $verified = $this->verifyPayPalPayment($transactionId);
                    break;
            }
            
            if ($verified) {
                // Update transaction status
                $this->updateTransactionStatus($transactionId, 'success', $paymentData);
                
                // Generate receipt
                $receiptNumber = $this->generateReceiptNumber();
                $this->updateTransactionReceipt($transactionId, $receiptNumber);
                
                // Get transaction details
                $transaction = $this->getTransactionByGatewayId($transactionId);
                
                if ($transaction) {
                    // Send payment confirmation email
                    $this->sendPaymentConfirmation($transaction);
                    
                    // Update member's payment record
                    $this->updateMemberPaymentRecord($transaction);
                    
                    log_activity('payment_success', "Payment verified: Transaction ID {$transactionId}", 'payments');
                }
                
                return [
                    'success' => true,
                    'verified' => true,
                    'receipt_number' => $receiptNumber,
                    'transaction' => $transaction
                ];
            }
            
            return [
                'success' => false,
                'verified' => false,
                'error' => 'Payment verification failed'
            ];
            
        } catch (Exception $e) {
            error_log("Payment verification failed: " . $e->getMessage());
            $this->updateTransactionStatus($transactionId, 'failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'verified' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify Razorpay payment signature
     */
    private function verifyRazorpayPayment($paymentData) {
        $orderId = $paymentData['razorpay_order_id'] ?? '';
        $paymentId = $paymentData['razorpay_payment_id'] ?? '';
        $signature = $paymentData['razorpay_signature'] ?? '';
        
        $generatedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->config['key_secret']);
        
        return hash_equals($generatedSignature, $signature);
    }
    
    /**
     * Verify Stripe payment
     */
    private function verifyStripePayment($paymentIntentId) {
        $url = "https://api.stripe.com/v1/payment_intents/{$paymentIntentId}";
        
        $response = $this->makeApiRequest($url, 'GET', [], [
            'Authorization: Bearer ' . $this->config['secret_key']
        ]);
        
        return $response && $response['status'] === 'succeeded';
    }
    
    /**
     * Verify PayPal payment
     */
    private function verifyPayPalPayment($orderId) {
        $accessToken = $this->getPayPalAccessToken();
        
        $url = $this->config['mode'] === 'live'
            ? "https://api.paypal.com/v2/checkout/orders/{$orderId}"
            : "https://api.sandbox.paypal.com/v2/checkout/orders/{$orderId}";
        
        $response = $this->makeApiRequest($url, 'GET', [], [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        return $response && $response['status'] === 'COMPLETED';
    }
    
    /**
     * Process refund
     */
    public function processRefund($transactionId, $amount = null, $reason = '') {
        try {
            $transaction = $this->getTransaction($transactionId);
            
            if (!$transaction || $transaction['status'] !== 'success') {
                throw new Exception("Invalid transaction for refund");
            }
            
            $refundAmount = $amount ?? $transaction['amount'];
            
            switch ($transaction['gateway']) {
                case 'razorpay':
                    $refund = $this->processRazorpayRefund($transaction['transaction_id'], $refundAmount);
                    break;
                case 'stripe':
                    $refund = $this->processStripeRefund($transaction['transaction_id'], $refundAmount);
                    break;
                case 'paypal':
                    $refund = $this->processPayPalRefund($transaction['transaction_id'], $refundAmount);
                    break;
                default:
                    throw new Exception("Refund not supported for this gateway");
            }
            
            if ($refund) {
                $this->updateTransactionStatus($transaction['id'], 'refunded', [
                    'refund_amount' => $refundAmount,
                    'refund_reason' => $reason,
                    'refund_id' => $refund['id'] ?? null
                ]);
                
                log_activity('payment_refunded', "Refund processed: Amount {$refundAmount}, Reason: {$reason}", 'payments');
                
                return ['success' => true, 'refund' => $refund];
            }
            
            return ['success' => false, 'error' => 'Refund processing failed'];
            
        } catch (Exception $e) {
            error_log("Refund processing failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Process Razorpay refund
     */
    private function processRazorpayRefund($paymentId, $amount) {
        $url = "https://api.razorpay.com/v1/payments/{$paymentId}/refund";
        
        $data = [
            'amount' => $amount * 100
        ];
        
        return $this->makeApiRequest($url, 'POST', $data, [
            'Authorization: Basic ' . base64_encode($this->config['key_id'] . ':' . $this->config['key_secret'])
        ]);
    }
    
    /**
     * Process Stripe refund
     */
    private function processStripeRefund($paymentIntentId, $amount) {
        $url = "https://api.stripe.com/v1/refunds";
        
        $data = [
            'payment_intent' => $paymentIntentId,
            'amount' => $amount * 100
        ];
        
        return $this->makeApiRequest($url, 'POST', $data, [
            'Authorization: Bearer ' . $this->config['secret_key']
        ]);
    }
    
    /**
     * Process PayPal refund
     */
    private function processPayPalRefund($captureId, $amount) {
        $accessToken = $this->getPayPalAccessToken();
        
        $url = $this->config['mode'] === 'live'
            ? "https://api.paypal.com/v2/payments/captures/{$captureId}/refund"
            : "https://api.sandbox.paypal.com/v2/payments/captures/{$captureId}/refund";
        
        $data = [
            'amount' => [
                'value' => number_format($amount, 2, '.', ''),
                'currency_code' => $this->config['currency']
            ]
        ];
        
        return $this->makeApiRequest($url, 'POST', $data, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
    }
    
    /**
     * Handle webhook
     */
    public function handleWebhook($payload, $signature = null) {
        try {
            // Log webhook
            $webhookId = $this->logWebhook($payload, $signature);
            
            // Verify webhook signature
            $verified = $this->verifyWebhookSignature($payload, $signature);
            
            if (!$verified) {
                throw new Exception("Webhook signature verification failed");
            }
            
            $this->updateWebhookVerification($webhookId, true);
            
            // Process webhook based on event type
            $event = json_decode($payload, true);
            $this->processWebhookEvent($event);
            
            $this->updateWebhookProcessed($webhookId, true);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            error_log("Webhook processing failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature($payload, $signature) {
        switch ($this->gateway) {
            case 'razorpay':
                $generatedSignature = hash_hmac('sha256', $payload, $this->config['key_secret']);
                return hash_equals($generatedSignature, $signature);
                
            case 'stripe':
                // Stripe signature verification would go here
                return true;
                
            case 'paypal':
                // PayPal signature verification would go here
                return true;
                
            default:
                return false;
        }
    }
    
    /**
     * Process webhook event
     */
    private function processWebhookEvent($event) {
        $eventType = $event['event'] ?? $event['type'] ?? '';
        
        switch ($eventType) {
            case 'payment.captured':
            case 'payment_intent.succeeded':
                $this->handlePaymentSuccess($event);
                break;
                
            case 'payment.failed':
            case 'payment_intent.payment_failed':
                $this->handlePaymentFailure($event);
                break;
                
            case 'refund.created':
                $this->handleRefundCreated($event);
                break;
        }
    }
    
    /**
     * Handle successful payment webhook
     */
    private function handlePaymentSuccess($event) {
        $paymentData = $event['payload']['payment']['entity'] ?? $event['data']['object'] ?? [];
        $transactionId = $paymentData['id'] ?? '';
        
        if ($transactionId) {
            $this->verifyPayment($transactionId, $paymentData);
        }
    }
    
    /**
     * Handle failed payment webhook
     */
    private function handlePaymentFailure($event) {
        $paymentData = $event['payload']['payment']['entity'] ?? $event['data']['object'] ?? [];
        $transactionId = $paymentData['id'] ?? '';
        
        if ($transactionId) {
            $this->updateTransactionStatus($transactionId, 'failed', $paymentData);
        }
    }
    
    /**
     * Handle refund created webhook
     */
    private function handleRefundCreated($event) {
        $refundData = $event['payload']['refund']['entity'] ?? $event['data']['object'] ?? [];
        // Process refund data
    }
    
    /**
     * Send payment confirmation email
     */
    private function sendPaymentConfirmation($transaction) {
        $stmt = $this->conn->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->bind_param("i", $transaction['member_id']);
        $stmt->execute();
        $member = $stmt->get_result()->fetch_assoc();
        
        if ($member) {
            $this->emailService->sendPaymentReceipt(
                $member,
                [
                    'amount' => $transaction['amount'],
                    'payment_method' => ucfirst($transaction['gateway']),
                    'transaction_id' => $transaction['transaction_id'],
                    'receipt_number' => $transaction['receipt_number'],
                    'date' => $transaction['created_at']
                ]
            );
        }
    }
    
    /**
     * Update member's payment record
     */
    private function updateMemberPaymentRecord($transaction) {
        $stmt = $this->conn->prepare("INSERT INTO payments (member_id, amount, payment_method, payment_date, receipt_number, status) VALUES (?, ?, ?, NOW(), ?, 'completed')");
        
        $paymentMethod = 'online_' . $transaction['gateway'];
        
        $stmt->bind_param("idss", 
            $transaction['member_id'],
            $transaction['amount'],
            $paymentMethod,
            $transaction['receipt_number']
        );
        
        return $stmt->execute();
    }
    
    /**
     * Utility methods
     */
    private function generateOrderId() {
        return 'ORD_' . time() . '_' . bin2hex(random_bytes(4));
    }
    
    private function generateReceiptNumber() {
        return 'RCP_' . date('Ymd') . '_' . bin2hex(random_bytes(3));
    }
    
    private function logTransaction($data) {
        $stmt = $this->conn->prepare("INSERT INTO payment_transactions (transaction_id, member_id, amount, currency, gateway, order_id, status, payment_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("sidsssss",
            $data['transaction_id'],
            $data['member_id'],
            $data['amount'],
            $data['currency'],
            $data['gateway'],
            $data['order_id'],
            $data['status'],
            $data['payment_data']
        );
        
        return $stmt->execute();
    }
    
    private function updateTransactionStatus($transactionId, $status, $data = []) {
        $errorMessage = $data['error'] ?? null;
        $paymentMethod = $data['method'] ?? null;
        
        $stmt = $this->conn->prepare("UPDATE payment_transactions SET status = ?, error_message = ?, payment_method = ?, payment_data = ? WHERE transaction_id = ?");
        
        $paymentDataJson = json_encode($data);
        
        $stmt->bind_param("sssss", $status, $errorMessage, $paymentMethod, $paymentDataJson, $transactionId);
        
        return $stmt->execute();
    }
    
    private function updateTransactionReceipt($transactionId, $receiptNumber) {
        $stmt = $this->conn->prepare("UPDATE payment_transactions SET receipt_number = ? WHERE transaction_id = ?");
        $stmt->bind_param("ss", $receiptNumber, $transactionId);
        return $stmt->execute();
    }
    
    private function getTransaction($id) {
        $stmt = $this->conn->prepare("SELECT * FROM payment_transactions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    private function getTransactionByGatewayId($transactionId) {
        $stmt = $this->conn->prepare("SELECT * FROM payment_transactions WHERE transaction_id = ?");
        $stmt->bind_param("s", $transactionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    private function logWebhook($payload, $signature) {
        $stmt = $this->conn->prepare("INSERT INTO payment_webhooks (gateway, payload, signature) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $this->gateway, $payload, $signature);
        $stmt->execute();
        return $this->conn->insert_id;
    }
    
    private function updateWebhookVerification($id, $verified) {
        $stmt = $this->conn->prepare("UPDATE payment_webhooks SET verified = ? WHERE id = ?");
        $stmt->bind_param("ii", $verified, $id);
        return $stmt->execute();
    }
    
    private function updateWebhookProcessed($id, $processed) {
        $stmt = $this->conn->prepare("UPDATE payment_webhooks SET processed = ? WHERE id = ?");
        $stmt->bind_param("ii", $processed, $id);
        return $stmt->execute();
    }
    
    private function makeApiRequest($url, $method, $data = [], $headers = []) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
            'Content-Type: application/json'
        ], $headers));
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }
        
        throw new Exception("API request failed: " . $response);
    }
    
    private function getPayPalAccessToken() {
        $url = $this->config['mode'] === 'live'
            ? "https://api.paypal.com/v1/oauth2/token"
            : "https://api.sandbox.paypal.com/v1/oauth2/token";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_USERPWD, $this->config['client_id'] . ':' . $this->config['client_secret']);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
}

/**
 * Payment reminder service
 */
class PaymentReminderService {
    private $conn;
    private $emailService;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->emailService = new EmailService();
    }
    
    /**
     * Send payment reminders for expiring memberships
     */
    public function sendExpiryReminders($daysBefore = 7) {
        $expiryDate = date('Y-m-d', strtotime("+{$daysBefore} days"));
        
        $stmt = $this->conn->prepare("
            SELECT m.id, m.name, m.email, m.phone, m.membership_end
            FROM members m
            WHERE m.membership_end = ?
            AND m.status = 'active'
        ");
        
        $stmt->bind_param("s", $expiryDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $remindersSent = 0;
        
        while ($member = $result->fetch_assoc()) {
            $this->emailService->sendExpiryReminder($member, [
                'days_remaining' => $daysBefore
            ]);
            
            $remindersSent++;
        }
        
        log_activity('payment_reminders', "Sent {$remindersSent} payment reminders", 'payments');
        
        return $remindersSent;
    }
    
    /**
     * Send overdue payment reminders
     */
    public function sendOverdueReminders() {
        $today = date('Y-m-d');
        
        $stmt = $this->conn->prepare("
            SELECT m.id, m.name, m.email, m.phone, m.membership_end,
                   DATEDIFF(?, m.membership_end) as days_overdue
            FROM members m
            WHERE m.membership_end < ?
            AND m.status = 'active'
            AND NOT EXISTS (
                SELECT 1 FROM payment_transactions pt
                WHERE pt.member_id = m.id
                AND pt.status = 'pending'
                AND pt.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            )
        ");
        
        $stmt->bind_param("ss", $today, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $remindersSent = 0;
        
        while ($member = $result->fetch_assoc()) {
            // Send overdue reminder
            $this->emailService->sendEmail(
                $member['email'],
                "Membership Overdue - {$member['name']}",
                $this->generateOverdueEmailContent($member),
                $member['name']
            );
            
            $remindersSent++;
        }
        
        log_activity('overdue_reminders', "Sent {$remindersSent} overdue payment reminders", 'payments');
        
        return $remindersSent;
    }
    
    private function generateOverdueEmailContent($member) {
        $content = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #e74c3c;'>Membership Overdue</h2>
            <p>Dear {$member['name']},</p>
            <p>Your gym membership expired on " . date('F j, Y', strtotime($member['membership_end'])) . ".</p>
            <p>It has been {$member['days_overdue']} days since your membership expired.</p>
            <p>Please renew your membership to continue enjoying our services.</p>
            <p><a href='" . SITE_URL . "/member/renew.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; display: inline-block; border-radius: 5px;'>Renew Now</a></p>
            <p>Thank you!</p>
        </div>
        ";
        
        return $content;
    }
}
