<?php
require_once '../includes/config.php';
require_role('admin');

// Handle payment recording
if (isset($_POST['record_payment'])) {
    $member_id = sanitize($_POST['member_id']);
    $plan_id = sanitize($_POST['plan_id']);
    $amount = sanitize($_POST['amount']);
    $payment_date = sanitize($_POST['payment_date']);
    $method = sanitize($_POST['method']);
    $invoice_no = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
    $status = sanitize($_POST['status']);

    $stmt = $conn->prepare("INSERT INTO payments (member_id, plan_id, amount, payment_date, method, invoice_no, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $member_id, $plan_id, $amount, $payment_date, $method, $invoice_no, $status);

    if ($stmt->execute()) {
        // Update member status to active if payment is successful
        if ($status == 'paid') {
            $conn->query("UPDATE members SET status='active' WHERE id=$member_id");
        }
        $success = "Payment recorded successfully!";
    } else {
        $errors[] = "Failed to record payment.";
    }
    $stmt->close();
}

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

// Calculate totals
$total_revenue = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status='paid'")->fetch_assoc()['total'] ?? 0;
$this_month_revenue = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status='paid' AND DATE_FORMAT(payment_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')")->fetch_assoc()['total'] ?? 0;
$pending_payments = $conn->query("SELECT COUNT(*) as count FROM payments WHERE status='pending'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment & Billing - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-credit-card me-2"></i>Payment & Billing</h2>
            <button class="btn btn-modern" data-bs-toggle="modal" data-bs-target="#paymentModal">
                <i class="fas fa-plus me-2"></i>Record Payment
            </button>
        </div>

        <!-- Revenue Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-dollar-sign me-2"></i>Total Revenue</h5>
                        <h2>$<?php echo number_format($total_revenue, 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-calendar-alt me-2"></i>This Month</h5>
                        <h2>$<?php echo number_format($this_month_revenue, 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-clock me-2"></i>Pending Payments</h5>
                        <h2><?php echo $pending_payments; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Payment History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Member</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($payment = $payments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $payment['invoice_no']; ?></td>
                                    <td><?php echo $payment['member_name']; ?></td>
                                    <td><?php echo $payment['plan_name']; ?></td>
                                    <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-<?php
                                                echo match($payment['method']) {
                                                    'cash' => 'money-bill-wave',
                                                    'card' => 'credit-card',
                                                    'upi' => 'mobile-alt',
                                                    'bank_transfer' => 'university',
                                                    default => 'question'
                                                };
                                            ?> me-1"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $payment['method'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php
                                            echo match($payment['status']) {
                                                'paid' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="printInvoice('<?php echo $payment['id']; ?>')">
                                            <i class="fas fa-print me-1"></i>Print
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Record New Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Member</label>
                            <select class="form-control" name="member_id" required>
                                <option value="">Select Member</option>
                                <?php
                                $members->data_seek(0);
                                while ($member = $members->fetch_assoc()): ?>
                                    <option value="<?php echo $member['id']; ?>"><?php echo $member['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Plan</label>
                            <select class="form-control" name="plan_id" id="planSelect" required onchange="updateAmount()">
                                <option value="">Select Plan</option>
                                <?php
                                $plans->data_seek(0);
                                while ($plan = $plans->fetch_assoc()): ?>
                                    <option value="<?php echo $plan['id']; ?>" data-amount="<?php echo $plan['amount']; ?>"><?php echo $plan['name']; ?> - $<?php echo $plan['amount']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="amount" id="amountInput" step="0.01" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Payment Date</label>
                                    <input type="date" class="form-control" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <select class="form-control" name="method" required>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="upi">UPI</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" required>
                                <option value="paid">Paid</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="record_payment" class="btn btn-modern">Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function updateAmount() {
            const select = document.getElementById('planSelect');
            const amountInput = document.getElementById('amountInput');
            const selectedOption = select.options[select.selectedIndex];
            const amount = selectedOption.getAttribute('data-amount');
            if (amount) {
                amountInput.value = amount;
            }
        }

        function printInvoice(paymentId) {
            // Simple print functionality - in real app, you'd generate a proper invoice
            window.open(`invoice.php?id=${paymentId}`, '_blank');
        }
    </script>
        </div>
    </div>
    </div>
</body>
</html>