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
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-calendar-check me-2"></i>Attendance Tracking</h2>
            <div class="d-flex gap-2">
                <input type="date" class="form-control" id="datePicker" value="<?php echo $selected_date; ?>">
                <button class="btn btn-modern" onclick="changeDate()">
                    <i class="fas fa-search me-1"></i>View
                </button>
            </div>
        </div>

        <!-- Today's Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user-check me-2"></i>Present Today</h5>
                        <h2><?php echo $present_count; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user-times me-2"></i>Absent Today</h5>
                        <h2><?php echo $absent_count; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users me-2"></i>Total Expected</h5>
                        <h2><?php echo $members->num_rows; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mark Attendance Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Mark Attendance for <?php echo date('F j, Y', strtotime($selected_date)); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $members->data_seek(0); // Reset pointer
                            while ($member = $members->fetch_assoc()):
                                // Check if attendance exists
                                $attendance_query->execute();
                                $attendance_result = $attendance_query->get_result();
                                $existing_attendance = null;
                                while ($att = $attendance_result->fetch_assoc()) {
                                    if ($att['user_id'] == $member['id']) {
                                        $existing_attendance = $att;
                                        break;
                                    }
                                }
                            ?>
                                <tr>
                                    <td><?php echo $member['name']; ?></td>
                                    <td><span class="badge bg-<?php echo $member['role'] == 'trainer' ? 'primary' : 'success'; ?>"><?php echo ucfirst($member['role']); ?></span></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                            <input type="hidden" name="role" value="<?php echo $member['role']; ?>">
                                            <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                                            <select name="status" class="form-select form-select-sm d-inline-block w-auto">
                                                <option value="present" <?php echo ($existing_attendance && $existing_attendance['status'] == 'present') ? 'selected' : ''; ?>>Present</option>
                                                <option value="absent" <?php echo ($existing_attendance && $existing_attendance['status'] == 'absent') ? 'selected' : ''; ?>>Absent</option>
                                            </select>
                                    </td>
                                    <td>
                                        <input type="time" name="check_in" class="form-control form-control-sm d-inline-block w-auto" value="<?php echo $existing_attendance['check_in'] ?? ''; ?>">
                                    </td>
                                    <td>
                                        <input type="time" name="check_out" class="form-control form-control-sm d-inline-block w-auto" value="<?php echo $existing_attendance['check_out'] ?? ''; ?>">
                                    </td>
                                    <td>
                                        <button type="submit" name="mark_attendance" class="btn btn-sm btn-modern">
                                            <i class="fas fa-save me-1"></i>Save
                                        </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Attendance Records -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Attendance Records for <?php echo date('F j, Y', strtotime($selected_date)); ?></h5>
                <div>
                    <button class="btn btn-success btn-sm me-2" onclick="exportToExcel()">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="attendanceTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $attendance_records->data_seek(0); // Reset pointer
                            while ($record = $attendance_records->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><?php echo $record['name']; ?></td>
                                    <td><span class="badge bg-<?php echo $record['role'] == 'trainer' ? 'primary' : 'success'; ?>"><?php echo ucfirst($record['role']); ?></span></td>
                                    <td>
                                        <span class="badge bg-<?php echo $record['status'] == 'present' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $record['check_in'] ?: '-'; ?></td>
                                    <td><?php echo $record['check_out'] ?: '-'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Include libraries for export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

    <script>
        function changeDate() {
            const date = document.getElementById('datePicker').value;
            window.location.href = 'attendance.php?date=' + date;
        }

        // Export to Excel
        function exportToExcel() {
            const table = document.getElementById('attendanceTable');
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            ws['!cols'] = [{wch: 20}, {wch: 10}, {wch: 10}, {wch: 12}, {wch: 12}];
            
            XLSX.utils.book_append_sheet(wb, ws, 'Attendance');
            XLSX.writeFile(wb, 'Attendance_<?php echo $selected_date; ?>.xlsx');
        }

        // Export to PDF
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            doc.setFontSize(18);
            doc.text('Attendance Report', 14, 15);
            doc.setFontSize(10);
            doc.text('Date: <?php echo date('F j, Y', strtotime($selected_date)); ?>', 14, 22);
            doc.text('Generated: ' + new Date().toLocaleString(), 14, 28);
            
            const table = document.getElementById('attendanceTable');
            const rows = [];
            const headers = [];
            
            const headerCells = table.querySelectorAll('thead th');
            headerCells.forEach(cell => {
                headers.push(cell.textContent.trim());
            });
            
            const bodyRows = table.querySelectorAll('tbody tr');
            bodyRows.forEach(row => {
                const rowData = [];
                const cells = row.querySelectorAll('td');
                cells.forEach(cell => {
                    rowData.push(cell.textContent.trim());
                });
                rows.push(rowData);
            });
            
            doc.autoTable({
                head: [headers],
                body: rows,
                startY: 34,
                theme: 'grid',
                styles: { fontSize: 10, cellPadding: 3 },
                headStyles: { fillColor: [102, 126, 234], textColor: 255, fontStyle: 'bold' },
                alternateRowStyles: { fillColor: [245, 247, 250] }
            });
            
            doc.save('Attendance_<?php echo $selected_date; ?>.pdf');
        }
    </script>
    </div>
    </div>
</body>
</html>