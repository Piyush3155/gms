<?php
require_once '../includes/config.php';
require_role('member');

$user_id = $_SESSION['user_id'];

// Get member info
$member = $conn->query("SELECT m.*, p.name as plan_name, t.name as trainer_name FROM members m LEFT JOIN plans p ON m.plan_id = p.id LEFT JOIN trainers t ON m.trainer_id = t.id WHERE m.id = $user_id")->fetch_assoc();

// Get attendance this month
$this_month = date('Y-m');
$monthly_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE user_id = $user_id AND DATE_FORMAT(date, '%Y-%m') = '$this_month' AND status = 'present'")->fetch_assoc()['count'];

// Get workout plans count
$workout_plans_count = $conn->query("SELECT COUNT(*) as count FROM workout_plans WHERE member_id = $user_id")->fetch_assoc()['count'];

// Get diet plans count
$diet_plans_count = $conn->query("SELECT COUNT(*) as count FROM diet_plans WHERE member_id = $user_id")->fetch_assoc()['count'];

// Get class bookings count
$class_bookings_count = $conn->query("SELECT COUNT(*) as count FROM class_bookings WHERE member_id = $user_id AND status = 'confirmed'")->fetch_assoc()['count'];

// Get recent workout plans
$recent_workouts = $conn->query("SELECT wp.*, t.name as trainer_name FROM workout_plans wp JOIN trainers t ON wp.trainer_id = t.id WHERE wp.member_id = $user_id ORDER BY wp.created_at DESC LIMIT 3");

// Get recent payments
$recent_payments = $conn->query("SELECT p.*, pl.name as plan_name FROM payments p JOIN plans pl ON p.plan_id = pl.id WHERE p.member_id = $user_id ORDER BY p.payment_date DESC LIMIT 3");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Welcome back, <?php echo $member['name']; ?>!</h2>
            <div>
                <span class="text-muted">Member Dashboard</span>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-id-card fa-2x text-primary mb-2"></i>
                        <h4><?php echo ucfirst($member['status']); ?></h4>
                        <p class="text-muted mb-0">Membership Status</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                        <h4><?php echo $monthly_attendance; ?></h4>
                        <p class="text-muted mb-0">This Month Attendance</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-dumbbell fa-2x text-info mb-2"></i>
                        <h4><?php echo $workout_plans_count; ?></h4>
                        <p class="text-muted mb-0">Workout Plans</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-utensils fa-2x text-warning mb-2"></i>
                        <h4><?php echo $diet_plans_count; ?></h4>
                        <p class="text-muted mb-0">Diet Plans</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-alt fa-2x text-secondary mb-2"></i>
                        <h4><?php echo $class_bookings_count; ?></h4>
                        <p class="text-muted mb-0">Class Bookings</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-tie fa-2x text-dark mb-2"></i>
                        <h4><?php echo $member['trainer_name'] ?: 'Not Assigned'; ?></h4>
                        <p class="text-muted mb-0">Assigned Trainer</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Workout Plans</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_workouts->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($workout = $recent_workouts->fetch_assoc()): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex w-100 justify-content-between">
                                            <small class="text-muted"><?php echo $workout['trainer_name']; ?></small>
                                            <small class="text-muted"><?php echo date('M d, Y', strtotime($workout['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo substr($workout['description'], 0, 100) . (strlen($workout['description']) > 100 ? '...' : ''); ?></p>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No workout plans yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Payments</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_payments->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($payment = $recent_payments->fetch_assoc()): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex w-100 justify-content-between">
                                            <small class="text-muted"><?php echo $payment['plan_name']; ?></small>
                                            <small class="text-muted"><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></small>
                                        </div>
                                        <p class="mb-1">₹<?php echo number_format($payment['amount'], 2); ?> - <?php echo ucfirst($payment['status']); ?></p>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No payment history.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <a href="attendance.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-calendar-check me-2"></i>Mark Attendance
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="workouts.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-dumbbell me-2"></i>View Workouts
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="diets.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-utensils me-2"></i>View Diets
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="classes.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-calendar-alt me-2"></i>Book Classes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>