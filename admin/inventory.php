<?php
require_once '../includes/config.php';
require_permission('inventory', 'view');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM inventory WHERE id = $id");
    redirect('inventory.php');
}

// Handle add/edit
$errors = [];
$item = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM inventory WHERE id = $id");
    $item = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $category = sanitize($_POST['category']);
    $quantity = sanitize($_POST['quantity']);
    $unit_price = sanitize($_POST['unit_price']);
    $supplier_id = sanitize($_POST['supplier_id']);
    $purchase_date = sanitize($_POST['purchase_date']);
    $expiry_date = !empty($_POST['expiry_date']) ? sanitize($_POST['expiry_date']) : null;
    $description = sanitize($_POST['description']);

    if (empty($name) || empty($quantity)) {
        $errors[] = "Name and quantity are required.";
    } else {
        if ($item) {
            // Update
            $stmt = $conn->prepare("UPDATE inventory SET name=?, category=?, quantity=?, unit_price=?, supplier_id=?, purchase_date=?, expiry_date=?, description=? WHERE id=?");
            $stmt->bind_param("ssidsissi", $name, $category, $quantity, $unit_price, $supplier_id, $purchase_date, $expiry_date, $description, $item['id']);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO inventory (name, category, quantity, unit_price, supplier_id, purchase_date, expiry_date, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssidsiss", $name, $category, $quantity, $unit_price, $supplier_id, $purchase_date, $expiry_date, $description);
        }

        if ($stmt->execute()) {
            redirect('inventory.php');
        } else {
            $errors[] = "Error saving item.";
        }
        $stmt->close();
    }
}

// Get inventory statistics
$inventory_stats = [];

// Total items
$result = $conn->query("SELECT COUNT(*) as total FROM inventory");
$inventory_stats['total_items'] = $result->fetch_assoc()['total'];

// Low stock items (quantity <= 10)
$result = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE quantity <= 10 AND quantity > 0");
$inventory_stats['low_stock'] = $result->fetch_assoc()['total'];

// Out of stock items (quantity = 0)
$result = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE quantity = 0");
$inventory_stats['out_of_stock'] = $result->fetch_assoc()['total'];

// Total inventory value
$result = $conn->query("SELECT SUM(quantity * unit_price) as total_value FROM inventory");
$inventory_stats['total_value'] = $result->fetch_assoc()['total_value'] ?? 0;

// Expired items
$result = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE expiry_date < CURDATE() AND expiry_date IS NOT NULL");
$inventory_stats['expired_items'] = $result->fetch_assoc()['total'];

// Items expiring soon (within 30 days)
$result = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date > CURDATE() AND expiry_date IS NOT NULL");
$inventory_stats['expiring_soon'] = $result->fetch_assoc()['total'];

// Get all inventory
$inventory = $conn->query("SELECT i.*, s.name as supplier_name FROM inventory i LEFT JOIN suppliers s ON i.supplier_id = s.id ORDER BY i.name");

// Get suppliers for dropdown
$suppliers = $conn->query("SELECT id, name FROM suppliers ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="page-header">
    <h1 class="page-title">Inventory Management</h1>
    <div class="page-options">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#itemModal">
            <i class="fas fa-plus me-1"></i>Add Item
        </button>
    </div>
</div>

<!-- Inventory Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.1s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Total Items</div>
                    <h2 class="info-card-value"><?php echo $inventory_stats['total_items']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.2s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Low Stock</div>
                    <h2 class="info-card-value"><?php echo $inventory_stats['low_stock']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.3s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Out of Stock</div>
                    <h2 class="info-card-value"><?php echo $inventory_stats['out_of_stock']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.4s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Total Value</div>
                    <h2 class="info-card-value">₹<?php echo number_format($inventory_stats['total_value'] / 1000, 0); ?>k</h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.5s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Expired</div>
                    <h2 class="info-card-value"><?php echo $inventory_stats['expired_items']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.6s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Expiring Soon</div>
                    <h2 class="info-card-value"><?php echo $inventory_stats['expiring_soon']; ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>

        <div class="card-modern">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="inventory-table" class="table table-modern" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Supplier</th>
                        <th>Expiry Date</th>
                        <th data-sortable="false" data-exportable="false">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $inventory->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['category']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $row['quantity'] > 10 ? 'success' : ($row['quantity'] > 0 ? 'warning' : 'danger'); ?>">
                                    <?php echo $row['quantity']; ?>
                                </span>
                            </td>
                            <td>₹<?php echo number_format($row['unit_price'], 2); ?></td>
                            <td><?php echo $row['supplier_name'] ?? 'N/A'; ?></td>
                            <td><?php echo $row['expiry_date'] ? date('M d, Y', strtotime($row['expiry_date'])) : 'N/A'; ?></td>
                            <td>
                                <a href="?edit=<?php echo $row['id']; ?>" class="btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></a>
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
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

    <!-- Item Modal -->
    <div class="modal fade" id="itemModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $item ? 'Edit' : 'Add'; ?> Inventory Item</h5>
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
                                    <input type="text" class="form-control" name="name" value="<?php echo $item['name'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <input type="text" class="form-control" name="category" value="<?php echo $item['category'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Quantity *</label>
                                    <input type="number" class="form-control" name="quantity" value="<?php echo $item['quantity'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Unit Price</label>
                                    <input type="number" class="form-control" name="unit_price" value="<?php echo $item['unit_price'] ?? ''; ?>" step="0.01">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Supplier</label>
                                    <select class="form-control" name="supplier_id">
                                        <option value="">Select Supplier</option>
                                        <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                                            <option value="<?php echo $supplier['id']; ?>" <?php echo ($item['supplier_id'] ?? '') == $supplier['id'] ? 'selected' : ''; ?>><?php echo $supplier['name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Purchase Date</label>
                                    <input type="date" class="form-control" name="purchase_date" value="<?php echo $item['purchase_date'] ?? date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="date" class="form-control" name="expiry_date" value="<?php echo $item['expiry_date'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo $item['description'] ?? ''; ?></textarea>
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

    <!-- Include libraries for export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script src="../assets/js/enhanced.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('inventory-table');
            if (table) {
                new DataTable(table, {
                    searchable: true,
                    pagination: true,
                    sortable: true,
                    exportable: true,
                    exportOptions: {
                        fileName: 'Inventory'
                    }
                });
            }

            // Show modal if editing
            <?php if ($item): ?>
                var modal = new bootstrap.Modal(document.getElementById('itemModal'));
                modal.show();
            <?php endif; ?>
        });
    </script>
</body>
</html>