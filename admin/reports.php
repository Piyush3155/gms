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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="../assets/css/components.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css?v=1.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="page-header">
    <h1 class="page-title">Reports & Analytics</h1>
</div>

<!-- Key Metrics -->
<div class="row g-4 mb-4">
    <?php
    $total_members = $conn->query("SELECT COUNT(*) as count FROM members")->fetch_assoc()['count'];
    $active_members = $conn->query("SELECT COUNT(*) as count FROM members WHERE status='active'")->fetch_assoc()['count'];
    $total_revenue_query = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status='paid'");
    $total_revenue = $total_revenue_query ? $total_revenue_query->fetch_assoc()['total'] ?? 0 : 0;
    $available_equipment = $conn->query("SELECT COUNT(*) as count FROM equipment WHERE status='available'")->fetch_assoc()['count'];
    ?>
    <div class="col-lg-3 col-md-6">
        <div class="feature-card">
            <div class="card-icon bg-primary-light text-primary"><i class="bi bi-people-fill"></i></div>
            <div class="card-content">
                <h3 class="card-title"><?php echo $total_members; ?></h3>
                <p class="card-text">Total Members</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="feature-card">
            <div class="card-icon bg-success-light text-success"><i class="bi bi-person-check-fill"></i></div>
            <div class="card-content">
                <h3 class="card-title"><?php echo $active_members; ?></h3>
                <p class="card-text">Active Members</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="feature-card">
            <div class="card-icon bg-info-light text-info"><i class="bi bi-cash-stack"></i></div>
            <div class="card-content">
                <h3 class="card-title">₹<?php echo number_format($total_revenue, 2); ?></h3>
                <p class="card-text">Total Revenue</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="feature-card">
            <div class="card-icon bg-warning-light text-warning"><i class="bi bi-tools"></i></div>
            <div class="card-content">
                <h3 class="card-title"><?php echo $available_equipment; ?></h3>
                <p class="card-text">Available Equipment</p>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card-modern">
            <div class="card-header">
                <h5 class="card-title mb-0">Member Growth (Last 12 Months)</h5>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="memberGrowthChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-modern">
            <div class="card-header">
                <h5 class="card-title mb-0">Membership Status</h5>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Member Growth Data Table -->
<div class="card-modern mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Member Growth Data</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="memberGrowthTable" class="table table-modern">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>New Members</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($member_growth_data as $data): ?>
                    <tr>
                        <td><?php echo date("F Y", strtotime($data['month'] . '-01')); ?></td>
                        <td><?php echo $data['count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- Charts Row 2 -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card-modern">
            <div class="card-header">
                <h5 class="card-title mb-0">Revenue vs Expenses (Last 6 Months)</h5>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="revenueExpenseChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-modern">
            <div class="card-header">
                <h5 class="card-title mb-0">Popular Classes</h5>
            </div>
            <div class="card-body">
                <table id="popularClassesTable" class="table table-modern">
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Bookings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_classes_data as $class): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($class['name']); ?></td>
                                <td><span class="badge bg-primary"><?php echo $class['booking_count']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 3 -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card-modern">
            <div class="card-header">
                <h5 class="card-title mb-0">Trainer Performance (Members Assigned)</h5>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="trainerChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-modern">
            <div class="card-header">
                <h5 class="card-title mb-0">Equipment Status</h5>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="equipmentChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/chartConfig.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Member Growth Chart
    const memberGrowthCtx = document.getElementById('memberGrowthChart')?.getContext('2d');
    if (memberGrowthCtx) {
        const memberGrowthData = <?php echo json_encode($member_growth_data); ?>;
        new Chart(memberGrowthCtx, {
            type: 'line',
            data: {
                labels: memberGrowthData.map(item => formatChartMonth(item.month)),
                datasets: [{
                    label: 'New Members',
                    data: memberGrowthData.map(item => item.count),
                    borderColor: GMS_COLORS.primary,
                    backgroundColor: GMS_COLORS.primary_light,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: lineChartOptions
        });
    }

    // Revenue vs Expense Chart
    const revenueExpenseCtx = document.getElementById('revenueExpenseChart')?.getContext('2d');
    if (revenueExpenseCtx) {
        const revenueExpenseData = <?php echo json_encode($revenue_expense_data); ?>;
        new Chart(revenueExpenseCtx, {
            type: 'bar',
            data: {
                labels: revenueExpenseData.map(item => formatChartMonth(item.month)),
                datasets: [{
                    label: 'Revenue',
                    data: revenueExpenseData.map(item => item.revenue),
                    backgroundColor: GMS_COLORS.success
                }, {
                    label: 'Expenses',
                    data: revenueExpenseData.map(item => item.expense),
                    backgroundColor: GMS_COLORS.danger
                }]
            },
            options: barChartOptions
        });
    }

    // Status Distribution Chart
    const statusCtx = document.getElementById('statusChart')?.getContext('2d');
    if (statusCtx) {
        const statusData = <?php echo json_encode($status_data); ?>;
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1)),
                datasets: [{
                    data: statusData.map(item => item.count),
                    backgroundColor: [GMS_COLORS.success, GMS_COLORS.danger, GMS_COLORS.warning, GMS_COLORS.info]
                }]
            },
            options: doughnutChartOptions
        });
    }

    // Trainer Performance Chart
    const trainerCtx = document.getElementById('trainerChart')?.getContext('2d');
    if (trainerCtx) {
        const trainerData = <?php echo json_encode($trainer_data); ?>;
        new Chart(trainerCtx, {
            type: 'bar',
            data: {
                labels: trainerData.map(item => item.name),
                datasets: [{
                    label: 'Members Assigned',
                    data: trainerData.map(item => item.member_count),
                    backgroundColor: GMS_COLORS.primary
                }]
            },
            options: { ...barChartOptions, indexAxis: 'y', plugins: { legend: { display: false } } }
        });
    }

    // Equipment Status Chart
    const equipmentCtx = document.getElementById('equipmentChart')?.getContext('2d');
    if (equipmentCtx) {
        const equipmentData = <?php echo json_encode($equipment_data); ?>;
        new Chart(equipmentCtx, {
            type: 'pie',
            data: {
                labels: equipmentData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1).replace('_', ' ')),
                datasets: [{
                    data: equipmentData.map(item => item.count),
                    backgroundColor: [GMS_COLORS.success, GMS_COLORS.warning, GMS_COLORS.danger, GMS_COLORS.info]
                }]
            },
            options: { ...doughnutChartOptions, cutout: '0%' }
        });
    }

    // Initialize DataTables
    const memberGrowthTable = document.getElementById('memberGrowthTable');
    if (memberGrowthTable) {
        new DataTable(memberGrowthTable, {
            exportable: true,
            exportOptions: {
                fileName: 'Member-Growth-Report'
            },
            paging: true,
            searchable: true
        });
    }

    const popularClassesTable = document.getElementById('popularClassesTable');
    if (popularClassesTable) {
        new DataTable(popularClassesTable, {
            paging: false,
            info: false,
            searching: false
        });
    }
});
</script>
</body>
</html>