<?php
require_once '../includes/config.php';
require_role('member');

$user_id = $_SESSION['user_id'];

// Get attendance history
$attendance_history = $conn->query("SELECT * FROM attendance WHERE user_id = $user_id ORDER BY date DESC LIMIT 30");

// Get monthly attendance summary
$this_month = date('Y-m');
$monthly_present = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE user_id = $user_id AND DATE_FORMAT(date, '%Y-%m') = '$this_month' AND status = 'present'")->fetch_assoc()['count'];
$monthly_total = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE user_id = $user_id AND DATE_FORMAT(date, '%Y-%m') = '$this_month'")->fetch_assoc()['count'];

// Get attendance by month for the last 6 months
$monthly_stats = $conn->query("
    SELECT
        DATE_FORMAT(date, '%Y-%m') as month,
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days
    FROM attendance
    WHERE user_id = $user_id AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date, '%Y-%m')
    ORDER BY month DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - <?php echo SITE_NAME; ?></title>
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
        <h2><i class="fas fa-calendar-check me-2"></i>My Attendance History</h2>

        <!-- Attendance Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-calendar-alt me-2"></i>This Month</h5>
                        <h2><?php echo $monthly_present; ?>/<?php echo $monthly_total; ?></h2>
                        <small>Days Present</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-percentage me-2"></i>Attendance Rate</h5>
                        <h2><?php echo $monthly_total > 0 ? round(($monthly_present / $monthly_total) * 100) : 0; ?>%</h2>
                        <small>This Month</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-trophy me-2"></i>Best Month</h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Attendance Chart -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Monthly Attendance Overview</h5>
            </div>
            <div class="card-body">
                <canvas id="attendanceChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Attendance History Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Detailed Attendance History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Status</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($record = $attendance_history->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                    <td><?php echo date('l', strtotime($record['date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $record['status'] == 'present' ? 'success' : 'danger'; ?>">
                                            <i class="fas fa-<?php echo $record['status'] == 'present' ? 'check' : 'times'; ?> me-1"></i>
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $record['check_in'] ?: '-'; ?></td>
                                    <td><?php echo $record['check_out'] ?: '-'; ?></td>
                                    <td>
                                        <?php
                                        if ($record['check_in'] && $record['check_out']) {
                                            $check_in = strtotime($record['check_in']);
                                            $check_out = strtotime($record['check_out']);
                                            $duration = ($check_out - $check_in) / 3600; // hours
                                            echo round($duration, 1) . ' hours';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Monthly Attendance Chart
        const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
        const attendanceData = <?php echo json_encode($monthly_stats->fetch_all(MYSQLI_ASSOC)); ?>;
        new Chart(attendanceCtx, {
            type: 'bar',
            data: {
                labels: attendanceData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }).reverse(),
                datasets: [{
                    label: 'Present Days',
                    data: attendanceData.map(item => item.present_days).reverse(),
                    backgroundColor: '#43e97b',
                    borderColor: '#43e97b',
                    borderWidth: 1
                }, {
                    label: 'Total Days',
                    data: attendanceData.map(item => item.total_days).reverse(),
                    backgroundColor: 'rgba(102, 126, 234, 0.3)',
                    borderColor: '#667eea',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                },
                plugins: {
                    legend: { display: true }
                }
            }
        });
    </script>
    </div>
    </div>
</body>
</html>