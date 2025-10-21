<?php
require_once '../includes/config.php';
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['member_ids']) || !is_array($input['member_ids'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

$member_ids = array_map('intval', $input['member_ids']);

// Get member details for the selected IDs
$placeholders = str_repeat('?,', count($member_ids) - 1) . '?';
$stmt = $conn->prepare("
    SELECT
        m.id,
        m.name,
        m.email,
        DATE_ADD(COALESCE(lp.payment_date, m.join_date), INTERVAL p.duration_months MONTH) as expiry_date,
        DATEDIFF(DATE_ADD(COALESCE(lp.payment_date, m.join_date), INTERVAL p.duration_months MONTH), CURDATE()) as days_remaining
    FROM members m
    JOIN plans p ON m.plan_id = p.id
    LEFT JOIN (
        SELECT member_id, MAX(payment_date) as payment_date
        FROM payments
        WHERE status = 'paid'
        GROUP BY member_id
    ) lp ON m.id = lp.member_id
    WHERE m.id IN ($placeholders)
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param(str_repeat('i', count($member_ids)), ...$member_ids);
$stmt->execute();
$result = $stmt->get_result();

// Get gym settings
$settings = get_gym_settings();

$sent_count = 0;
$errors = [];

while ($member = $result->fetch_assoc()) {
    $name = $member['name'];
    $email = $member['email'];
    $expiry_date = $member['expiry_date'];
    $days_remaining = $member['days_remaining'];

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
        $sent_count++;
    } else {
        $errors[] = "Failed to send email to {$email}";
    }
}

$stmt->close();

if ($sent_count > 0) {
    echo json_encode([
        'success' => true,
        'message' => 'Bulk emails sent successfully',
        'sent_count' => $sent_count,
        'errors' => $errors
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send any emails',
        'errors' => $errors
    ]);
}
?>