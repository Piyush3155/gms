<?php
/**
 * SMS Management Interface
 * Send individual or bulk SMS to members
 */

require_once '../includes/config.php';
require_once '../includes/sms_service.php';

require_permission('members', 'edit');

$message = '';
$error = '';

$smsService = new SMSService();

// Handle single SMS send
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_single_sms'])) {
    $memberId = intval($_POST['member_id']);
    $messageText = trim($_POST['message']);
    
    // Get member details
    $stmt = $conn->prepare("SELECT id, name, phone FROM members WHERE id = ?");
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();
    
    if ($member && $member['phone']) {
        $result = $smsService->sendSMS($member['phone'], $messageText, $member['id']);
        
        if ($result['success']) {
            $message = "SMS sent successfully to {$member['name']}";
            log_activity('sms_sent_manual', "SMS sent to {$member['name']}", 'sms');
        } else {
            $error = $result['error'] ?? 'Failed to send SMS';
        }
    } else {
        $error = "Member not found or phone number missing";
    }
}

// Handle templated SMS send
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_template_sms'])) {
    $memberId = intval($_POST['member_id']);
    $templateName = $_POST['template'];
    $variables = json_decode($_POST['variables'] ?? '{}', true);
    
    $stmt = $conn->prepare("SELECT id, name, phone FROM members WHERE id = ?");
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();
    
    if ($member && $member['phone']) {
        $result = $smsService->sendTemplatedSMS($templateName, $member, $variables);
        
        if ($result['success']) {
            $message = "Templated SMS sent successfully to {$member['name']}";
        } else {
            $error = $result['error'] ?? 'Failed to send SMS';
        }
    } else {
        $error = "Member not found or phone number missing";
    }
}

// Handle bulk SMS send
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_bulk_sms'])) {
    $recipientType = $_POST['recipient_type'];
    $messageText = trim($_POST['bulk_message']);
    
    $recipients = [];
    
    // Get recipients based on type
    switch ($recipientType) {
        case 'all_active':
            $stmt = $conn->query("SELECT id, name, phone FROM members WHERE status = 'active' AND phone IS NOT NULL AND phone != ''");
            break;
        case 'expiring_soon':
            $stmt = $conn->query("SELECT id, name, phone FROM members WHERE status = 'active' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND phone IS NOT NULL AND phone != ''");
            break;
        case 'inactive':
            $stmt = $conn->query("SELECT id, name, phone FROM members WHERE status = 'inactive' AND phone IS NOT NULL AND phone != ''");
            break;
        case 'custom':
            $memberIds = explode(',', $_POST['custom_member_ids']);
            $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
            $stmt = $conn->prepare("SELECT id, name, phone FROM members WHERE id IN ($placeholders) AND phone IS NOT NULL AND phone != ''");
            $stmt->bind_param(str_repeat('i', count($memberIds)), ...$memberIds);
            $stmt->execute();
            $stmt = $stmt->get_result();
            break;
        default:
            $stmt = null;
    }
    
    if ($stmt) {
        while ($member = $stmt->fetch_assoc()) {
            $recipients[] = [
                'phone' => $member['phone'],
                'message' => $messageText,
                'member_id' => $member['id']
            ];
        }
        
        $result = $smsService->sendBulkSMS($recipients);
        
        $message = "Bulk SMS completed: {$result['success']} sent, {$result['failed']} failed out of {$result['total']} total";
        log_activity('bulk_sms_sent_manual', "Bulk SMS: {$result['success']} sent", 'sms');
    } else {
        $error = "Invalid recipient type";
    }
}

// Handle automated reminder triggers
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_attendance_reminders'])) {
    $count = $smsService->sendAttendanceReminders();
    $message = "Sent {$count} attendance reminders via SMS";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_payment_reminders'])) {
    $days = intval($_POST['days_before']);
    $count = $smsService->sendPaymentReminders($days);
    $message = "Sent {$count} payment reminders via SMS";
}

// Get all members for dropdown
$members = $conn->query("SELECT id, name, phone FROM members WHERE phone IS NOT NULL AND phone != '' ORDER BY name ASC");

// Get SMS statistics
$stats = $smsService->getSMSStats();

