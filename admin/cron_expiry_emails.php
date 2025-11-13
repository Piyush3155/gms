<?php
/**
 * Automated Membership Expiry Email Cron Job
 * Run daily at 9 AM
 * 
 * Add to crontab:
 * 0 9 * * * /usr/bin/php /var/www/gms/admin/cron_expiry_emails.php
 */

// Prevent direct browser access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/email.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting expiry email notifications...\n";

try {
    $emailService = new EmailService();
    
    // Get members expiring in 7 days (critical)
    $critical_members = $conn->query("
        SELECT m.id, m.name, m.email,
               DATE_ADD(COALESCE(lp.payment_date, m.join_date), INTERVAL p.duration_months MONTH) as expiry_date,
               DATEDIFF(DATE_ADD(COALESCE(lp.payment_date, m.join_date), INTERVAL p.duration_months MONTH), CURDATE()) as days_remaining
        FROM members m
        JOIN plans p ON m.plan_id = p.id
        LEFT JOIN (
            SELECT member_id, MAX(payment_date) as payment_date
            FROM payments WHERE status = 'paid' GROUP BY member_id
        ) lp ON m.id = lp.member_id
        WHERE m.status = 'active'
        AND DATEDIFF(DATE_ADD(COALESCE(lp.payment_date, m.join_date), INTERVAL p.duration_months MONTH), CURDATE()) = 7
    ");
    
    $critical_sent = 0;
    while ($member = $critical_members->fetch_assoc()) {
        $emailData = [
            'to' => $member['email'],
            'to_name' => $member['name'],
            'subject' => 'URGENT: Membership Expires in 7 Days',
            'template' => 'membership_expiry',
            'variables' => [
                'member_name' => $member['name'],
                'expiry_date' => date('F j, Y', strtotime($member['expiry_date'])),
                'days_remaining' => $member['days_remaining'],
                'urgency' => 'CRITICAL'
            ]
        ];
        
        if ($emailService->sendEmail($emailData)) {
            $critical_sent++;
            echo "[" . date('Y-m-d H:i:s') . "] Sent critical alert to: " . $member['email'] . "\n";
        }
    }
    
    // Get members expiring in 30 days (warning)
    $warning_members = $conn->query("
        SELECT m.id, m.name, m.email,
               DATE_ADD(COALESCE(lp.payment_date, m.join_date), INTERVAL p.duration_months MONTH) as expiry_date,
               DATEDIFF(DATE_ADD(COALESCE(lp.payment_date, m.join_date), INTERVAL p.duration_months MONTH), CURDATE()) as days_remaining
        FROM members m
        JOIN plans p ON m.plan_id = p.id
        LEFT JOIN (
            SELECT member_id, MAX(payment_date) as payment_date
            FROM payments WHERE status = 'paid' GROUP BY member_id
        ) lp ON m.id = lp.member_id
        WHERE m.status = 'active'
        AND DATEDIFF(DATE_ADD(COALESCE(lp.payment_date, m.join_date), INTERVAL p.duration_months MONTH), CURDATE()) = 30
    ");
    
    $warning_sent = 0;
    while ($member = $warning_members->fetch_assoc()) {
        $emailData = [
            'to' => $member['email'],
            'to_name' => $member['name'],
            'subject' => 'Membership Renewal Reminder',
            'template' => 'membership_expiry',
            'variables' => [
                'member_name' => $member['name'],
                'expiry_date' => date('F j, Y', strtotime($member['expiry_date'])),
                'days_remaining' => $member['days_remaining'],
                'urgency' => 'WARNING'
            ]
        ];
        
        if ($emailService->sendEmail($emailData)) {
            $warning_sent++;
            echo "[" . date('Y-m-d H:i:s') . "] Sent warning to: " . $member['email'] . "\n";
        }
    }
    
    // Get expired members (today)
    $expired_members = $conn->query("
        SELECT m.id, m.name, m.email,
               DATE_ADD(COALESCE(lp.payment_date, m.join_date), INTERVAL p.duration_months MONTH) as expiry_date
        FROM members m
        JOIN plans p ON m.plan_id = p.id
        LEFT JOIN (
            SELECT member_id, MAX(payment_date) as payment_date
            FROM payments WHERE status = 'paid' GROUP BY member_id
        ) lp ON m.id = lp.member_id
        WHERE m.status = 'active'
        AND DATE(DATE_ADD(COALESCE(lp.payment_date, m.join_date), INTERVAL p.duration_months MONTH)) = CURDATE()
    ");
    
    $expired_sent = 0;
    while ($member = $expired_members->fetch_assoc()) {
        // Mark member as expired
        $conn->query("UPDATE members SET status = 'expired' WHERE id = " . $member['id']);
        
        $emailData = [
            'to' => $member['email'],
            'to_name' => $member['name'],
            'subject' => 'Membership Expired - Please Renew',
            'template' => 'membership_expired',
            'variables' => [
                'member_name' => $member['name'],
                'expiry_date' => date('F j, Y', strtotime($member['expiry_date']))
            ]
        ];
        
        if ($emailService->sendEmail($emailData)) {
            $expired_sent++;
            echo "[" . date('Y-m-d H:i:s') . "] Sent expiry notice to: " . $member['email'] . "\n";
        }
    }
    
    echo "\n[" . date('Y-m-d H:i:s') . "] Email Summary:\n";
    echo "  - Critical alerts (7 days): $critical_sent\n";
    echo "  - Warnings (30 days): $warning_sent\n";
    echo "  - Expired today: $expired_sent\n";
    echo "  - Total emails sent: " . ($critical_sent + $warning_sent + $expired_sent) . "\n";
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Expiry email process completed\n";
?>
