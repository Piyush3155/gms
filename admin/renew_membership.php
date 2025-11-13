<?php
require_once '../includes/config.php';
require_role('admin');

// Get member details if member_id is provided
$member = null;
$errors = [];
$success = '';

if (isset($_GET['member_id']) && is_numeric($_GET['member_id'])) {
    $member_id = $_GET['member_id'];
    $result = $conn->query("SELECT m.*, p.name as plan_name, p.amount, p.duration_months FROM members m LEFT JOIN plans p ON m.plan_id = p.id WHERE m.id = $member_id");
    $member = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_id = sanitize($_POST['member_id']);
    $plan_id = sanitize($_POST['plan_id']);
    $payment_method = sanitize($_POST['payment_method']);
    $renewal_date = date('Y-m-d');

    if (empty($member_id) || empty($plan_id) || empty($payment_method)) {
        $errors[] = "All fields are required.";
    } else {
        // Get plan details
        $plan_result = $conn->query("SELECT * FROM plans WHERE id = $plan_id");
        $plan = $plan_result->fetch_assoc();

        // Calculate new expiry date
        $expiry_date = date('Y-m-d', strtotime($renewal_date . ' + ' . $plan['duration_months'] . ' months'));

        // Update member
        $update_sql = "UPDATE members SET plan_id = ?, join_date = ?, expiry_date = ?, status = 'active' WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("issi", $plan_id, $renewal_date, $expiry_date, $member_id);

        if ($stmt->execute()) {
            // Record payment
            $payment_sql = "INSERT INTO payments (member_id, plan_id, amount, payment_date, method, status) VALUES (?, ?, ?, ?, ?, 'paid')";
            $payment_stmt = $conn->prepare($payment_sql);
            $payment_stmt->bind_param("iisss", $member_id, $plan_id, $plan['amount'], $renewal_date, $payment_method);

            if ($payment_stmt->execute()) {
                $success = "Membership renewed successfully!";
                // Redirect to members list
                redirect('members.php');
            } else {
                $errors[] = "Error recording payment.";
            }
            $payment_stmt->close();
        } else {
            $errors[] = "Error updating membership.";
        }
        $stmt->close();
    }
}

// Get all members for dropdown
$members = $conn->query("SELECT id, name, email FROM members");

// Get all plans
$plans = $conn->query("SELECT id, name, amount, duration_months FROM plans");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renew Membership - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Renew Membership</h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <?php echo $success; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Select Member</label>
                                    <select class="form-control" name="member_id" id="memberSelect" required>
                                        <option value="">Choose a member...</option>
                                        <?php while ($row = $members->fetch_assoc()): ?>
                                            <option value="<?php echo $row['id']; ?>" <?php echo ($member && $member['id'] == $row['id']) ? 'selected' : ''; ?>>
                                                <?php echo $row['name'] . ' (' . $row['email'] . ')'; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <?php if ($member): ?>
                                    <div class="mb-3">
                                        <label class="form-label">Current Membership Details</label>
                                        <div class="border p-3 bg-light">
                                            <p><strong>Name:</strong> <?php echo $member['name']; ?></p>
                                            <p><strong>Current Plan:</strong> <?php echo $member['plan_name']; ?></p>
                                            <p><strong>Status:</strong> <span class="badge bg-<?php echo $member['status'] == 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($member['status']); ?></span></p>
                                            <p><strong>Expiry Date:</strong> <?php echo $member['expiry_date'] ?? 'N/A'; ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="form-label">Select Plan</label>
                                    <select class="form-control" name="plan_id" required>
                                        <option value="">Choose a plan...</option>
                                        <?php
                                        $plans->data_seek(0); // Reset pointer
                                        while ($plan = $plans->fetch_assoc()): ?>
                                            <option value="<?php echo $plan['id']; ?>" <?php echo ($member && $member['plan_id'] == $plan['id']) ? 'selected' : ''; ?>>
                                                <?php echo $plan['name'] . ' - ₹' . $plan['amount'] . ' (' . $plan['duration_months'] . ' months)'; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <select class="form-control" name="payment_method" required>
                                        <option value="">Select payment method...</option>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="upi">UPI</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                    </select>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Renew Membership</button>
                                    <a href="members.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit form when member is selected (optional)
        document.getElementById('memberSelect').addEventListener('change', function() {
            if (this.value) {
                window.location.href = '?member_id=' + this.value;
            }
        });
    </script>
</body>
</html>