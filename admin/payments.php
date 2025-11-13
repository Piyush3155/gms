<?php
require_once '../includes/config.php';
require_role('admin');

// Handle payment recording
if (isset($_POST['record_payment'])) {
    $member_id = intval($_POST['member_id']);
    $plan_id = intval($_POST['plan_id']);
    $amount = floatval($_POST['amount']);
    $payment_date = trim($_POST['payment_date']);
    $method = trim($_POST['method']);
    $invoice_no = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
    $status = trim($_POST['status']);

    $stmt = $conn->prepare("INSERT INTO payments (member_id, plan_id, amount, payment_date, method, invoice_no, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iidssss", $member_id, $plan_id, $amount, $payment_date, $method, $invoice_no, $status);

    if ($stmt->execute()) {
        // Update member status to active if payment is successful
        if ($status == 'paid') {
            $update_stmt = $conn->prepare("UPDATE members SET status='active' WHERE id=?");
            $update_stmt->bind_param("i", $member_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
        log_activity("Recorded payment", "payments", "Invoice: $invoice_no, Member ID: $member_id, Amount: $amount");
        redirect('payments.php?msg=16');
    } else {
        $errors[] = "Failed to record payment.";
    }
    $stmt->close();
}

// Get payment statistics
$payment_stats = [];

// Today's revenue
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT SUM(amount) as total FROM payments WHERE status='paid' AND DATE(payment_date) = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$payment_stats['todays_revenue'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Monthly revenue
$current_month = date('Y-m');
$stmt = $conn->prepare("SELECT SUM(amount) as total FROM payments WHERE status='paid' AND DATE_FORMAT(payment_date, '%Y-%m') = ?");
$stmt->bind_param("s", $current_month);
$stmt->execute();
$result = $stmt->get_result();
$payment_stats['monthly_revenue'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Total payments count
$result = $conn->query("SELECT COUNT(*) as total FROM payments WHERE status='paid'");
$payment_stats['total_payments'] = $result->fetch_assoc()['total'];

// Pending payments count
$result = $conn->query("SELECT COUNT(*) as total FROM payments WHERE status='pending'");
$payment_stats['pending_payments'] = $result->fetch_assoc()['total'];

// Failed payments count
$result = $conn->query("SELECT COUNT(*) as total FROM payments WHERE status='failed'");
$payment_stats['failed_payments'] = $result->fetch_assoc()['total'];

// Average payment amount
$result = $conn->query("SELECT AVG(amount) as avg_amount FROM payments WHERE status='paid'");
$payment_stats['avg_payment'] = $result->fetch_assoc()['avg_amount'] ?? 0;

// Get all payments with member and plan details
$payments = $conn->query("
    SELECT p.*, m.name as member_name, pl.name as plan_name
    FROM payments p
    JOIN members m ON p.member_id = m.id
    JOIN plans pl ON p.plan_id = pl.id
    ORDER BY p.payment_date DESC
");

// Get members and plans for dropdowns
$members = $conn->query("SELECT id, name FROM members ORDER BY name");
$plans = $conn->query("SELECT id, name, amount FROM plans ORDER BY name");

// Calculate totals (legacy variables for backward compatibility)
$total_revenue = $payment_stats['monthly_revenue'];
$this_month_revenue = $payment_stats['monthly_revenue'];
$pending_payments = $payment_stats['pending_payments'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment & Billing - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=1.0">
    <link href="../assets/css/style.css?v=1.0" rel="stylesheet">
    <link href="../assets/css/custom.css?v=1.0" rel="stylesheet">
    <link href="../assets/css/components.css?v=1.0" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css?v=1.0">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="page-header">
    <h1 class="page-title">Payment & Billing</h1>
    <div class="page-options">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal">
            <i class="fas fa-plus me-1"></i>Record Payment
        </button>
    </div>
</div>

<!-- Payment Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.1s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Today's Revenue</div>
                    <h2 class="info-card-value">₹<?php echo number_format($payment_stats['todays_revenue'], 0); ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.2s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Monthly Revenue</div>
                    <h2 class="info-card-value">₹<?php echo number_format($payment_stats['monthly_revenue'], 0); ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.3s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Total Payments</div>
                    <h2 class="info-card-value"><?php echo $payment_stats['total_payments']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.4s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Pending</div>
                    <h2 class="info-card-value"><?php echo $payment_stats['pending_payments']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.5s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Failed</div>
                    <h2 class="info-card-value"><?php echo $payment_stats['failed_payments']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.6s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                    <i class="fas fa-calculator"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Avg Payment</div>
                    <h2 class="info-card-value">₹<?php echo number_format($payment_stats['avg_payment'], 0); ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Revenue Summary -->
<div class="row g-4 mb-4" style="display: none;">
    <!-- This section is now replaced by the new statistics cards above -->
    <div class="col-md-4">
        <div class="feature-card">
            <div class="card-icon bg-success-light text-success">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="card-content">
                <h3 class="card-title">₹<?php echo number_format($total_revenue, 2); ?></h3>
                <p class="card-text">Total Revenue</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="feature-card">
            <div class="card-icon bg-primary-light text-primary">
                <i class="bi bi-calendar-month"></i>
            </div>
            <div class="card-content">
                <h3 class="card-title">₹<?php echo number_format($this_month_revenue, 2); ?></h3>
                <p class="card-text">This Month's Revenue</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="feature-card">
            <div class="card-icon bg-warning-light text-warning">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="card-content">
                <h3 class="card-title"><?php echo $pending_payments; ?></h3>
                <p class="card-text">Pending Payments</p>
            </div>
        </div>
    </div>
</div>

<!-- Payments Table -->
<div class="card-modern">
    <div class="card-header">
        <h5 class="card-title mb-0">Payment History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="payments-table" class="table table-modern" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Member</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th data-sortable="false" data-exportable="false">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($payment = $payments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['invoice_no']); ?></td>
                            <td><?php echo htmlspecialchars($payment['member_name']); ?></td>
                            <td><?php echo htmlspecialchars($payment['plan_name']); ?></td>
                            <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                            <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $payment['method']))); ?></td>
                            <td>
                                <span class="status-indicator <?php echo 'status-' . htmlspecialchars($payment['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($payment['status'])); ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="invoice.php?id=<?php echo $payment['id']; ?>" class="btn-icon" title="Print Invoice" target="_blank"><i class="bi bi-printer"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record New Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="paymentForm" autocomplete="off">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Member *</label>
                        <select class="form-select" name="member_id" required>
                            <option value="">Select Member</option>
                            <?php mysqli_data_seek($members, 0); while ($member = $members->fetch_assoc()): ?>
                                <option value="<?php echo $member['id']; ?>"><?php echo htmlspecialchars($member['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Plan *</label>
                        <select class="form-select" name="plan_id" id="planSelect" required onchange="updateAmount()">
                            <option value="">Select Plan</option>
                            <?php mysqli_data_seek($plans, 0); while ($plan = $plans->fetch_assoc()): ?>
                                <option value="<?php echo $plan['id']; ?>" data-amount="<?php echo $plan['amount']; ?>"><?php echo htmlspecialchars($plan['name']); ?> - ₹<?php echo $plan['amount']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount *</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" class="form-control" name="amount" id="amountInput" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Date *</label>
                            <input type="date" class="form-control" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method *</label>
                            <select class="form-select" name="method" required>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="upi">UPI</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select class="form-select" name="status" required>
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="record_payment" class="btn btn-primary">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('payments-table');
    if (table) {
        new DataTable(table, {
            searchable: true,
            pagination: true,
            sortable: true,
            exportable: true,
            exportOptions: {
                fileName: 'GMS_Payments_Export',
            }
        });
    }
    
    const paymentForm = document.getElementById('paymentForm');
    if(paymentForm) {
        new FormValidator(paymentForm);
    }
});

function updateAmount() {
    const select = document.getElementById('planSelect');
    const amountInput = document.getElementById('amountInput');
    const selectedOption = select.options[select.selectedIndex];
    const amount = selectedOption.getAttribute('data-amount');
    amountInput.value = amount || '';
}
</script>
</body>
</html>