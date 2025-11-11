<?php
require_once '../includes/config.php';
require_role('admin');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        log_activity("Deleted equipment", "equipment", "Equipment ID: $id");
    }
    $stmt->close();
    redirect('equipment.php');
}

// Handle add/edit
$errors = [];
$equipment = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM equipment WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $equipment = $result->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $quantity = intval($_POST['quantity']);
    $purchase_date = !empty($_POST['purchase_date']) ? trim($_POST['purchase_date']) : null;
    $purchase_cost = !empty($_POST['purchase_cost']) ? floatval($_POST['purchase_cost']) : 0.00;
    $location = trim($_POST['location']);
    $status = trim($_POST['status']);
    $description = trim($_POST['description']);
    $maintenance_schedule = trim($_POST['maintenance_schedule']);
    $last_maintenance = !empty($_POST['last_maintenance']) ? trim($_POST['last_maintenance']) : null;
    $next_maintenance = !empty($_POST['next_maintenance']) ? trim($_POST['next_maintenance']) : null;

    if (empty($name) || empty($category)) {
        $errors[] = "Name and category are required.";
    } else {
        if ($equipment) {
            // Update
            $stmt = $conn->prepare("UPDATE equipment SET name=?, category=?, quantity=?, purchase_date=?, purchase_cost=?, location=?, status=?, description=?, maintenance_schedule=?, last_maintenance=?, next_maintenance=? WHERE id=?");
            $stmt->bind_param("siisdsssssi", $name, $category, $quantity, $purchase_date, $purchase_cost, $location, $status, $description, $maintenance_schedule, $last_maintenance, $next_maintenance, $equipment['id']);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO equipment (name, category, quantity, purchase_date, purchase_cost, location, status, description, maintenance_schedule, last_maintenance, next_maintenance) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siidsssssss", $name, $category, $quantity, $purchase_date, $purchase_cost, $location, $status, $description, $maintenance_schedule, $last_maintenance, $next_maintenance);
        }

        if ($stmt->execute()) {
            $action = $equipment ? "Updated equipment" : "Added new equipment";
            $equipment_id = $equipment ? $equipment['id'] : $conn->insert_id;
            log_activity($action, "equipment", "Equipment ID: $equipment_id, Name: $name");
            redirect('equipment.php');
        } else {
            $errors[] = "Error saving equipment.";
        }
        $stmt->close();
    }
}

// Get equipment statistics
$equipment_stats = [];

// Total equipment
$result = $conn->query("SELECT COUNT(*) as total FROM equipment");
$equipment_stats['total_equipment'] = $result->fetch_assoc()['total'];

// Available equipment
$result = $conn->query("SELECT COUNT(*) as total FROM equipment WHERE status = 'available'");
$equipment_stats['available_equipment'] = $result->fetch_assoc()['total'];

// Under maintenance
$result = $conn->query("SELECT COUNT(*) as total FROM equipment WHERE status = 'maintenance'");
$equipment_stats['under_maintenance'] = $result->fetch_assoc()['total'];

// Out of order
$result = $conn->query("SELECT COUNT(*) as total FROM equipment WHERE status = 'out_of_order'");
$equipment_stats['out_of_order'] = $result->fetch_assoc()['total'];

// Needs maintenance (next maintenance due within 7 days)
$result = $conn->query("SELECT COUNT(*) as total FROM equipment WHERE next_maintenance <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND next_maintenance >= CURDATE() AND status != 'maintenance'");
$equipment_stats['needs_maintenance'] = $result->fetch_assoc()['total'];

// Total value
$result = $conn->query("SELECT SUM(purchase_cost * quantity) as total_value FROM equipment");
$equipment_stats['total_value'] = $result->fetch_assoc()['total_value'] ?? 0;

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
        <div class="page-header">
    <h1 class="page-title">Equipment Management</h1>
    <div class="page-options">
        <button class="btn btn-success me-2" onclick="exportToExcel()">
            <i class="fas fa-file-excel me-1"></i>Export Excel
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#equipmentModal">
            <i class="fas fa-plus me-1"></i>Add Equipment
        </button>
    </div>
</div>

<!-- Equipment Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.1s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Total Equipment</div>
                    <h2 class="info-card-value"><?php echo $equipment_stats['total_equipment']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.2s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Available</div>
                    <h2 class="info-card-value"><?php echo $equipment_stats['available_equipment']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.3s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Under Maintenance</div>
                    <h2 class="info-card-value"><?php echo $equipment_stats['under_maintenance']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.4s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Out of Order</div>
                    <h2 class="info-card-value"><?php echo $equipment_stats['out_of_order']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.5s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Needs Maintenance</div>
                    <h2 class="info-card-value"><?php echo $equipment_stats['needs_maintenance']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.6s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Total Value</div>
                    <h2 class="info-card-value">₹<?php echo number_format($equipment_stats['total_value'] / 1000, 0); ?>k</h2>
                </div>
            </div>
        </div>
    </div>
</div>

        <div class="card-modern">
            <div class="card-body">
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