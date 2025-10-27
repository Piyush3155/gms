<?php
require_once 'includes/config.php';
if (!is_logged_in()) {
    redirect('login.php');
}

$user_role = get_user_role();
$user_name = $_SESSION['user_name'];

// Get dashboard stats based on role
$stats = [];

if ($user_role == 'admin') {
    // Total members
    $result = $conn->query("SELECT COUNT(*) as total FROM members");
    $stats['members'] = $result->fetch_assoc()['total'];

    // Total trainers
    $result = $conn->query("SELECT COUNT(*) as total FROM trainers");
    $stats['trainers'] = $result->fetch_assoc()['total'];

    // Today's attendance
    $today = date('Y-m-d');
    $result = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE date = '$today'");
    $stats['attendance'] = $result->fetch_assoc()['total'];

    // Monthly revenue
    $current_month = date('Y-m');
    $result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = '$current_month'");
    $stats['revenue'] = $result->fetch_assoc()['total'] ?? 0;
} elseif ($user_role == 'trainer') {
    // Assigned members
    $trainer_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE trainer_id = $trainer_id");
    $stats['members'] = $result->fetch_assoc()['total'];

    // Today's sessions
    $today = date('Y-m-d');
    $result = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE user_id = $trainer_id AND date = '$today'");
    $stats['sessions'] = $result->fetch_assoc()['total'];
} elseif ($user_role == 'member') {
    // Membership status
    $member_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT status FROM members WHERE id = $member_id");
    $stats['status'] = $result->fetch_assoc()['status'];

    // Attendance this month
    $current_month = date('Y-m');
    $result = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE user_id = $member_id AND DATE_FORMAT(date, '%Y-%m') = '$current_month'");
    $stats['attendance'] = $result->fetch_assoc()['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/custom.css" rel="stylesheet">
</head>
<body class="sidebar-open">
    <div class="main-wrapper">
    <?php include 'includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
            <div class="fade-in">
                <!-- Modern Page Header -->
                <div class="page-title-section">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1><i class="fas fa-chart-line me-3"></i>Welcome back, <?php echo $user_name; ?>!</h1>
                            <p class="lead mb-0">Here's a snapshot of your gym's activity today - <?php echo date('l, F j, Y'); ?></p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="d-flex justify-content-md-end gap-2">
                                <a href="<?php echo SITE_URL; ?><?php echo $_SESSION['user_role']; ?>/profile.php" class="btn btn-light">
                                    <i class="fas fa-user me-2"></i>My Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards Grid -->
                <div class="row">
                    <?php if ($user_role == 'admin'): ?>
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="stats-card bg-primary slide-up" style="animation-delay: 0.1s;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="card-title">Total Members</h5>
                                            <h2><?php echo $stats['members']; ?></h2>
                                            <p class="mb-0 mt-2" style="font-size: 0.875rem;">
                                                <i class="fas fa-arrow-up me-1"></i> Active members
                                            </p>
                                        </div>
                                        <div class="icon"><i class="fas fa-users"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="stats-card bg-danger slide-up" style="animation-delay: 0.2s;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="card-title">Total Trainers</h5>
                                            <h2><?php echo $stats['trainers']; ?></h2>
                                            <p class="mb-0 mt-2" style="font-size: 0.875rem;">
                                                <i class="fas fa-certificate me-1"></i> Expert trainers
                                            </p>
                                        </div>
                                        <div class="icon"><i class="fas fa-user-tie"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="stats-card bg-info slide-up" style="animation-delay: 0.3s;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="card-title">Today's Attendance</h5>
                                            <h2><?php echo $stats['attendance']; ?></h2>
                                            <p class="mb-0 mt-2" style="font-size: 0.875rem;">
                                                <i class="fas fa-clock me-1"></i> Check-ins today
                                            </p>
                                        </div>
                                        <div class="icon"><i class="fas fa-calendar-check"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="stats-card bg-success slide-up" style="animation-delay: 0.4s;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="card-title">Monthly Revenue</h5>
                                            <h2>₹<?php echo number_format($stats['revenue'], 0); ?></h2>
                                            <p class="mb-0 mt-2" style="font-size: 0.875rem;">
                                                <i class="fas fa-trending-up me-1"></i> This month
                                            </p>
                                        </div>
                                        <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions for Admin -->
                        <div class="col-12 mb-4">
                            <div class="card-modern slide-up" style="animation-delay: 0.5s;">
                                <div class="card-header">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </div>
                                <div class="card-body">
                                    <div class="quick-actions-grid">
                                        <a href="<?php echo SITE_URL; ?>admin/members.php" class="quick-action-btn">
                                            <i class="fas fa-user-plus"></i>
                                            <span>Add Member</span>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>admin/trainers.php" class="quick-action-btn">
                                            <i class="fas fa-chalkboard-teacher"></i>
                                            <span>Manage Trainers</span>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>admin/attendance.php" class="quick-action-btn">
                                            <i class="fas fa-clipboard-check"></i>
                                            <span>Mark Attendance</span>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>admin/payments.php" class="quick-action-btn">
                                            <i class="fas fa-credit-card"></i>
                                            <span>Add Payment</span>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>admin/reports.php" class="quick-action-btn">
                                            <i class="fas fa-chart-bar"></i>
                                            <span>View Reports</span>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>admin/qr_scanner.php" class="quick-action-btn">
                                            <i class="fas fa-qrcode"></i>
                                            <span>QR Scanner</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($user_role == 'trainer'): ?>
                        <div class="col-md-6 mb-4">
                            <div class="stats-card bg-primary slide-up">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="card-title">Assigned Members</h5>
                                            <h2><?php echo $stats['members']; ?></h2>
                                            <p class="mb-0 mt-2" style="font-size: 0.875rem;">
                                                <i class="fas fa-users me-1"></i> Under your guidance
                                            </p>
                                        </div>
                                        <div class="icon"><i class="fas fa-users"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="stats-card bg-warning slide-up" style="animation-delay: 0.1s;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="card-title">Today's Sessions</h5>
                                            <h2><?php echo $stats['sessions']; ?></h2>
                                            <p class="mb-0 mt-2" style="font-size: 0.875rem;">
                                                <i class="fas fa-dumbbell me-1"></i> Training sessions
                                            </p>
                                        </div>
                                        <div class="icon"><i class="fas fa-clock"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions for Trainer -->
                        <div class="col-12 mb-4">
                            <div class="card-modern slide-up" style="animation-delay: 0.2s;">
                                <div class="card-header">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </div>
                                <div class="card-body">
                                    <div class="quick-actions-grid">
                                        <a href="<?php echo SITE_URL; ?>trainer/index.php" class="quick-action-btn">
                                            <i class="fas fa-users"></i>
                                            <span>My Members</span>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>trainer/plans.php" class="quick-action-btn">
                                            <i class="fas fa-clipboard-list"></i>
                                            <span>Training Plans</span>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>trainer/attendance.php" class="quick-action-btn">
                                            <i class="fas fa-calendar-check"></i>
                                            <span>Attendance</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($user_role == 'member'): ?>
                        <div class="col-md-6 mb-4">
                            <div class="stats-card bg-primary slide-up">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="card-title">Membership Status</h5>
                                            <h2><?php echo ucfirst($stats['status']); ?></h2>
                                            <p class="mb-0 mt-2" style="font-size: 0.875rem;">
                                                <i class="fas fa-check-circle me-1"></i> Current status
                                            </p>
                                        </div>
                                        <div class="icon"><i class="fas fa-id-card"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="stats-card bg-secondary slide-up" style="animation-delay: 0.1s;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="card-title">This Month's Attendance</h5>
                                            <h2><?php echo $stats['attendance']; ?> days</h2>
                                            <p class="mb-0 mt-2" style="font-size: 0.875rem;">
                                                <i class="fas fa-fire me-1"></i> Keep it up!
                                            </p>
                                        </div>
                                        <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions for Member -->
                        <div class="col-12 mb-4">
                            <div class="card-modern slide-up" style="animation-delay: 0.2s;">
                                <div class="card-header">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </div>
                                <div class="card-body">
                                    <div class="quick-actions-grid">
                                        <a href="<?php echo SITE_URL; ?>member/attendance.php" class="quick-action-btn">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span>My Attendance</span>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>member/workouts.php" class="quick-action-btn">
                                            <i class="fas fa-dumbbell"></i>
                                            <span>Workout Plans</span>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>member/diets.php" class="quick-action-btn">
                                            <i class="fas fa-utensils"></i>
                                            <span>Diet Plans</span>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>member/classes.php" class="quick-action-btn">
                                            <i class="fas fa-users"></i>
                                            <span>Group Classes</span>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>member/profile.php" class="quick-action-btn">
                                            <i class="fas fa-user-circle"></i>
                                            <span>My Profile</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
    </div>

</body>
</html>