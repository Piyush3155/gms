<?php
/**
 * Automated Payment Reminder Script
 * Send payment reminders for expiring and overdue memberships
 * 
 * Usage: Run this script via cron job daily
 * Example cron: 0 9 * * * php /path/to/send_payment_reminders.php
 */

require_once '../includes/config.php';
require_once '../includes/payment_gateway.php';

// Ensure script is run from CLI or authenticated admin
if (php_sapi_name() !== 'cli') {
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        die('Unauthorized access');
    }
}

echo "=== Payment Reminder Script Started ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

$reminderService = new PaymentReminderService();

// Send 7-day expiry reminders
echo "Sending 7-day expiry reminders...\n";
$count7days = $reminderService->sendExpiryReminders(7);
echo "Sent {$count7days} reminders for memberships expiring in 7 days\n\n";

// Send 3-day expiry reminders
echo "Sending 3-day expiry reminders...\n";
$count3days = $reminderService->sendExpiryReminders(3);
echo "Sent {$count3days} reminders for memberships expiring in 3 days\n\n";

// Send 1-day expiry reminders
echo "Sending 1-day expiry reminders...\n";
$count1day = $reminderService->sendExpiryReminders(1);
echo "Sent {$count1day} reminders for memberships expiring tomorrow\n\n";

// Send overdue payment reminders
echo "Sending overdue payment reminders...\n";
$countOverdue = $reminderService->sendOverdueReminders();
echo "Sent {$countOverdue} overdue payment reminders\n\n";

$totalReminders = $count7days + $count3days + $count1day + $countOverdue;

echo "=== Payment Reminder Script Completed ===\n";
echo "Total reminders sent: {$totalReminders}\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";

// Log activity
log_activity('payment_reminders_automated', "Automated payment reminders sent: {$totalReminders} total", 'system');

// If run from web interface, show results
if (php_sapi_name() !== 'cli') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Reminders - <?php echo SITE_NAME; ?></title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css?v=1.0">
    </head>
    <body>
        <div class="container mt-5">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-envelope"></i> Payment Reminders Sent</h4>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            7-Day Expiry Reminders
                            <span class="badge bg-primary rounded-pill"><?php echo $count7days; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            3-Day Expiry Reminders
                            <span class="badge bg-primary rounded-pill"><?php echo $count3days; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            1-Day Expiry Reminders
                            <span class="badge bg-primary rounded-pill"><?php echo $count1day; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Overdue Payment Reminders
                            <span class="badge bg-danger rounded-pill"><?php echo $countOverdue; ?></span>
                        </li>
                    </ul>
                    
                    <div class="mt-4">
                        <h5>Total Reminders Sent: <strong><?php echo $totalReminders; ?></strong></h5>
                    </div>
                    
                    <div class="mt-3">
                        <a href="payments.php" class="btn btn-primary">Back to Payments</a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
