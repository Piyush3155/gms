<?php
// Test script for expiry_alerts.php logic
// Mock session for admin role
$_SESSION['user_role'] = 'admin';
$_SESSION['user_id'] = 1; // Mock user ID

require_once 'C:\wamp64\www\gms\includes\config.php';

// Copy the logic from expiry_alerts.php
// Get expiry alert days from URL parameter or default to 30 days
$alert_days = isset($_GET['days']) ? (int)$_GET['days'] : 30;

// Get members with expiring memberships using prepared statement
$sql = "
    SELECT
        m.id,
        m.name,
        m.contact,
        m.email,
        m.join_date,
        p.name as plan_name,
        p.duration_months,
        p.amount,
        COALESCE(latest_payment.payment_date, m.join_date) as start_date,
        DATE_ADD(COALESCE(latest_payment.payment_date, m.join_date), INTERVAL p.duration_months MONTH) as expiry_date,
        DATEDIFF(DATE_ADD(COALESCE(latest_payment.payment_date, m.join_date), INTERVAL p.duration_months MONTH), CURDATE()) as days_remaining
    FROM members m
    JOIN plans p ON m.plan_id = p.id
    LEFT JOIN (
        SELECT member_id, MAX(payment_date) as payment_date
        FROM payments
        WHERE status = 'paid'
        GROUP BY member_id
    ) latest_payment ON m.id = latest_payment.member_id
    WHERE m.status = 'active'
    AND DATEDIFF(DATE_ADD(COALESCE(latest_payment.payment_date, m.join_date), INTERVAL p.duration_months MONTH), CURDATE()) <= ?
    ORDER BY days_remaining ASC
";
$stmt = $conn->prepare($sql);
if (!$stmt) { die('Prepare failed: ' . $conn->error); }
$stmt->bind_param('i', $alert_days);
$stmt->execute();
$expiring_members = $stmt->get_result();

// Get summary stats using prepared statements
// Critical: <= 7 days
$critical_sql = "
    SELECT COUNT(*) as count
    FROM (
        SELECT m.id,
               DATEDIFF(DATE_ADD(COALESCE(lp.payment_date, m.join_date), INTERVAL p.duration_months MONTH), CURDATE()) as days_remaining
        FROM members m
        JOIN plans p ON m.plan_id = p.id
        LEFT JOIN (
            SELECT member_id, MAX(payment_date) as payment_date
            FROM payments
            WHERE status = 'paid'
            GROUP BY member_id
        ) lp ON m.id = lp.member_id
        WHERE m.status = 'active'
    ) t
    WHERE days_remaining <= 7
";
$critical_stmt = $conn->prepare($critical_sql);
if (!$critical_stmt) { die('Prepare failed: ' . $conn->error); }
$critical_stmt->execute();
$critical_result = $critical_stmt->get_result();
$critical_row = $critical_result->fetch_assoc();
$critical_count = (int)$critical_row['count'];

// Warning: 8 - 30 days
$warning_sql = "
    SELECT COUNT(*) as count
    FROM (
        SELECT m.id,
               DATEDIFF(DATE_ADD(COALESCE(lp.payment_date, m.join_date), INTERVAL p.duration_months MONTH), CURDATE()) as days_remaining
        FROM members m
        JOIN plans p ON m.plan_id = p.id
        LEFT JOIN (
            SELECT member_id, MAX(payment_date) as payment_date
            FROM payments
            WHERE status = 'paid'
            GROUP BY member_id
        ) lp ON m.id = lp.member_id
        WHERE m.status = 'active'
    ) t
    WHERE days_remaining BETWEEN 8 AND 30
";
$warning_stmt = $conn->prepare($warning_sql);
if (!$warning_stmt) { die('Prepare failed: ' . $conn->error); }
$warning_stmt->execute();
$warning_result = $warning_stmt->get_result();
$warning_row = $warning_result->fetch_assoc();
$warning_count = (int)$warning_row['count'];

$total_expiring = $critical_count + $warning_count;

// Output test results
echo "Test Results:\n";
echo "alert_days: $alert_days\n";
echo "critical_count: $critical_count\n";
echo "warning_count: $warning_count\n";
echo "total_expiring: $total_expiring\n";
echo "expiring_members rows: " . $expiring_members->num_rows . "\n";

// Test fetch one row
if ($member = $expiring_members->fetch_assoc()) {
    echo "Sample member: " . $member['name'] . " - " . $member['days_remaining'] . " days\n";
} else {
    echo "No expiring members found.\n";
}

echo "Test completed successfully.\n";
?>