<?php
require_once '../includes/config.php';
require_role('admin');

$user_id = $_SESSION['user_id'];

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, email=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $phone, $email, $user_id);

        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
        } else {
            $errors[] = "Failed to update profile.";
        }
        $stmt->close();
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Get current password hash
    $user = $conn->query("SELECT password FROM users WHERE id = $user_id")->fetch_assoc();

    if (!password_verify($current_password, $user['password'])) {
        $errors[] = "Current password is incorrect.";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hashed_password' WHERE id=$user_id");
        $success = "Password changed successfully!";
    }
}

// Get admin info
$admin = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px;">
                            <i class="fas fa-user-shield fa-3x text-white"></i>
                        </div>

                        <h4><?php echo $admin['name']; ?></h4>
                        <p class="text-muted">Administrator</p>

                        <div class="row text-center mt-3">
                            <div class="col-6">
                                <strong><?php echo $admin['email']; ?></strong>
                                <br><small class="text-muted">Email</small>
                            </div>
                            <div class="col-6">
                                <strong><?php
                                $member_count = $conn->query("SELECT COUNT(*) as count FROM members WHERE status = 'active'")->fetch_assoc()['count'];
                                echo $member_count;
                                ?></strong>
                                <br><small class="text-muted">Active Members</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Profile Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo $admin['name']; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo $admin['email']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" class="form-control" name="phone" value="<?php echo $admin['phone'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Current Password *</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">New Password *</label>
                                        <input type="password" class="form-control" name="new_password" required minlength="6">
                                        <div class="form-text">Minimum 6 characters</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password *</label>
                                        <input type="password" class="form-control" name="confirm_password" required minlength="6">
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="change_password" class="btn btn-warning">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Statistics -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>System Overview</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stats = $conn->query("
                            SELECT
                                (SELECT COUNT(*) FROM members WHERE status = 'active') as active_members,
                                (SELECT COUNT(*) FROM trainers) as active_trainers,
                                (SELECT COUNT(*) FROM plans) as active_plans,
                                (SELECT COUNT(*) FROM attendance WHERE DATE(date) = CURDATE()) as today_attendance,
                                (SELECT SUM(amount) FROM payments WHERE DATE(payment_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as monthly_revenue
                        ")->fetch_assoc();
                        ?>

                        <div class="row text-center">
                            <div class="col-md-2 col-6">
                                <h3 class="text-primary"><?php echo $stats['active_members']; ?></h3>
                                <small class="text-muted">Active Members</small>
                            </div>
                            <div class="col-md-2 col-6">
                                <h3 class="text-success"><?php echo $stats['active_trainers']; ?></h3>
                                <small class="text-muted">Active Trainers</small>
                            </div>
                            <div class="col-md-2 col-6">
                                <h3 class="text-info"><?php echo $stats['active_plans']; ?></h3>
                                <small class="text-muted">Active Plans</small>
                            </div>
                            <div class="col-md-2 col-6">
                                <h3 class="text-warning"><?php echo $stats['today_attendance']; ?></h3>
                                <small class="text-muted">Today's Attendance</small>
                            </div>
                            <div class="col-md-2 col-6">
                                <h3 class="text-danger">$<?php echo number_format($stats['monthly_revenue'] ?: 0, 2); ?></h3>
                                <small class="text-muted">Monthly Revenue</small>
                            </div>
                            <div class="col-md-2 col-6">
                                <h3 class="text-secondary"><?php
                                $total_expenses = $conn->query("SELECT SUM(amount) as total FROM expenses WHERE DATE(expense_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetch_assoc()['total'] ?: 0;
                                echo '$' . number_format($total_expenses, 2);
                                ?></h3>
                                <small class="text-muted">Monthly Expenses</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
    </div>

</body>
</html>