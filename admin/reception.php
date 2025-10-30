<?php
require_once '../includes/config.php';
require_permission('reception', 'view');

// Handle quick registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['quick_register'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $plan_id = sanitize($_POST['plan_id']);
    $amount = sanitize($_POST['amount']);
    $payment_method = sanitize($_POST['payment_method']);
    $join_date = date('Y-m-d');
    
    // Get plan details
    $plan = $conn->query("SELECT * FROM plans WHERE id = $plan_id")->fetch_assoc();
    $expiry_date = date('Y-m-d', strtotime("+$plan[duration_months] months"));
    
    // Insert member
    $stmt = $conn->prepare("INSERT INTO members (name, contact, email, join_date, expiry_date, plan_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("sssssi", $name, $phone, $email, $join_date, $expiry_date, $plan_id);
    if ($stmt->execute()) {
        $member_id = $conn->insert_id;
        
        // Generate QR code
        $qr_code = 'GMS_MEMBER_' . $member_id . '_' . md5($member_id . $email);
        $update_stmt = $conn->prepare("UPDATE members SET qr_code = ? WHERE id = ?");
        $update_stmt->bind_param("si", $qr_code, $member_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Insert payment
        $invoice_no = 'INV' . date('Ymd') . $member_id;
        $stmt2 = $conn->prepare("INSERT INTO payments (member_id, plan_id, amount, payment_date, method, invoice_no, status) VALUES (?, ?, ?, ?, ?, ?, 'paid')");
        $stmt2->bind_param("iissss", $member_id, $plan_id, $amount, $join_date, $payment_method, $invoice_no);
        $stmt2->execute();
        $stmt2->close();
        
        $success = "Member registered successfully! Invoice: $invoice_no";
    } else {
        $error = "Error registering member.";
    }
    $stmt->close();
}

// Handle renewal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['renew_membership'])) {
    $member_id = sanitize($_POST['member_id']);
    $amount = sanitize($_POST['renew_amount']);
    $payment_method = sanitize($_POST['renew_payment_method']);
    
    // Get current member
    $member = $conn->query("SELECT * FROM members WHERE id = $member_id")->fetch_assoc();
    $plan = $conn->query("SELECT * FROM plans WHERE id = $member[plan_id]")->fetch_assoc();
    
    // Calculate new expiry
    $current_expiry = $member['expiry_date'];
    $new_expiry = date('Y-m-d', strtotime($current_expiry . " +$plan[duration_months] months"));
    
    // Update member
    $conn->query("UPDATE members SET expiry_date = '$new_expiry', status = 'active' WHERE id = $member_id");
    
    // Insert payment
    $invoice_no = 'RNW' . date('Ymd') . $member_id;
    $stmt = $conn->prepare("INSERT INTO payments (member_id, plan_id, amount, payment_date, method, invoice_no, status) VALUES (?, ?, ?, CURDATE(), ?, ?, 'paid')");
    $stmt->bind_param("iisss", $member_id, $member['plan_id'], $amount, $payment_method, $invoice_no);
    $stmt->execute();
    $stmt->close();
    
    $success = "Membership renewed successfully! New expiry: $new_expiry, Invoice: $invoice_no";
}

// Handle POS payment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pos_payment'])) {
    $member_id = sanitize($_POST['pos_member_id']);
    $amount = sanitize($_POST['pos_amount']);
    $description = sanitize($_POST['pos_description']);
    $payment_method = sanitize($_POST['pos_payment_method']);
    
    // Insert payment
    $invoice_no = 'POS' . date('YmdHis');
    $stmt = $conn->prepare("INSERT INTO payments (member_id, amount, payment_date, method, invoice_no, status) VALUES (?, ?, CURDATE(), ?, ?, 'paid')");
    $stmt->bind_param("issss", $member_id, $amount, $payment_method, $invoice_no);
    $stmt->execute();
    $stmt->close();
    
    // Log as expense if not membership
    if (!empty($description)) {
        $conn->query("INSERT INTO expenses (category, amount, expense_date, description) VALUES ('POS Payment', $amount, CURDATE(), '$description')");
    }
    
    $success = "Payment recorded successfully! Invoice: $invoice_no";
}

