<?php
require_once '../includes/config.php';
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['member_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

$member_id = (int)$input['member_id'];
$name = $input['name'];
$email = $input['email'];
$expiry_date = $input['expiry_date'];
$days_remaining = (int)$input['days_remaining'];

// Get gym settings
$settings = get_gym_settings();

// Email content
$subject = "Membership Expiry Notice - " . $settings['gym_name'];

$message = "
Dear {$name},

This is a reminder that your membership with {$settings['gym_name']} is expiring soon.

Membership Details:
- Expiry Date: " . date('F j, Y', strtotime($expiry_date)) . "
- Days Remaining: {$days_remaining}

Please renew your membership to continue enjoying our facilities and services.

Contact Information:
" . ($settings['contact'] ? "Phone: {$settings['contact']}\n" : "") . "
" . ($settings['email'] ? "Email: {$settings['email']}\n" : "") . "
" . ($settings['address'] ? "Address: {$settings['address']}\n" : "") . "

We look forward to seeing you continue your fitness journey with us!

Best regards,
{$settings['gym_name']} Team
{$settings['tagline']}
";

// Email headers
$headers = "From: " . ($settings['email'] ?: 'noreply@gym.com') . "\r\n";
$headers .= "Reply-To: " . ($settings['email'] ?: 'noreply@gym.com') . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Send email
if (mail($email, $subject, $message, $headers)) {
    echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send email']);
}
?>