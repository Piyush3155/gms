<?php
/**
 * Payment Gateway Webhook Handler
 * Handles webhooks from Razorpay, Stripe, and PayPal
 */

require_once '../includes/config.php';
require_once '../includes/payment_gateway.php';

// Get webhook payload
$payload = file_get_contents('php://input');
$headers = getallheaders();

// Determine gateway based on headers or URL parameter
$gateway = $_GET['gateway'] ?? null;

if (!$gateway) {
    // Try to detect gateway from headers
    if (isset($headers['X-Razorpay-Signature'])) {
        $gateway = 'razorpay';
    } elseif (isset($headers['Stripe-Signature'])) {
        $gateway = 'stripe';
    } elseif (isset($headers['PAYPAL-TRANSMISSION-SIG'])) {
        $gateway = 'paypal';
    }
}

if (!$gateway) {
    http_response_code(400);
    echo json_encode(['error' => 'Unable to determine payment gateway']);
    exit;
}

// Get signature from headers
$signature = null;

switch ($gateway) {
    case 'razorpay':
        $signature = $headers['X-Razorpay-Signature'] ?? null;
        break;
    case 'stripe':
        $signature = $headers['Stripe-Signature'] ?? null;
        break;
    case 'paypal':
        $signature = $headers['PAYPAL-TRANSMISSION-SIG'] ?? null;
        break;
}

// Process webhook
$paymentGateway = new PaymentGateway($gateway);
$result = $paymentGateway->handleWebhook($payload, $signature);

if ($result['success']) {
    http_response_code(200);
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(400);
    echo json_encode(['error' => $result['error'] ?? 'Webhook processing failed']);
}

log_activity('webhook_received', "Payment webhook received from {$gateway}", 'payments');
