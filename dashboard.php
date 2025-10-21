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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="sidebar-open">
    <div class="main-wrapper">
    <?php include 'includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
            <div class="fade-in">
                <h1 class="display-6">Welcome back, <?php echo $user_name; ?>!</h1>
                <p class="text-muted">Here's a snapshot of your gym's activity today.</p>

                <div class="row mt-4">
                    <?php if ($user_role == 'admin'): ?>
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="stats-card bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Total Members</h5>
                                    <h2><?php echo $stats['members']; ?></h2>
                                    <div class="icon"><i class="fas fa-users"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="stats-card bg-danger">
                                <div class="card-body">
                                    <h5 class="card-title">Total Trainers</h5>
                                    <h2><?php echo $stats['trainers']; ?></h2>
                                    <div class="icon"><i class="fas fa-user-tie"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="stats-card bg-info">
                                <div class="card-body">
                                    <h5 class="card-title">Today's Attendance</h5>
                                    <h2><?php echo $stats['attendance']; ?></h2>
                                    <div class="icon"><i class="fas fa-calendar-check"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="stats-card bg-success">
                                <div class="card-body">
                                    <h5 class="card-title">Monthly Revenue</h5>
                                    <h2>₹<?php echo number_format($stats['revenue'], 2); ?></h2>
                                    <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($user_role == 'trainer'): ?>
                        <div class="col-md-6 mb-4">
                            <div class="stats-card bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Assigned Members</h5>
                                    <h2><?php echo $stats['members']; ?></h2>
                                    <div class="icon"><i class="fas fa-users"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="stats-card bg-warning">
                                <div class="card-body">
                                    <h5 class="card-title">Today's Sessions</h5>
                                    <h2><?php echo $stats['sessions']; ?></h2>
                                    <div class="icon"><i class="fas fa-clock"></i></div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($user_role == 'member'): ?>
                        <div class="col-md-6 mb-4">
                            <div class="stats-card bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Membership Status</h5>
                                    <h2><?php echo ucfirst($stats['status']); ?></h2>
                                    <div class="icon"><i class="fas fa-id-card"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="stats-card bg-secondary">
                                <div class="card-body">
                                    <h5 class="card-title">This Month's Attendance</h5>
                                    <h2><?php echo $stats['attendance']; ?> days</h2>
                                    <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    </div>

</body>
</html>