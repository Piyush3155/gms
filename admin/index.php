<?php
require_once '../includes/config.php';
require_permission('dashboard', 'view');

// Get dashboard statistics
$stats = [];

// Total members
$result = $conn->query("SELECT COUNT(*) as total FROM members");
$stats['members'] = $result->fetch_assoc()['total'];

// Active members
$result = $conn->query("SELECT COUNT(*) as total FROM members WHERE status = 'active'");
$stats['active_members'] = $result->fetch_assoc()['total'];

// Total trainers
$result = $conn->query("SELECT COUNT(*) as total FROM trainers");
$stats['trainers'] = $result->fetch_assoc()['total'];

// Today's attendance
$today = date('Y-m-d');
$result = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE date = '$today'");
$stats['today_attendance'] = $result->fetch_assoc()['total'];

// Monthly revenue
$current_month = date('Y-m');
$result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = '$current_month'");
$stats['monthly_revenue'] = $result->fetch_assoc()['total'] ?? 0;

// Pending payments
$result = $conn->query("SELECT COUNT(*) as total FROM payments WHERE status = 'pending'");
$stats['pending_payments'] = $result->fetch_assoc()['total'];

// Low stock items
$result = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE quantity <= 10");
$stats['low_stock'] = $result->fetch_assoc()['total'];

// Get notifications/alerts
$alerts = [];

// Expiring memberships (next 7 days)
$expiry_date = date('Y-m-d', strtotime('+7 days'));
$result = $conn->query("SELECT COUNT(*) as total FROM members WHERE expiry_date <= '$expiry_date' AND status = 'active'");
$expiring_count = $result->fetch_assoc()['total'];
if ($expiring_count > 0) {
    $alerts[] = [
        'type' => 'warning',
        'icon' => 'fas fa-exclamation-triangle',
        'title' => 'Membership Expiry Alert',
        'message' => "$expiring_count memberships expiring within 7 days",
        'link' => 'expiry_alerts.php'
    ];
}

// Pending feedback
$result = $conn->query("SELECT COUNT(*) as total FROM feedback WHERE status = 'pending'");
$pending_feedback = $result->fetch_assoc()['total'];
if ($pending_feedback > 0) {
    $alerts[] = [
        'type' => 'info',
        'icon' => 'fas fa-comments',
        'title' => 'Pending Feedback',
        'message' => "$pending_feedback feedback items awaiting review",
        'link' => 'feedback.php'
    ];
}

// Equipment maintenance due
$result = $conn->query("SELECT COUNT(*) as total FROM equipment WHERE next_maintenance <= CURDATE() AND status != 'maintenance'");
$maintenance_count = $result->fetch_assoc()['total'];
if ($maintenance_count > 0) {
    $alerts[] = [
        'type' => 'danger',
        'icon' => 'fas fa-tools',
        'title' => 'Equipment Maintenance',
        'message' => "$maintenance_count equipment items due for maintenance",
        'link' => 'equipment.php'
    ];
}

