<?php
require_once '../includes/config.php';
require_role('admin');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM group_classes WHERE id = $id");
    redirect('group_classes.php');
}

// Handle add/edit
$errors = [];
$class = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT gc.*, t.name as trainer_name FROM group_classes gc LEFT JOIN trainers t ON gc.trainer_id = t.id WHERE gc.id = $id");
    $class = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $trainer_id = !empty($_POST['trainer_id']) ? sanitize($_POST['trainer_id']) : null;
    $description = sanitize($_POST['description']);
    $capacity = sanitize($_POST['capacity']);
    $duration_minutes = sanitize($_POST['duration_minutes']);
    $class_date = sanitize($_POST['class_date']);
    $start_time = sanitize($_POST['start_time']);
    $end_time = sanitize($_POST['end_time']);
    $status = sanitize($_POST['status']);

    if (empty($name) || empty($class_date) || empty($start_time) || empty($end_time)) {
        $errors[] = "Name, date, start time, and end time are required.";
    } elseif (strtotime($start_time) >= strtotime($end_time)) {
        $errors[] = "End time must be after start time.";
    } else {
        if ($class) {
            // Update
            $stmt = $conn->prepare("UPDATE group_classes SET name=?, trainer_id=?, description=?, capacity=?, duration_minutes=?, class_date=?, start_time=?, end_time=?, status=? WHERE id=?");
            $stmt->bind_param("sisisssssi", $name, $trainer_id, $description, $capacity, $duration_minutes, $class_date, $start_time, $end_time, $status, $class['id']);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO group_classes (name, trainer_id, description, capacity, duration_minutes, class_date, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisssssss", $name, $trainer_id, $description, $capacity, $duration_minutes, $class_date, $start_time, $end_time, $status);
        }

        if ($stmt->execute()) {
            redirect('group_classes.php');
        } else {
            $errors[] = "Error saving class.";
        }
        $stmt->close();
    }
}

