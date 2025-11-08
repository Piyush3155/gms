<?php
require_once '../includes/config.php';
require_role('admin');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM member_progress WHERE id = $id");
    redirect('member_progress.php');
}

// Handle add/edit
$errors = [];
$progress = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT mp.*, m.name as member_name FROM member_progress mp JOIN members m ON mp.member_id = m.id WHERE mp.id = $id");
    $progress = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_id = sanitize($_POST['member_id']);
    $measurement_date = sanitize($_POST['measurement_date']);
    $weight = !empty($_POST['weight']) ? sanitize($_POST['weight']) : null;
    $height = !empty($_POST['height']) ? sanitize($_POST['height']) : null;
    $chest = !empty($_POST['chest']) ? sanitize($_POST['chest']) : null;
    $waist = !empty($_POST['waist']) ? sanitize($_POST['waist']) : null;
    $hips = !empty($_POST['hips']) ? sanitize($_POST['hips']) : null;
    $biceps = !empty($_POST['biceps']) ? sanitize($_POST['biceps']) : null;
    $thighs = !empty($_POST['thighs']) ? sanitize($_POST['thighs']) : null;
    $notes = sanitize($_POST['notes']);

    if (empty($member_id) || empty($measurement_date)) {
        $errors[] = "Member and measurement date are required.";
    } else {
        if ($progress) {
            // Update
            $stmt = $conn->prepare("UPDATE member_progress SET member_id=?, measurement_date=?, weight=?, height=?, chest=?, waist=?, hips=?, biceps=?, thighs=?, notes=? WHERE id=?");
            $stmt->bind_param("issdddddddsi", $member_id, $measurement_date, $weight, $height, $chest, $waist, $hips, $biceps, $thighs, $notes, $progress['id']);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO member_progress (member_id, measurement_date, weight, height, chest, waist, hips, biceps, thighs, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issddddddd", $member_id, $measurement_date, $weight, $height, $chest, $waist, $hips, $biceps, $thighs, $notes);
        }

        if ($stmt->execute()) {
            redirect('member_progress.php');
        } else {
            $errors[] = "Error saving progress record.";
        }
        $stmt->close();
    }
}

// Get all progress records with member names
$progress_list = $conn->query("SELECT mp.*, m.name as member_name FROM member_progress mp JOIN members m ON mp.member_id = m.id ORDER BY mp.measurement_date DESC");

// Get members for dropdown
$members = $conn->query("SELECT id, name FROM members ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Progress Tracking - <?php echo SITE_NAME; ?></title>
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
            <h2 class="mb-0">Member Progress Tracking</h2>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#progressModal">
                    <i class="fas fa-plus me-2"></i>Add Progress Record
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="member-progress-table" class="table table-striped table-no-bordered table-hover" cellspacing="0" width="100%" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Member</th>
                        <th>Date</th>
                        <th>Weight (kg)</th>
                        <th>Chest (cm)</th>
                        <th>Waist (cm)</th>
                        <th>Hips (cm)</th>
                        <th>Biceps (cm)</th>
                        <th>Notes</th>
                        <th data-sortable="false" data-exportable="false">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $progress_list->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['member_name']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['measurement_date'])); ?></td>
                            <td><?php echo $row['weight'] ? $row['weight'] . ' kg' : '-'; ?></td>
                            <td><?php echo $row['chest'] ? $row['chest'] . ' cm' : '-'; ?></td>
                            <td><?php echo $row['waist'] ? $row['waist'] . ' cm' : '-'; ?></td>
                            <td><?php echo $row['hips'] ? $row['hips'] . ' cm' : '-'; ?></td>
                            <td><?php echo $row['biceps'] ? $row['biceps'] . ' cm' : '-'; ?></td>
                            <td><?php echo $row['notes'] ? substr($row['notes'], 0, 50) . (strlen($row['notes']) > 50 ? '...' : '') : '-'; ?></td>
                            <td class="d-flex gap-2">
                                <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
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
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Progress Modal -->
    <div class="modal fade" id="progressModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $progress ? 'Edit' : 'Add'; ?> Progress Record</h5>
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
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Member *</label>
                                    <select class="form-control" name="member_id" required>
                                        <option value="">Select Member</option>
                                        <?php
                                        $members->data_seek(0);
                                        while ($member = $members->fetch_assoc()): ?>
                                            <option value="<?php echo $member['id']; ?>" <?php echo ($progress && $progress['member_id'] == $member['id']) ? 'selected' : ''; ?>>
                                                <?php echo $member['name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Measurement Date *</label>
                                    <input type="date" class="form-control" name="measurement_date" value="<?php echo $progress['measurement_date'] ?? date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Weight (kg)</label>
                                    <input type="number" class="form-control" name="weight" value="<?php echo $progress['weight'] ?? ''; ?>" step="0.1" placeholder="e.g., 70.5">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Height (cm)</label>
                                    <input type="number" class="form-control" name="height" value="<?php echo $progress['height'] ?? ''; ?>" step="0.1" placeholder="e.g., 170.0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Chest (cm)</label>
                                    <input type="number" class="form-control" name="chest" value="<?php echo $progress['chest'] ?? ''; ?>" step="0.1">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Waist (cm)</label>
                                    <input type="number" class="form-control" name="waist" value="<?php echo $progress['waist'] ?? ''; ?>" step="0.1">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Hips (cm)</label>
                                    <input type="number" class="form-control" name="hips" value="<?php echo $progress['hips'] ?? ''; ?>" step="0.1">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Biceps (cm)</label>
                                    <input type="number" class="form-control" name="biceps" value="<?php echo $progress['biceps'] ?? ''; ?>" step="0.1">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Thighs (cm)</label>
                                    <input type="number" class="form-control" name="thighs" value="<?php echo $progress['thighs'] ?? ''; ?>" step="0.1">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes about progress, goals, etc."><?php echo $progress['notes'] ?? ''; ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
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
    <script src="../assets/js/enhanced.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            const memberProgressTable = new DataTable('member-progress-table', {
                search: true,
                pagination: true,
                sortable: true,
                exportable: true,
                exportOptions: {
                    excel: {
                        filename: 'Member_Progress_' + new Date().toISOString().slice(0,10) + '.xlsx',
                        sheetName: 'Member_Progress'
                    },
                    pdf: {
                        filename: 'Member_Progress_' + new Date().toISOString().slice(0,10) + '.pdf',
                        title: 'Member Progress Records'
                    },
                    csv: {
                        filename: 'Member_Progress_' + new Date().toISOString().slice(0,10) + '.csv'
                    }
                }
            });

            // Show modal if editing
            <?php if ($progress): ?>
                var modal = new bootstrap.Modal(document.getElementById('progressModal'));
                modal.show();
            <?php endif; ?>
        });
    </script>
       <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>