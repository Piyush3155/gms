<?php
require_once '../includes/config.php';
require_role('admin');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM equipment WHERE id = $id");
    redirect('equipment.php');
}

// Handle add/edit
$errors = [];
$equipment = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM equipment WHERE id = $id");
    $equipment = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $category = sanitize($_POST['category']);
    $quantity = sanitize($_POST['quantity']);
    $purchase_date = sanitize($_POST['purchase_date']);
    $purchase_cost = sanitize($_POST['purchase_cost']);
    $location = sanitize($_POST['location']);
    $status = sanitize($_POST['status']);
    $description = sanitize($_POST['description']);
    $maintenance_schedule = sanitize($_POST['maintenance_schedule']);
    $last_maintenance = sanitize($_POST['last_maintenance']);
    $next_maintenance = sanitize($_POST['next_maintenance']);

    if (empty($name) || empty($category)) {
        $errors[] = "Name and category are required.";
    } else {
        if ($equipment) {
            // Update
            $stmt = $conn->prepare("UPDATE equipment SET name=?, category=?, quantity=?, purchase_date=?, purchase_cost=?, location=?, status=?, description=?, maintenance_schedule=?, last_maintenance=?, next_maintenance=? WHERE id=?");
            $stmt->bind_param("ssissssssssi", $name, $category, $quantity, $purchase_date, $purchase_cost, $location, $status, $description, $maintenance_schedule, $last_maintenance, $next_maintenance, $equipment['id']);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO equipment (name, category, quantity, purchase_date, purchase_cost, location, status, description, maintenance_schedule, last_maintenance, next_maintenance) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssissssssss", $name, $category, $quantity, $purchase_date, $purchase_cost, $location, $status, $description, $maintenance_schedule, $last_maintenance, $next_maintenance);
        }

        if ($stmt->execute()) {
            redirect('equipment.php');
        } else {
            $errors[] = "Error saving equipment.";
        }
        $stmt->close();
    }
}

// Get all equipment
$equipment_list = $conn->query("SELECT * FROM equipment ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Equipment Management</h2>
            <div>
                <button class="btn btn-success me-2" onclick="exportToExcel()">
                    <i class="fas fa-file-excel me-2"></i>Export to Excel
                </button>
                <button class="btn btn-danger me-2" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf me-2"></i>Export to PDF
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#equipmentModal">
                    <i class="fas fa-plus me-2"></i>Add Equipment
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="equipment-table" class="table table-modern" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Next Maintenance</th>
                        <th data-sortable="false" data-exportable="false">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $equipment_list->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td>
                                <span class="badge bg-<?php
                                    echo $row['status'] == 'available' ? 'success' :
                                         ($row['status'] == 'maintenance' ? 'warning' :
                                         ($row['status'] == 'out_of_order' ? 'danger' : 'secondary'));
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo $row['next_maintenance'] ? date('M d, Y', strtotime($row['next_maintenance'])) : 'N/A'; ?></td>
                            <td class="actions">
                                <a href="?edit=<?php echo $row['id']; ?>" class="btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Equipment Modal -->
    <div class="modal fade" id="equipmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $equipment ? 'Edit' : 'Add'; ?> Equipment</h5>
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
                                    <label class="form-label">Name *</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo $equipment['name'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category *</label>
                                    <select class="form-control" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Cardio" <?php echo ($equipment['category'] ?? '') == 'Cardio' ? 'selected' : ''; ?>>Cardio</option>
                                        <option value="Strength" <?php echo ($equipment['category'] ?? '') == 'Strength' ? 'selected' : ''; ?>>Strength</option>
                                        <option value="Accessories" <?php echo ($equipment['category'] ?? '') == 'Accessories' ? 'selected' : ''; ?>>Accessories</option>
                                        <option value="Machines" <?php echo ($equipment['category'] ?? '') == 'Machines' ? 'selected' : ''; ?>>Machines</option>
                                        <option value="Free Weights" <?php echo ($equipment['category'] ?? '') == 'Free Weights' ? 'selected' : ''; ?>>Free Weights</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" class="form-control" name="quantity" value="<?php echo $equipment['quantity'] ?? 1; ?>" min="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Location</label>
                                    <input type="text" class="form-control" name="location" value="<?php echo $equipment['location'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Purchase Date</label>
                                    <input type="date" class="form-control" name="purchase_date" value="<?php echo $equipment['purchase_date'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Purchase Cost (₹)</label>
                                    <input type="number" class="form-control" name="purchase_cost" value="<?php echo $equipment['purchase_cost'] ?? ''; ?>" step="0.01">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status">
                                <option value="available" <?php echo ($equipment['status'] ?? 'available') == 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="in_use" <?php echo ($equipment['status'] ?? '') == 'in_use' ? 'selected' : ''; ?>>In Use</option>
                                <option value="maintenance" <?php echo ($equipment['status'] ?? '') == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="out_of_order" <?php echo ($equipment['status'] ?? '') == 'out_of_order' ? 'selected' : ''; ?>>Out of Order</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2"><?php echo $equipment['description'] ?? ''; ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Maintenance Schedule</label>
                                    <select class="form-control" name="maintenance_schedule">
                                        <option value="">Select Schedule</option>
                                        <option value="Daily" <?php echo ($equipment['maintenance_schedule'] ?? '') == 'Daily' ? 'selected' : ''; ?>>Daily</option>
                                        <option value="Weekly" <?php echo ($equipment['maintenance_schedule'] ?? '') == 'Weekly' ? 'selected' : ''; ?>>Weekly</option>
                                        <option value="Bi-weekly" <?php echo ($equipment['maintenance_schedule'] ?? '') == 'Bi-weekly' ? 'selected' : ''; ?>>Bi-weekly</option>
                                        <option value="Monthly" <?php echo ($equipment['maintenance_schedule'] ?? '') == 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
                                        <option value="Quarterly" <?php echo ($equipment['maintenance_schedule'] ?? '') == 'Quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                        <option value="Annually" <?php echo ($equipment['maintenance_schedule'] ?? '') == 'Annually' ? 'selected' : ''; ?>>Annually</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Last Maintenance</label>
                                    <input type="date" class="form-control" name="last_maintenance" value="<?php echo $equipment['last_maintenance'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Next Maintenance</label>
                                    <input type="date" class="form-control" name="next_maintenance" value="<?php echo $equipment['next_maintenance'] ?? ''; ?>">
                                </div>
                            </div>
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
    <!-- DataTables JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('equipment-table');
            if (table) {
                new DataTable(table, {
                    searchable: true,
                    pagination: true,
                    sortable: true,
                    exportable: true,
                    exportOptions: {
                        fileName: 'GMS_Equipment_Export',
                    }
                });
            }
        });

        // Show modal if editing
        <?php if ($equipment): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('equipmentModal'));
                modal.show();
            });
        <?php endif; ?>
    </script>
</body>
</html>