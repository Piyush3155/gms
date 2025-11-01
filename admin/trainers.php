<?php
require_once '../includes/config.php';
require_role('admin');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM trainers WHERE id = $id");
    redirect('trainers.php?msg=6');
}

// Handle add/edit
$errors = [];
$trainer = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM trainers WHERE id = $id");
    $trainer = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $specialization = sanitize($_POST['specialization']);
    $contact = sanitize($_POST['contact']);
    $email = sanitize($_POST['email']);
    $experience = sanitize($_POST['experience']);
    $salary = sanitize($_POST['salary']);
    $join_date = sanitize($_POST['join_date']);

    if (empty($name) || empty($email)) {
        $errors[] = "Name and email are required.";
    } else {
        if ($trainer) {
            // Update
            $stmt = $conn->prepare("UPDATE trainers SET name=?, specialization=?, contact=?, email=?, experience=?, salary=?, join_date=? WHERE id=?");
            $stmt->bind_param("sssssdsi", $name, $specialization, $contact, $email, $experience, $salary, $join_date, $trainer['id']);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO trainers (name, specialization, contact, email, experience, salary, join_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssds", $name, $specialization, $contact, $email, $experience, $salary, $join_date);
        }

        if ($stmt->execute()) {
            $msg = $trainer ? '5' : '4';
            redirect('trainers.php?msg=' . $msg);
        } else {
            $errors[] = "Error saving trainer.";
        }
        $stmt->close();
    }
}

// If POST submission produced errors, repopulate $trainer with submitted values
// so the modal can show the user's input when the page reloads.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($errors)) {
    $trainer = [
        'name' => $name ?? '',
        'specialization' => $specialization ?? '',
        'contact' => $contact ?? '',
        'email' => $email ?? '',
        'experience' => $experience ?? '',
        'salary' => $salary ?? '',
        'join_date' => $join_date ?? date('Y-m-d'),
    ];
}

// Get all trainers
$trainers = $conn->query("SELECT t.*, COUNT(m.id) as member_count FROM trainers t LEFT JOIN members m ON t.id = m.trainer_id GROUP BY t.id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="page-header">
    <h1 class="page-title">Trainer Management</h1>
    <div class="page-options">
        <button class="btn btn-outline-secondary" onclick="exportToExcel()">
            <i class="fas fa-file-excel me-1"></i>Export to Excel
        </button>
        <button class="btn btn-outline-secondary" onclick="exportToPDF()">
            <i class="fas fa-file-pdf me-1"></i>Export to PDF
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#trainerModal">
            <i class="fas fa-plus me-1"></i>Add New Trainer
        </button>
    </div>
</div>

        <div class="card-modern">
    <div class="card-body">
        <div class="table-responsive">
            <table id="datatables" class="table table-modern" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Specialization</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Experience (years)</th>
                        <th>Salary</th>
                        <th>Members</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $trainers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['specialization']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['contact']; ?></td>
                            <td><?php echo $row['experience']; ?></td>
                            <td>$<?php echo number_format($row['salary'], 2); ?></td>
                            <td><?php echo $row['member_count']; ?></td>
                            <td class="text-center d-flex gap-2 justify-content-center">
                                <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-icon btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-icon btn-danger" title="Delete" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></a>
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

    <!-- Include libraries for export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- Bootstrap is loaded in includes/header.php; avoid duplicate include -->

    <script>
        $(document).ready(function() {
            // showTrainerModal flag is set from PHP when ?edit=<id> is present or when POST produced errors
            var showTrainerModal = <?php echo (!empty($trainer) || !empty($errors)) ? 'true' : 'false'; ?>;

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

            // Show the modal only after DataTable init to avoid blinking/jumping
            if (showTrainerModal) {
                try {
                    var modalEl = document.getElementById('trainerModal');
                    if (modalEl) {
                        var modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    }
                } catch (e) {
                    console && console.error('Error showing trainer modal', e);
                }
            }
        });

        // Export to Excel
        function exportToExcel() {
            const table = document.getElementById('datatables');
            const wb = XLSX.utils.book_new();
            
            const clonedTable = table.cloneNode(true);
            const rows = clonedTable.querySelectorAll('tr');
            rows.forEach(row => {
                const lastCell = row.querySelector('th:last-child, td:last-child');
                if (lastCell) lastCell.remove();
            });
            
            const ws = XLSX.utils.table_to_sheet(clonedTable);
            ws['!cols'] = [{wch: 5}, {wch: 20}, {wch: 20}, {wch: 25}, {wch: 15}, {wch: 12}, {wch: 12}, {wch: 10}];
            
            XLSX.utils.book_append_sheet(wb, ws, 'Trainers');
            XLSX.writeFile(wb, 'Trainers_' + new Date().toISOString().slice(0,10) + '.xlsx');
        }

        // Export to PDF
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4');
            
            doc.setFontSize(18);
            doc.text('Trainer List', 14, 15);
            doc.setFontSize(10);
            doc.text('Generated: ' + new Date().toLocaleString(), 14, 22);
            
            const table = document.getElementById('datatables');
            const rows = [];
            const headers = [];
            
            const headerCells = table.querySelectorAll('thead th');
            headerCells.forEach((cell, index) => {
                if (index < headerCells.length - 1) {
                    headers.push(cell.textContent.trim());
                }
            });
            
            const bodyRows = table.querySelectorAll('tbody tr');
            bodyRows.forEach(row => {
                const rowData = [];
                const cells = row.querySelectorAll('td');
                cells.forEach((cell, index) => {
                    if (index < cells.length - 1) {
                        rowData.push(cell.textContent.trim());
                    }
                });
                rows.push(rowData);
            });
            
            doc.autoTable({
                head: [headers],
                body: rows,
                startY: 28,
                theme: 'grid',
                styles: { fontSize: 8, cellPadding: 2 },
                headStyles: { fillColor: [102, 126, 234], textColor: 255, fontStyle: 'bold' },
                alternateRowStyles: { fillColor: [245, 247, 250] }
            });
            
            doc.save('Trainers_' + new Date().toISOString().slice(0,10) + '.pdf');
        }
    </script>

    

    </div>
    </div>
    </div>

    <!-- Trainer Modal (moved to be a direct child of body to avoid blinking caused by DOM moves) -->
    <div class="modal fade" id="trainerModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-modern">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $trainer ? 'Edit' : 'Add'; ?> Trainer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" class="form-modern">
                    <div class="modal-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-modern">
                                <div>
                                    <h5 class="alert-heading">Error!</h5>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-user"></i>Name *</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo $trainer['name'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-envelope"></i>Email *</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo $trainer['email'] ?? ''; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-star"></i>Specialization</label>
                                    <input type="text" class="form-control" name="specialization" value="<?php echo $trainer['specialization'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-phone"></i>Contact</label>
                                    <input type="text" class="form-control" name="contact" value="<?php echo $trainer['contact'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-briefcase"></i>Experience (years)</label>
                                    <input type="number" class="form-control" name="experience" value="<?php echo $trainer['experience'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-dollar-sign"></i>Salary</label>
                                    <input type="number" step="0.01" class="form-control" name="salary" value="<?php echo $trainer['salary'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-calendar-plus"></i>Join Date</label>
                                    <input type="date" class="form-control" name="join_date" value="<?php echo $trainer['join_date'] ?? date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-modern">Save Trainer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>