// Get recent SMS log
$recentSMS = $conn->query("
    SELECT sl.*, m.name as member_name
    FROM sms_log sl
    LEFT JOIN members m ON sl.member_id = m.id
    ORDER BY sl.sent_at DESC
    LIMIT 20
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css?v=1.0">
    
    <style>
        .sms-template-card {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .sms-template-card:hover {
            border-color: #3b82f6;
            background: #f0f9ff;
        }
        
        .char-counter {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .char-counter.warning {
            color: #f59e0b;
        }
        
        .char-counter.danger {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- SMS Statistics -->
            <div class="col-lg-12 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>SMS Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            $totalSent = 0;
                            $totalSuccess = 0;
                            $totalFailed = 0;
                            
                            foreach ($stats as $stat) {
                                $totalSent += $stat['total_sent'];
                                $totalSuccess += $stat['successful'];
                                $totalFailed += $stat['failed'];
                            }
                            ?>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h3 class="mb-0"><?php echo $totalSent; ?></h3>
                                        <small class="text-muted">Total SMS Sent</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h3 class="mb-0"><?php echo $totalSuccess; ?></h3>
                                        <small>Successful</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h3 class="mb-0"><?php echo $totalFailed; ?></h3>
                                        <small>Failed</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h3 class="mb-0"><?php echo $totalSuccess > 0 ? round(($totalSuccess / $totalSent) * 100, 1) : 0; ?>%</h3>
                                        <small>Success Rate</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Send Single SMS -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-sms me-2"></i>Send Single SMS</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="singleSMSForm">
                            <input type="hidden" name="send_single_sms" value="1">
                            
                            <div class="mb-3">
                                <label for="member_id" class="form-label">Select Member *</label>
                                <select class="form-select" name="member_id" required>
                                    <option value="">Choose member...</option>
                                    <?php
                                    $members->data_seek(0);
                                    while ($member = $members->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $member['id']; ?>">
                                            <?php echo htmlspecialchars($member['name']); ?> - <?php echo htmlspecialchars($member['phone']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">Message *</label>
                                <textarea class="form-control sms-message" name="message" rows="4" maxlength="160" required></textarea>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">Max 160 characters</small>
                                    <span class="char-counter">0 / 160</span>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-paper-plane me-2"></i>Send SMS
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Send Bulk SMS -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Send Bulk SMS</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="bulkSMSForm">
                            <input type="hidden" name="send_bulk_sms" value="1">
                            
                            <div class="mb-3">
                                <label for="recipient_type" class="form-label">Recipients *</label>
                                <select class="form-select" name="recipient_type" id="recipient_type" required>
                                    <option value="all_active">All Active Members</option>
                                    <option value="expiring_soon">Expiring in 7 Days</option>
                                    <option value="inactive">Inactive Members</option>
                                    <option value="custom">Custom Member IDs</option>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="custom_ids_div" style="display: none;">
                                <label for="custom_member_ids" class="form-label">Member IDs (comma-separated)</label>
                                <input type="text" class="form-control" name="custom_member_ids" placeholder="1,2,3,4">
                            </div>
                            
                            <div class="mb-3">
                                <label for="bulk_message" class="form-label">Message *</label>
                                <textarea class="form-control sms-message" name="bulk_message" rows="4" maxlength="160" required></textarea>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">Max 160 characters</small>
                                    <span class="char-counter">0 / 160</span>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to send bulk SMS? This action cannot be undone.');">
                                <i class="fas fa-broadcast-tower me-2"></i>Send Bulk SMS
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Automated Reminders -->
            <div class="col-lg-12 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Automated Reminders</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="send_attendance_reminders" value="1">
                                    <button type="submit" class="btn btn-info w-100">
                                        <i class="fas fa-calendar-check me-2"></i>Send Attendance Reminders
                                    </button>
                                </form>
                                <small class="text-muted d-block mt-2">Send to members who haven't attended in 3 days</small>
                            </div>
                            <div class="col-md-4">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="send_payment_reminders" value="1">
                                    <input type="hidden" name="days_before" value="7">
                                    <button type="submit" class="btn btn-info w-100">
                                        <i class="fas fa-money-bill me-2"></i>Send Payment Reminders (7 days)
                                    </button>
                                </form>
                                <small class="text-muted d-block mt-2">Send to members expiring in 7 days</small>
                            </div>
                            <div class="col-md-4">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="send_payment_reminders" value="1">
                                    <input type="hidden" name="days_before" value="3">
                                    <button type="submit" class="btn btn-info w-100">
                                        <i class="fas fa-exclamation-triangle me-2"></i>Send Payment Reminders (3 days)
                                    </button>
                                </form>
                                <small class="text-muted d-block mt-2">Send to members expiring in 3 days</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent SMS Log -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent SMS Log</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Member</th>
                                        <th>Phone</th>
                                        <th>Message</th>
                                        <th>Template</th>
                                        <th>Provider</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recentSMS->num_rows > 0): ?>
                                        <?php while ($sms = $recentSMS->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y g:i A', strtotime($sms['sent_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($sms['member_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($sms['phone']); ?></td>
                                                <td>
                                                    <small><?php echo htmlspecialchars(substr($sms['message'], 0, 50)); ?>
                                                    <?php echo strlen($sms['message']) > 50 ? '...' : ''; ?></small>
                                                </td>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($sms['template_name'] ?? 'Custom'); ?></span></td>
                                                <td><span class="badge bg-info"><?php echo ucfirst($sms['provider']); ?></span></td>
                                                <td>
                                                    <?php if ($sms['status'] === 'sent'): ?>
                                                        <span class="badge bg-success">Sent</span>
                                                    <?php elseif ($sms['status'] === 'failed'): ?>
                                                        <span class="badge bg-danger" title="<?php echo htmlspecialchars($sms['error_message']); ?>">Failed</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No SMS sent yet</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Character counter for SMS messages
        document.querySelectorAll('.sms-message').forEach(textarea => {
            const counter = textarea.closest('.mb-3').querySelector('.char-counter');
            
            textarea.addEventListener('input', function() {
                const length = this.value.length;
                const maxLength = this.getAttribute('maxlength');
                
                counter.textContent = length + ' / ' + maxLength;
                
                counter.classList.remove('warning', 'danger');
                if (length > maxLength * 0.9) {
                    counter.classList.add('danger');
                } else if (length > maxLength * 0.7) {
                    counter.classList.add('warning');
                }
            });
        });
        
        // Show/hide custom IDs field
        document.getElementById('recipient_type').addEventListener('change', function() {
            const customDiv = document.getElementById('custom_ids_div');
            customDiv.style.display = this.value === 'custom' ? 'block' : 'none';
        });
    </script>
</body>
</html>