// Get all classes with trainer names and booking counts
$classes = $conn->query("
    SELECT gc.*,
           t.name as trainer_name,
           COUNT(cb.id) as booked_count
    FROM group_classes gc
    LEFT JOIN trainers t ON gc.trainer_id = t.id
    LEFT JOIN class_bookings cb ON gc.id = cb.class_id AND cb.status IN ('confirmed', 'attended')
    GROUP BY gc.id
    ORDER BY gc.class_date DESC, gc.start_time DESC
");

// Get trainers for dropdown
$trainers = $conn->query("SELECT id, name FROM trainers ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Classes Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Group Classes Management</h2>
            <div>
                <button class="btn btn-success me-2" onclick="exportToExcel()">
                    <i class="fas fa-file-excel me-2"></i>Export to Excel
                </button>
                <button class="btn btn-danger me-2" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf me-2"></i>Export to PDF
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#classModal">
                    <i class="fas fa-plus me-2"></i>Schedule New Class
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="datatables" class="table table-striped table-no-bordered table-hover" cellspacing="0" width="100%" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Class Name</th>
                        <th>Trainer</th>
                        <th>Date & Time</th>
                        <th>Duration</th>
                        <th>Capacity</th>
                        <th>Booked</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $classes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['trainer_name'] ?? 'TBA'; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['class_date'])) . '<br>' . date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time'])); ?></td>
                            <td><?php echo $row['duration_minutes']; ?> min</td>
                            <td><?php echo $row['capacity']; ?></td>
                            <td><?php echo $row['booked_count']; ?>/<?php echo $row['capacity']; ?></td>
                            <td>
                                <span class="badge bg-<?php
                                    echo $row['status'] == 'scheduled' ? 'primary' :
                                         ($row['status'] == 'completed' ? 'success' :
                                         ($row['status'] == 'cancelled' ? 'danger' : 'secondary'));
                                ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="view_class_bookings.php?class_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="View Bookings"><i class="bi bi-people"></i></a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Class Modal -->
    <div class="modal fade" id="classModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $class ? 'Edit' : 'Schedule'; ?> Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Class Name *</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo $class['name'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Trainer</label>
                                    <select class="form-control" name="trainer_id">
                                        <option value="">Select Trainer (Optional)</option>
                                        <?php
                                        $trainers->data_seek(0);
                                        while ($trainer = $trainers->fetch_assoc()): ?>
                                            <option value="<?php echo $trainer['id']; ?>" <?php echo ($class && $class['trainer_id'] == $trainer['id']) ? 'selected' : ''; ?>>
                                                <?php echo $trainer['name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo $class['description'] ?? ''; ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Capacity</label>
                                    <input type="number" class="form-control" name="capacity" value="<?php echo $class['capacity'] ?? 20; ?>" min="1">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Duration (minutes)</label>
                                    <input type="number" class="form-control" name="duration_minutes" value="<?php echo $class['duration_minutes'] ?? 60; ?>" min="15" step="15">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-control" name="status">
                                        <option value="scheduled" <?php echo ($class['status'] ?? 'scheduled') == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                        <option value="completed" <?php echo ($class['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo ($class['status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Class Date *</label>
                                    <input type="date" class="form-control" name="class_date" value="<?php echo $class['class_date'] ?? date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Start Time *</label>
                                    <input type="time" class="form-control" name="start_time" value="<?php echo $class['start_time'] ?? '09:00'; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">End Time *</label>
                                    <input type="time" class="form-control" name="end_time" value="<?php echo $class['end_time'] ?? '10:00'; ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Class</button>
                    </div>
                </form>
            </div>
        </div>
        </div>
    </div>
    </div>

    <!-- Include libraries for export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#datatables').DataTable({
                "pagingType": "full_numbers",
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search records",
                }
            });

            var table = $('#datatables').DataTable();
        });

        // Show modal if editing
        <?php if ($class): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('classModal'));
                modal.show();
            });
        <?php endif; ?>

        // Export to Excel
        function exportToExcel() {
            const table = document.getElementById('datatables');
            const wb = XLSX.utils.book_new();

            // Clone table and remove Actions column
            const clonedTable = table.cloneNode(true);
            const rows = clonedTable.querySelectorAll('tr');
            rows.forEach(row => {
                const lastCell = row.querySelector('th:last-child, td:last-child');
                if (lastCell) lastCell.remove();
            });

            const ws = XLSX.utils.table_to_sheet(clonedTable);

            // Set column widths
            ws['!cols'] = [
                {wch: 5},  // ID
                {wch: 25}, // Class Name
                {wch: 20}, // Trainer
                {wch: 20}, // Date & Time
                {wch: 10}, // Duration
                {wch: 10}, // Capacity
                {wch: 10}, // Booked
                {wch: 12}  // Status
            ];

            XLSX.utils.book_append_sheet(wb, ws, 'Group_Classes');
            XLSX.writeFile(wb, 'Group_Classes_' + new Date().toISOString().slice(0,10) + '.xlsx');
        }

        // Export to PDF
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4'); // landscape orientation

            // Add title
            doc.setFontSize(18);
            doc.text('Group Classes Schedule', 14, 15);

            // Add date
            doc.setFontSize(10);
            doc.text('Generated: ' + new Date().toLocaleString(), 14, 22);

            // Get table data
            const table = document.getElementById('datatables');
            const rows = [];
            const headers = [];

            // Get headers (exclude Actions column)
            const headerCells = table.querySelectorAll('thead th');
            headerCells.forEach((cell, index) => {
                if (index < headerCells.length - 1) { // Skip last column (Actions)
                    headers.push(cell.textContent.trim());
                }
            });

            // Get rows (exclude Actions column)
            const bodyRows = table.querySelectorAll('tbody tr');
            bodyRows.forEach(row => {
                const rowData = [];
                const cells = row.querySelectorAll('td');
                cells.forEach((cell, index) => {
                    if (index < cells.length - 1) { // Skip last column (Actions)
                        rowData.push(cell.textContent.trim());
                    }
                });
                rows.push(rowData);
            });

            // Add table
            doc.autoTable({
                head: [headers],
                body: rows,
                startY: 28,
                theme: 'grid',
                styles: {
                    fontSize: 8,
                    cellPadding: 2
                },
                headStyles: {
                    fillColor: [102, 126, 234],
                    textColor: 255,
                    fontStyle: 'bold'
                },
                alternateRowStyles: {
                    fillColor: [245, 247, 250]
                }
            });

            doc.save('Group_Classes_' + new Date().toISOString().slice(0,10) + '.pdf');
        }
    </script>
       <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>