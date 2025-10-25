<?php
require_once '../includes/config.php';
require_role('trainer');

$user_id = $_SESSION['user_id'];

// Handle attendance marking
if (isset($_POST['mark_attendance'])) {
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
        $stmt = $conn->prepare("INSERT INTO attendance (user_id, role, date, check_in, check_out, status) VALUES (?, 'trainer', ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $date, $check_in, $check_out, $status);
    }

    $stmt->execute();
    $stmt->close();
    header("Location: attendance.php?date=" . $date);
    exit();
}

// Get selected date
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Get trainer's attendance for selected date
$attendance_query = $conn->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
$attendance_query->bind_param("is", $user_id, $selected_date);
$attendance_query->execute();
$current_attendance = $attendance_query->get_result()->fetch_assoc();

// Get attendance history
$attendance_history = $conn->query("SELECT * FROM attendance WHERE user_id = $user_id ORDER BY date DESC LIMIT 30");

// Get monthly attendance summary
$this_month = date('Y-m');
$monthly_present = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE user_id = $user_id AND DATE_FORMAT(date, '%Y-%m') = '$this_month' AND status = 'present'")->fetch_assoc()['count'];
$monthly_total = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE user_id = $user_id AND DATE_FORMAT(date, '%Y-%m') = '$this_month'")->fetch_assoc()['count'];
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
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-calendar-check me-2"></i>My Attendance</h2>
            <div class="d-flex gap-2">
                <input type="date" class="form-control" id="datePicker" value="<?php echo $selected_date; ?>">
                <button class="btn btn-modern" onclick="changeDate()">
                    <i class="fas fa-search me-1"></i>View
                </button>
            </div>
        </div>

        <!-- Monthly Summary -->
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
                        <h5 class="card-title"><i class="fas fa-clock me-2"></i>Today's Status</h5>
                        <h2><?php echo $current_attendance ? ucfirst($current_attendance['status']) : 'Not Marked'; ?></h2>
                        <small><?php echo date('M j, Y', strtotime($selected_date)); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mark Attendance -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Mark Attendance for <?php echo date('F j, Y', strtotime($selected_date)); ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <input type="hidden" name="date" value="<?php echo $selected_date; ?>">

                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status" required>
                            <option value="present" <?php echo ($current_attendance && $current_attendance['status'] == 'present') ? 'selected' : ''; ?>>Present</option>
                            <option value="absent" <?php echo ($current_attendance && $current_attendance['status'] == 'absent') ? 'selected' : ''; ?>>Absent</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Check In Time</label>
                        <input type="time" class="form-control" name="check_in" value="<?php echo $current_attendance['check_in'] ?? ''; ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Check Out Time</label>
                        <input type="time" class="form-control" name="check_out" value="<?php echo $current_attendance['check_out'] ?? ''; ?>">
                    </div>

                    <div class="col-12">
                        <button type="submit" name="mark_attendance" class="btn btn-modern">
                            <i class="fas fa-save me-2"></i><?php echo $current_attendance ? 'Update' : 'Mark'; ?> Attendance
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance History -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Attendance History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
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
                                    <td>
                                        <span class="badge bg-<?php echo $record['status'] == 'present' ? 'success' : 'danger'; ?>">
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
        function changeDate() {
            const date = document.getElementById('datePicker').value;
            window.location.href = `attendance.php?date=${date}`;
        }
    </script>
    </div>
    </div>
</body>
</html>