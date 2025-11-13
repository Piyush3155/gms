<?php
require_once '../includes/config.php';
require_role('admin');

// Handle AJAX requests for sending emails
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['action'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    $settings = get_gym_settings();
    
    // Send single email
    if ($input['action'] === 'send_single') {
        $member_id = (int)$input['member_id'];
        $name = $input['name'];
        $email = $input['email'];
        $expiry_date = $input['expiry_date'];
        $days_remaining = (int)$input['days_remaining'];
        
        $subject = "Membership Expiry Notice - " . $settings['gym_name'];
        $message = "Dear {$name},\n\nThis is a reminder that your membership with {$settings['gym_name']} is expiring soon.\n\nMembership Details:\n- Expiry Date: " . date('F j, Y', strtotime($expiry_date)) . "\n- Days Remaining: {$days_remaining}\n\nPlease renew your membership to continue enjoying our facilities.\n\nContact: " . ($settings['contact'] ?: 'N/A') . "\nEmail: " . ($settings['email'] ?: 'N/A') . "\n\nBest regards,\n{$settings['gym_name']} Team";
        
        $headers = "From: " . ($settings['email'] ?: 'noreply@gym.com') . "\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        
        if (mail($email, $subject, $message, $headers)) {
            echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send email']);
        }
        exit;
    }
    
    // Send bulk emails
    if ($input['action'] === 'send_bulk') {
        $member_ids = array_map('intval', $input['member_ids']);
        
        $placeholders = str_repeat('?,', count($member_ids) - 1) . '?';
        $stmt = $conn->prepare("
            SELECT m.id, m.name, m.email,
                   DATE_ADD(COALESCE(lp.payment_date, m.join_date), INTERVAL p.duration_months MONTH) as expiry_date,
                   DATEDIFF(DATE_ADD(COALESCE(lp.payment_date, m.join_date), INTERVAL p.duration_months MONTH), CURDATE()) as days_remaining
            FROM members m
            JOIN plans p ON m.plan_id = p.id
            LEFT JOIN (SELECT member_id, MAX(payment_date) as payment_date FROM payments WHERE status = 'paid' GROUP BY member_id) lp ON m.id = lp.member_id
            WHERE m.id IN ($placeholders)
        ");
        
        $stmt->bind_param(str_repeat('i', count($member_ids)), ...$member_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sent_count = 0;
        while ($member = $result->fetch_assoc()) {
            $subject = "Membership Expiry Notice - " . $settings['gym_name'];
            $message = "Dear {$member['name']},\n\nThis is a reminder that your membership with {$settings['gym_name']} is expiring soon.\n\nMembership Details:\n- Expiry Date: " . date('F j, Y', strtotime($member['expiry_date'])) . "\n- Days Remaining: {$member['days_remaining']}\n\nPlease renew your membership to continue enjoying our facilities.\n\nContact: " . ($settings['contact'] ?: 'N/A') . "\nEmail: " . ($settings['email'] ?: 'N/A') . "\n\nBest regards,\n{$settings['gym_name']} Team";
            
            $headers = "From: " . ($settings['email'] ?: 'noreply@gym.com') . "\r\nContent-Type: text/plain; charset=UTF-8\r\n";
            
            if (mail($member['email'], $subject, $message, $headers)) {
                $sent_count++;
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Bulk emails sent', 'sent_count' => $sent_count]);
        exit;
    }
}

// Get expiry alert days from URL parameter or default to 30 days
$alert_days = isset($_GET['days']) ? (int)$_GET['days'] : 30;

// Get members with expiring memberships
$expiring_members = $conn->query("
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
    AND DATEDIFF(DATE_ADD(COALESCE(latest_payment.payment_date, m.join_date), INTERVAL p.duration_months MONTH), CURDATE()) BETWEEN 0 AND $alert_days
    ORDER BY days_remaining ASC
");

// Get summary stats
$critical_count = $conn->query("
    SELECT COUNT(*) as count FROM (
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
    ) t WHERE days_remaining <= 7
")->fetch_assoc()['count'];

$warning_count = $conn->query("
    SELECT COUNT(*) as count FROM (
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
    ) t WHERE days_remaining BETWEEN 8 AND 30
")->fetch_assoc()['count'];

$total_expiring = $critical_count + $warning_count;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Expiry Emails - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css?v=1.0">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-envelope me-2"></i>Send Expiry Emails</h2>
            <div class="d-flex gap-2">
                <select class="form-select" onchange="changeAlertDays(this.value)" style="width: auto;">
                    <option value="7" <?php echo $alert_days == 7 ? 'selected' : ''; ?>>Next 7 days</option>
                    <option value="30" <?php echo $alert_days == 30 ? 'selected' : ''; ?>>Next 30 days</option>
                    <option value="60" <?php echo $alert_days == 60 ? 'selected' : ''; ?>>Next 60 days</option>
                    <option value="90" <?php echo $alert_days == 90 ? 'selected' : ''; ?>>Next 90 days</option>
                </select>
                <button class="btn btn-success" onclick="sendBulkEmails()">
                    <i class="fas fa-paper-plane me-1"></i>Send All Emails
                </button>
            </div>
        </div>

        <!-- Alert Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-exclamation-circle me-2"></i>Critical (≤7 days)</h5>
                        <h2><?php echo $critical_count; ?></h2>
                        <small>Expire very soon</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white" style="background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-clock me-2"></i>Warning (8-30 days)</h5>
                        <h2><?php echo $warning_count; ?></h2>
                        <small>Expire soon</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white" style="background: linear-gradient(135deg, #42a5f5 0%, #1976d2 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-calendar-alt me-2"></i>Total Expiring</h5>
                        <h2><?php echo $total_expiring; ?></h2>
                        <small>Within <?php echo $alert_days; ?> days</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expiring Members Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Members with Expiring Memberships</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="expiry-emails-table" class="table table-striped table-no-bordered table-hover" cellspacing="0" width="100%" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th data-sortable="false" data-exportable="false"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Plan</th>
                                <th>Expiry Date</th>
                                <th>Days Remaining</th>
                                <th>Status</th>
                                <th data-sortable="false" data-exportable="false">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($member = $expiring_members->fetch_assoc()):
                                $days_remaining = $member['days_remaining'];
                                $status_class = $days_remaining <= 7 ? 'danger' : ($days_remaining <= 30 ? 'warning' : 'info');
                                $status_text = $days_remaining <= 7 ? 'Critical' : ($days_remaining <= 30 ? 'Warning' : 'Info');
                            ?>
                                <tr>
                                    <td><input type="checkbox" class="member-checkbox" value="<?php echo $member['id']; ?>"></td>
                                    <td><?php echo htmlspecialchars($member['name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td><?php echo htmlspecialchars($member['contact']); ?></td>
                                    <td><?php echo htmlspecialchars($member['plan_name']); ?> (₹<?php echo number_format($member['amount'], 2); ?>)</td>
                                    <td><?php echo date('M j, Y', strtotime($member['expiry_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo $days_remaining; ?> days
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="sendSingleEmail(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['email']); ?>', '<?php echo htmlspecialchars($member['name']); ?>', '<?php echo $member['expiry_date']; ?>', <?php echo $days_remaining; ?>)">
                                            <i class="fas fa-envelope"></i> Send Email
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Include libraries for export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script src="../assets/js/enhanced.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('expiry-emails-table');
            if (table) {
                new DataTable(table, {
                    searchable: true,
                    pagination: true,
                    sortable: true,
                    exportable: true,
                    exportOptions: {
                        fileName: 'Membership_Expiry_Emails'
                    }
                });
            }
        });

        function changeAlertDays(days) {
            window.location.href = `send_expiry_emails.php?days=${days}`;
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.member-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        }

        async function sendSingleEmail(memberId, email, name, expiryDate, daysRemaining) {
            if (!confirm(`Send expiry email to ${name} (${email})?`)) return;
            
            try {
                const response = await fetch('send_expiry_emails.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'send_single',
                        member_id: memberId,
                        email: email,
                        name: name,
                        expiry_date: expiryDate,
                        days_remaining: daysRemaining
                    })
                });
                
                const data = await response.json();
                alert(data.success ? 'Email sent successfully!' : 'Failed: ' + data.message);
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        async function sendBulkEmails() {
            const selectedCheckboxes = document.querySelectorAll('.member-checkbox:checked');
            if (selectedCheckboxes.length === 0) {
                alert('Please select members to send emails to.');
                return;
            }

            if (!confirm(`Send expiry emails to ${selectedCheckboxes.length} selected members?`)) return;

            const memberIds = Array.from(selectedCheckboxes).map(cb => cb.value);
            
            try {
                const response = await fetch('send_expiry_emails.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'send_bulk',
                        member_ids: memberIds
                    })
                });
                
                const data = await response.json();
                alert(data.success ? `Success! ${data.sent_count} emails sent.` : 'Failed: ' + data.message);
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
    </script>
        </div>
    </div>
    </div>
</body>
</html>
