<?php
require_once '../includes/config.php';
require_role('admin');

// Handle attendance marking
if (isset($_POST['mark_attendance'])) {
    $date = trim($_POST['date']);
    $users = $_POST['users'] ?? [];
    
    if (!empty($users) && !empty($date)) {
        foreach ($users as $user_id => $data) {
            $user_id = intval($user_id);
            $role = trim($data['role']);
            $status = trim($data['status']);
            $check_in = !empty($data['check_in']) ? trim($data['check_in']) : null;
            $check_out = !empty($data['check_out']) ? trim($data['check_out']) : null;

            // Check if attendance already exists
            $stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND date = ?");
            $stmt->bind_param("is", $user_id, $date);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Update existing
                $stmt = $conn->prepare("UPDATE attendance SET check_in=?, check_out=?, status=? WHERE user_id=? AND date=?");
                $stmt->bind_param("sssis", $check_in, $check_out, $status, $user_id, $date);
            } else {
                // Insert new
                $stmt = $conn->prepare("INSERT INTO attendance (user_id, role, date, check_in, check_out, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $user_id, $role, $date, $check_in, $check_out, $status);
            }

            $stmt->execute();
            $stmt->close();
        }
        
        log_activity("Updated attendance records", "attendance", "Date: $date");
    }
    
    header("Location: attendance.php?date=" . urlencode($date));
    exit();
}

// Get selected date
$selected_date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');

// Get attendance statistics
$attendance_stats = [];

// Today's stats
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM attendance WHERE date = ? AND status = 'present'");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$attendance_stats['present_today'] = $result->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM attendance WHERE date = ? AND status = 'absent'");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$attendance_stats['absent_today'] = $result->fetch_assoc()['count'];
$stmt->close();

// Total expected (all active members)
$result = $conn->query("SELECT COUNT(*) as count FROM members WHERE status = 'active'");
$attendance_stats['total_expected'] = $result->fetch_assoc()['count'];

// Attendance rate today
$attendance_stats['attendance_rate'] = $attendance_stats['total_expected'] > 0 ? 
    round(($attendance_stats['present_today'] / $attendance_stats['total_expected']) * 100, 1) : 0;

// Average monthly attendance
$current_month = date('Y-m');
$stmt = $conn->prepare("SELECT AVG(daily_present) as avg_attendance FROM (
    SELECT COUNT(*) as daily_present 
    FROM attendance 
    WHERE status = 'present' AND DATE_FORMAT(date, '%Y-%m') = ?
    GROUP BY date
) as daily_stats");
$stmt->bind_param("s", $current_month);
$stmt->execute();
$result = $stmt->get_result();
$attendance_stats['monthly_avg'] = round($result->fetch_assoc()['avg_attendance'] ?? 0, 1);
$stmt->close();

// Peak attendance day this month
$stmt = $conn->prepare("SELECT MAX(daily_present) as peak FROM (
    SELECT COUNT(*) as daily_present 
    FROM attendance 
    WHERE status = 'present' AND DATE_FORMAT(date, '%Y-%m') = ?
    GROUP BY date
) as daily_stats");
$stmt->bind_param("s", $current_month);
$stmt->execute();
$result = $stmt->get_result();
$attendance_stats['monthly_peak'] = $result->fetch_assoc()['peak'] ?? 0;
$stmt->close();

// Get all users (members and trainers)
$users = $conn->query("SELECT id, name, 'member' as role FROM members WHERE status = 'active' UNION SELECT id, name, 'trainer' as role FROM trainers ORDER BY name");

// Get attendance for selected date and convert to associative array
$attendance_query = $conn->prepare("SELECT * FROM attendance WHERE date = ?");
$attendance_query->bind_param("s", $selected_date);
$attendance_query->execute();
$attendance_result = $attendance_query->get_result();

$attendance_records = [];
while ($record = $attendance_result->fetch_assoc()) {
    $attendance_records[$record['user_id']] = $record;
}

// Legacy variables for backward compatibility
$present_count = $attendance_stats['present_today'];
$absent_count = $attendance_stats['absent_today'];
$total_expected = $attendance_stats['total_expected'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Tracking - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="../assets/css/components.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css?v=1.0">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="page-header">
    <h1 class="page-title">Attendance Tracking</h1>
    <div class="page-options d-flex">
        <input type="date" class="form-control me-2" id="datePicker" value="<?php echo htmlspecialchars($selected_date); ?>">
        <button class="btn btn-primary" onclick="changeDate()">View</button>
    </div>
</div>

<!-- Attendance Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.1s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Present Today</div>
                    <h2 class="info-card-value"><?php echo $attendance_stats['present_today']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.2s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Absent Today</div>
                    <h2 class="info-card-value"><?php echo $attendance_stats['absent_today']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.3s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Total Expected</div>
                    <h2 class="info-card-value"><?php echo $attendance_stats['total_expected']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.4s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Attendance Rate</div>
                    <h2 class="info-card-value"><?php echo $attendance_stats['attendance_rate']; ?>%</h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.5s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Monthly Avg</div>
                    <h2 class="info-card-value"><?php echo $attendance_stats['monthly_avg']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.6s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Monthly Peak</div>
                    <h2 class="info-card-value"><?php echo $attendance_stats['monthly_peak']; ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mark Attendance Table -->
<div class="card-modern">
    <div class="card-header">
        <h5 class="card-title mb-0">Mark Attendance for <?php echo date('F j, Y', strtotime($selected_date)); ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" id="attendance-form">
            <input type="hidden" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users->num_rows > 0): ?>
                            <?php while ($user = $users->fetch_assoc()):
                                $attendance = $attendance_records[$user['id']] ?? null;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['role'] === 'trainer' ? 'bg-primary-light' : 'bg-secondary-light'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <input type="hidden" name="users[<?php echo $user['id']; ?>][role]" value="<?php echo htmlspecialchars($user['role']); ?>">
                                    <select name="users[<?php echo $user['id']; ?>][status]" class="form-select form-select-sm">
                                        <option value="absent" <?php echo ($attendance['status'] ?? 'absent') === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                        <option value="present" <?php echo ($attendance['status'] ?? '') === 'present' ? 'selected' : ''; ?>>Present</option>
                                    </select>
                                </td>
                                <td><input type="time" name="users[<?php echo $user['id']; ?>][check_in]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($attendance['check_in'] ?? ''); ?>"></td>
                                <td><input type="time" name="users[<?php echo $user['id']; ?>][check_out]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($attendance['check_out'] ?? ''); ?>"></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-3">
                <button type="submit" name="mark_attendance" class="btn btn-primary">Save All Changes</button>
            </div>
        </form>
    </div>
</div>

    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('attendance-records-table');
    if (table) {
        new DataTable(table, {
            searchable: true,
            pagination: true,
            sortable: true,
            exportable: true,
            exportOptions: {
                fileName: 'GMS_Attendance_<?php echo $selected_date; ?>',
            }
        });
    }
});

function changeDate() {
    const date = document.getElementById('datePicker').value;
    if (date) {
        window.location.href = 'attendance.php?date=' + date;
    }
}
</script>
</body>
</html>