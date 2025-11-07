<?php
require_once '../includes/config.php';
require_role('admin');

// Handle attendance marking
if (isset($_POST['mark_attendance'])) {
    $user_id = sanitize($_POST['user_id']);
    $role = sanitize($_POST['role']);
    $date = sanitize($_POST['date']);
    $check_in = !empty($_POST['check_in']) ? sanitize($_POST['check_in']) : null;
    $check_out = !empty($_POST['check_out']) ? sanitize($_POST['check_out']) : null;
    $status = sanitize($_POST['status']);

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
    header("Location: attendance.php?date=" . $date);
    exit();
}

// Get selected date
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Get all users (members and trainers)
$members = $conn->query("SELECT id, name, 'member' as role FROM members UNION SELECT id, name, 'trainer' as role FROM trainers ORDER BY name");

// Get attendance for selected date
$attendance_query = $conn->prepare("SELECT a.*, u.name, a.role FROM attendance a JOIN users u ON a.user_id = u.id WHERE a.date = ? ORDER BY u.name");
$attendance_query->bind_param("s", $selected_date);
$attendance_query->execute();
$attendance_records = $attendance_query->get_result();

// Get attendance summary
$today = date('Y-m-d');
$present_count = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$today' AND status = 'present'")->fetch_assoc()['count'];
$absent_count = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$today' AND status = 'absent'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Tracking - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="../assets/css/components.css" rel="stylesheet">
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

<!-- Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="feature-card">
            <div class="card-icon bg-success-light text-success">
                <i class="bi bi-person-check-fill"></i>
            </div>
            <div class="card-content">
                <h3 class="card-title"><?php echo $present_count; ?></h3>
                <p class="card-text">Present Today</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="feature-card">
            <div class="card-icon bg-danger-light text-danger">
                <i class="bi bi-person-x-fill"></i>
            </div>
            <div class="card-content">
                <h3 class="card-title"><?php echo $absent_count; ?></h3>
                <p class="card-text">Absent Today</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="feature-card">
            <div class="card-icon bg-info-light text-info">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="card-content">
                <h3 class="card-title"><?php echo $total_expected; ?></h3>
                <p class="card-text">Total Expected</p>
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