// Get plans for dropdown
$plans = $conn->query("SELECT id, name, amount FROM plans");

// Get active members for renewal and POS
$members = $conn->query("SELECT id, name, contact, expiry_date FROM members WHERE status = 'active' ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Front Desk - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Front Desk / Reception</h2>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- Quick Registration -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-user-plus me-2"></i>Quick Registration</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Plan</label>
                                <select class="form-control" name="plan_id" required onchange="updateAmount(this)">
                                    <option value="">Select Plan</option>
                                    <?php while ($plan = $plans->fetch_assoc()): ?>
                                        <option value="<?php echo $plan['id']; ?>" data-amount="<?php echo $plan['amount']; ?>"><?php echo $plan['name']; ?> - ₹<?php echo $plan['amount']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" class="form-control" name="amount" id="reg_amount" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-control" name="payment_method" required>
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="upi">UPI</option>
                                </select>
                            </div>
                            <button type="submit" name="quick_register" class="btn btn-primary w-100">Register & Pay</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Membership Renewal -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-sync-alt me-2"></i>Membership Renewal</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Select Member</label>
                                <select class="form-control" name="member_id" required onchange="loadMemberDetails(this)">
                                    <option value="">Select Member</option>
                                    <?php 
                                    $members->data_seek(0);
                                    while ($member = $members->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $member['id']; ?>" data-expiry="<?php echo $member['expiry_date']; ?>"><?php echo $member['name']; ?> (<?php echo $member['contact']; ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Current Expiry</label>
                                <input type="text" class="form-control" id="current_expiry" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Renewal Amount</label>
                                <input type="number" class="form-control" name="renew_amount" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-control" name="renew_payment_method" required>
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="upi">UPI</option>
                                </select>
                            </div>
                            <button type="submit" name="renew_membership" class="btn btn-success w-100">Renew Membership</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- POS Payment -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-cash-register me-2"></i>POS Payment</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Member (Optional)</label>
                                <select class="form-control" name="pos_member_id">
                                    <option value="">Walk-in Customer</option>
                                    <?php 
                                    $members->data_seek(0);
                                    while ($member = $members->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $member['id']; ?>"><?php echo $member['name']; ?> (<?php echo $member['contact']; ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" class="form-control" name="pos_amount" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control" name="pos_description" placeholder="e.g., Supplement purchase">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-control" name="pos_payment_method" required>
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="upi">UPI</option>
                                </select>
                            </div>
                            <button type="submit" name="pos_payment" class="btn btn-info w-100">Record Payment</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Member</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Invoice</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $recent = $conn->query("SELECT p.*, m.name as member_name FROM payments p LEFT JOIN members m ON p.member_id = m.id ORDER BY p.id DESC LIMIT 10");
                                    while ($payment = $recent->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td><?php echo date('H:i', strtotime($payment['payment_date'])); ?></td>
                                            <td><?php echo $payment['member_name'] ?? 'Walk-in'; ?></td>
                                            <td><?php echo $payment['plan_id'] ? 'Membership' : 'POS'; ?></td>
                                            <td>₹<?php echo $payment['amount']; ?></td>
                                            <td><?php echo ucfirst($payment['method']); ?></td>
                                            <td><?php echo $payment['invoice_no']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateAmount(select) {
            const amount = select.options[select.selectedIndex].getAttribute('data-amount');
            document.getElementById('reg_amount').value = amount;
        }

        function loadMemberDetails(select) {
            const expiry = select.options[select.selectedIndex].getAttribute('data-expiry');
            document.getElementById('current_expiry').value = expiry;
        }
    </script>
</body>
</html>