// Recent activities
$recent_activities = $conn->query("SELECT a.*, u.name as user_name FROM activity_log a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
            <div>
                <h2 class="mb-1">Admin Dashboard</h2>
                <p class="text-muted mb-0">Welcome back, <strong><?php echo $_SESSION['user_name']; ?></strong>! Here's what's happening today.</p>
            </div>
            <div class="text-muted">
                <i class="fas fa-calendar-alt me-2"></i><?php echo date('F d, Y'); ?>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <div class="info-card fade-in" style="animation-delay: 0.1s;">
                    <div class="info-card-top">
                        <div class="info-card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="info-card-content">
                            <div class="info-card-title">Total Members</div>
                            <h2 class="info-card-value"><?php echo $stats['members']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <div class="info-card fade-in" style="animation-delay: 0.2s;">
                    <div class="info-card-top">
                        <div class="info-card-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="info-card-content">
                            <div class="info-card-title">Active Members</div>
                            <h2 class="info-card-value"><?php echo $stats['active_members']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <div class="info-card fade-in" style="animation-delay: 0.3s;">
                    <div class="info-card-top">
                        <div class="info-card-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="info-card-content">
                            <div class="info-card-title">Trainers</div>
                            <h2 class="info-card-value"><?php echo $stats['trainers']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <div class="info-card fade-in" style="animation-delay: 0.4s;">
                    <div class="info-card-top">
                        <div class="info-card-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="info-card-content">
                            <div class="info-card-title">Today's Attendance</div>
                            <h2 class="info-card-value"><?php echo $stats['today_attendance']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <div class="info-card fade-in" style="animation-delay: 0.5s;">
                    <div class="info-card-top">
                        <div class="info-card-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div class="info-card-content">
                            <div class="info-card-title">Monthly Revenue</div>
                            <h2 class="info-card-value">₹<?php echo number_format($stats['monthly_revenue'], 0); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <div class="info-card fade-in" style="animation-delay: 0.6s;">
                    <div class="info-card-top">
                        <div class="info-card-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="info-card-content">
                            <div class="info-card-title">Pending Payments</div>
                            <h2 class="info-card-value"><?php echo $stats['pending_payments']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <div class="info-card fade-in" style="animation-delay: 0.7s;">
                    <div class="info-card-top">
                        <div class="info-card-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="info-card-content">
                            <div class="info-card-title">Low Stock Items</div>
                            <h2 class="info-card-value"><?php echo $stats['low_stock']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts/Notifications -->
        <?php if (!empty($alerts)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-modern fade-in">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Important Notifications</h5>
                        <button class="btn btn-sm btn-outline-light" onclick="dismissAllNotifications()">
                            <i class="fas fa-times me-1"></i>Dismiss All
                        </button>
                    </div>
                    <div class="card-body">
                        <?php foreach ($alerts as $index => $alert): ?>
                            <div class="alert alert-<?php echo $alert['type']; ?> alert-modern d-flex align-items-center alert-dismissible fade show" id="alert-<?php echo $index; ?>">
                                <i class="<?php echo $alert['icon']; ?>"></i>
                                <div class="flex-grow-1">
                                    <strong><?php echo $alert['title']; ?>:</strong> <?php echo $alert['message']; ?>
                                </div>
                                <a href="<?php echo $alert['link']; ?>" class="btn btn-sm btn-primary me-2">
                                    <i class="fas fa-eye me-1"></i>View
                                </a>
                                <button type="button" class="btn-close" onclick="dismissNotification(<?php echo $index; ?>)"></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-md-6">
                <div class="card card-modern fade-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                                <div class="list-group-item px-0 border-0">
                                    <div class="d-flex w-100 justify-content-between mb-1">
                                        <small class="text-muted fw-semibold"><?php echo $activity['user_name'] ?? 'System'; ?></small>
                                        <small class="text-muted"><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo $activity['action']; ?></p>
                                    <?php if ($activity['module']): ?>
                                        <small class="badge badge-status badge-inactive"><?php echo ucfirst($activity['module']); ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-modern fade-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions-grid">
                            <a href="members.php" class="quick-action-btn">
                                <i class="fas fa-user-plus"></i>
                                <span>Add Member</span>
                            </a>
                            <a href="attendance.php" class="quick-action-btn">
                                <i class="fas fa-calendar-check"></i>
                                <span>Mark Attendance</span>
                            </a>
                            <a href="payments.php" class="quick-action-btn">
                                <i class="fas fa-dollar-sign"></i>
                                <span>Record Payment</span>
                            </a>
                            <a href="reports.php" class="quick-action-btn">
                                <i class="fas fa-chart-bar"></i>
                                <span>View Reports</span>
                            </a>
                            <a href="equipment.php" class="quick-action-btn">
                                <i class="fas fa-dumbbell"></i>
                                <span>Equipment</span>
                            </a>
                            <a href="settings.php" class="quick-action-btn">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function dismissNotification(index) {
            const alertElement = document.getElementById('alert-' + index);
            if (alertElement) {
                alertElement.style.display = 'none';
                // Here you could add AJAX call to mark notification as read in database
            }
        }

        function dismissAllNotifications() {
            const alerts = document.querySelectorAll('[id^="alert-"]');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
            // Here you could add AJAX call to mark all notifications as read
        }

        // Auto-refresh dashboard data every 5 minutes
        setInterval(function() {
            // You could add AJAX calls here to refresh statistics
            console.log('Dashboard refresh check...');
        }, 300000);
    </script>
</body>
</html>