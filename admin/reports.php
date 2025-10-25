<?php
require_once '../includes/config.php';
require_role('admin');

// Get member growth data (monthly)
$member_growth = $conn->query("
    SELECT DATE_FORMAT(join_date, '%Y-%m') as month, COUNT(*) as count
    FROM members
    WHERE join_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(join_date, '%Y-%m')
    ORDER BY month
");

// Get revenue vs expense data (monthly)
$revenue_expense = $conn->query("
    SELECT
        DATE_FORMAT(date, '%Y-%m') as month,
        SUM(CASE WHEN type = 'revenue' THEN amount ELSE 0 END) as revenue,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
    FROM (
        SELECT payment_date as date, amount, 'revenue' as type FROM payments WHERE status = 'paid'
        UNION ALL
        SELECT expense_date as date, amount, 'expense' as type FROM expenses
    ) combined
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date, '%Y-%m')
    ORDER BY month
");

// Get attendance summary
$attendance_summary = $conn->query("
    SELECT
        DATE_FORMAT(date, '%Y-%m') as month,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
    FROM attendance
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date, '%Y-%m')
    ORDER BY month
");

// Get membership status distribution
$membership_status = $conn->query("
    SELECT status, COUNT(*) as count
    FROM members
    GROUP BY status
");

// Get trainer performance (members per trainer)
$trainer_performance = $conn->query("
    SELECT t.name, COUNT(m.id) as member_count
    FROM trainers t
    LEFT JOIN members m ON t.id = m.trainer_id
    GROUP BY t.id, t.name
    ORDER BY member_count DESC
");

// Get equipment status distribution
$equipment_status = $conn->query("
    SELECT status, COUNT(*) as count
    FROM equipment
    GROUP BY status
");

// Get class booking statistics
$class_bookings = $conn->query("
    SELECT DATE_FORMAT(gc.class_date, '%Y-%m') as month, COUNT(cb.id) as bookings
    FROM group_classes gc
    LEFT JOIN class_bookings cb ON gc.id = cb.class_id AND cb.status IN ('confirmed', 'attended')
    WHERE gc.class_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(gc.class_date, '%Y-%m')
    ORDER BY month
");

// Get member progress statistics (average weight change)
$progress_stats = $conn->query("
    SELECT
        AVG(CASE WHEN measurement_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN weight END) -
        AVG(CASE WHEN measurement_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) AND measurement_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN weight END) as avg_weight_change
    FROM member_progress
    WHERE weight IS NOT NULL
");

// Get popular classes
$popular_classes = $conn->query("
    SELECT gc.name, COUNT(cb.id) as booking_count
    FROM group_classes gc
    LEFT JOIN class_bookings cb ON gc.id = cb.class_id AND cb.status IN ('confirmed', 'attended')
    GROUP BY gc.id, gc.name
    ORDER BY booking_count DESC
    LIMIT 5
");

// Convert data to JSON for JavaScript
$member_growth_data = [];
while ($row = $member_growth->fetch_assoc()) {
    $member_growth_data[] = $row;
}

$revenue_expense_data = [];
while ($row = $revenue_expense->fetch_assoc()) {
    $revenue_expense_data[] = $row;
}

$attendance_data = [];
while ($row = $attendance_summary->fetch_assoc()) {
    $attendance_data[] = $row;
}

$status_data = [];
while ($row = $membership_status->fetch_assoc()) {
    $status_data[] = $row;
}

$trainer_data = [];
while ($row = $trainer_performance->fetch_assoc()) {
    $trainer_data[] = $row;
}

$equipment_data = [];
while ($row = $equipment_status->fetch_assoc()) {
    $equipment_data[] = $row;
}

$class_booking_data = [];
while ($row = $class_bookings->fetch_assoc()) {
    $class_booking_data[] = $row;
}

$popular_classes_data = [];
while ($row = $popular_classes->fetch_assoc()) {
    $popular_classes_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <h2 class="mb-4"><i class="fas fa-chart-line me-2"></i>Reports & Analytics</h2>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <?php
            $total_members = $conn->query("SELECT COUNT(*) as count FROM members")->fetch_assoc()['count'];
            $active_members = $conn->query("SELECT COUNT(*) as count FROM members WHERE status='active'")->fetch_assoc()['count'];
            $total_trainers = $conn->query("SELECT COUNT(*) as count FROM trainers")->fetch_assoc()['count'];
            $total_revenue = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status='paid'")->fetch_assoc()['total'] ?? 0;
            $total_equipment = $conn->query("SELECT COUNT(*) as count FROM equipment")->fetch_assoc()['count'];
            $available_equipment = $conn->query("SELECT COUNT(*) as count FROM equipment WHERE status='available'")->fetch_assoc()['count'];
            $total_classes = $conn->query("SELECT COUNT(*) as count FROM group_classes WHERE class_date >= CURDATE()")->fetch_assoc()['count'];
            $total_bookings = $conn->query("SELECT COUNT(*) as count FROM class_bookings WHERE status IN ('confirmed', 'attended')")->fetch_assoc()['count'];
            ?>
            <div class="col-md-3">
                <div class="card text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users me-2"></i>Total Members</h5>
                        <h2><?php echo $total_members; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user-check me-2"></i>Active Members</h5>
                        <h2><?php echo $active_members; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-tools me-2"></i>Equipment</h5>
                        <h2><?php echo $available_equipment; ?>/<?php echo $total_equipment; ?></h2>
                        <small>Available</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-calendar-alt me-2"></i>Class Bookings</h5>
                        <h2><?php echo $total_bookings; ?></h2>
                        <small>This Month</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Member Growth (Last 12 Months)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="memberGrowthChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i>Revenue vs Expenses (Last 6 Months)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueExpenseChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Attendance Summary (Last 6 Months)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="attendanceChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Membership Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trainer Performance -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Equipment Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="equipmentChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Class Bookings (Last 6 Months)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="classBookingChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Trainer Performance</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="trainerChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-star me-2"></i>Popular Classes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Class Name</th>
                                        <th>Total Bookings</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($popular_classes_data as $class): ?>
                                        <tr>
                                            <td><?php echo $class['name']; ?></td>
                                            <td><span class="badge bg-primary"><?php echo $class['booking_count']; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Member Growth Chart
        const memberGrowthCtx = document.getElementById('memberGrowthChart').getContext('2d');
        const memberGrowthData = <?php echo json_encode($member_growth_data); ?>;
        new Chart(memberGrowthCtx, {
            type: 'line',
            data: {
                labels: memberGrowthData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'New Members',
                    data: memberGrowthData.map(item => item.count),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Revenue vs Expense Chart
        const revenueExpenseCtx = document.getElementById('revenueExpenseChart').getContext('2d');
        const revenueExpenseData = <?php echo json_encode($revenue_expense_data); ?>;
        new Chart(revenueExpenseCtx, {
            type: 'bar',
            data: {
                labels: revenueExpenseData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Revenue',
                    data: revenueExpenseData.map(item => item.revenue),
                    backgroundColor: '#43e97b'
                }, {
                    label: 'Expenses',
                    data: revenueExpenseData.map(item => item.expense),
                    backgroundColor: '#f5576c'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Attendance Chart
        const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
        const attendanceData = <?php echo json_encode($attendance_data); ?>;
        new Chart(attendanceCtx, {
            type: 'bar',
            data: {
                labels: attendanceData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Present',
                    data: attendanceData.map(item => item.present),
                    backgroundColor: '#4facfe'
                }, {
                    label: 'Absent',
                    data: attendanceData.map(item => item.absent),
                    backgroundColor: '#f093fb'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusData = <?php echo json_encode($status_data); ?>;
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1)),
                datasets: [{
                    data: statusData.map(item => item.count),
                    backgroundColor: ['#43e97b', '#f5576c', '#f093fb', '#4facfe']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // Trainer Performance Chart
        const trainerCtx = document.getElementById('trainerChart').getContext('2d');
        const trainerData = <?php echo json_encode($trainer_data); ?>;
        new Chart(trainerCtx, {
            type: 'bar',
            data: {
                labels: trainerData.map(item => item.name),
                datasets: [{
                    label: 'Members',
                    data: trainerData.map(item => item.member_count),
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Equipment Status Chart
        const equipmentCtx = document.getElementById('equipmentChart').getContext('2d');
        const equipmentData = <?php echo json_encode($equipment_data); ?>;
        new Chart(equipmentCtx, {
            type: 'doughnut',
            data: {
                labels: equipmentData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1).replace('_', ' ')),
                datasets: [{
                    data: equipmentData.map(item => item.count),
                    backgroundColor: ['#43e97b', '#f5576c', '#f093fb', '#4facfe', '#667eea']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // Class Booking Chart
        const classBookingCtx = document.getElementById('classBookingChart').getContext('2d');
        const classBookingData = <?php echo json_encode($class_booking_data); ?>;
        new Chart(classBookingCtx, {
            type: 'line',
            data: {
                labels: classBookingData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Class Bookings',
                    data: classBookingData.map(item => item.bookings),
                    borderColor: '#f093fb',
                    backgroundColor: 'rgba(240, 147, 251, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
        </div>
    </div>
    </div>
</body>
</html>