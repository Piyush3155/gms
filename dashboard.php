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
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <?php if ($user_role == 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                Management
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="admin/members.php">Members</a></li>
                                <li><a class="dropdown-item" href="admin/trainers.php">Trainers</a></li>
                                <li><a class="dropdown-item" href="admin/plans.php">Plans</a></li>
                                <li><a class="dropdown-item" href="admin/attendance.php">Attendance</a></li>
                                <li><a class="dropdown-item" href="admin/payments.php">Payments</a></li>
                                <li><a class="dropdown-item" href="admin/expenses.php">Expenses</a></li>
                                <li><a class="dropdown-item" href="admin/reports.php">Reports</a></li>
                                <li><a class="dropdown-item" href="admin/settings.php">Settings</a></li>
                            </ul>
                        </li>
                    <?php elseif ($user_role == 'trainer'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="trainer/members.php">My Members</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="trainer/attendance.php">Attendance</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="trainer/plans.php">Plans</a>
                        </li>
                    <?php elseif ($user_role == 'member'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="member/profile.php">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="member/attendance.php">Attendance</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="member/plans.php">My Plans</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo $user_name; ?> (<?php echo ucfirst($user_role); ?>)
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Welcome to <?php echo SITE_NAME; ?>, <?php echo $user_name; ?>!</h1>

        <div class="row mt-4">
            <?php if ($user_role == 'admin'): ?>
                <div class="col-md-3 mb-4">
                    <div class="stats-card text-white">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-users me-2"></i>Total Members</h5>
                            <h2><?php echo $stats['members']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stats-card text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-user-tie me-2"></i>Total Trainers</h5>
                            <h2><?php echo $stats['trainers']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stats-card text-white" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-calendar-check me-2"></i>Today's Attendance</h5>
                            <h2><?php echo $stats['attendance']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stats-card text-white" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-dollar-sign me-2"></i>Monthly Revenue</h5>
                            <h2>$<?php echo number_format($stats['revenue'], 2); ?></h2>
                        </div>
                    </div>
                </div>
            <?php elseif ($user_role == 'trainer'): ?>
                <div class="col-md-6 mb-4">
                    <div class="stats-card text-white">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-users me-2"></i>Assigned Members</h5>
                            <h2><?php echo $stats['members']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="stats-card text-white" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-clock me-2"></i>Today's Sessions</h5>
                            <h2><?php echo $stats['sessions']; ?></h2>
                        </div>
                    </div>
                </div>
            <?php elseif ($user_role == 'member'): ?>
                <div class="col-md-6 mb-4">
                    <div class="stats-card text-white">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-id-card me-2"></i>Membership Status</h5>
                            <h2><?php echo ucfirst($stats['status']); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="stats-card text-white" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-calendar-alt me-2"></i>This Month's Attendance</h5>
                            <h2><?php echo $stats['attendance']; ?> days</h2>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>