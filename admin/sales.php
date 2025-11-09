<?php
require_once '../includes/config.php';
require_permission('sales', 'view');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    // Update inventory quantity
    $sale = $conn->query("SELECT * FROM sales WHERE id = $id")->fetch_assoc();
    if ($sale) {
        $conn->query("UPDATE inventory SET quantity = quantity + {$sale['quantity']} WHERE id = {$sale['item_id']}");
    }
    $conn->query("DELETE FROM sales WHERE id = $id");
    redirect('sales.php');
}

// Handle add
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = sanitize($_POST['item_id']);
    $quantity = sanitize($_POST['quantity']);
    $unit_price = sanitize($_POST['unit_price']);
    $customer_name = sanitize($_POST['customer_name']);
    $payment_method = sanitize($_POST['payment_method']);

    // Check inventory
    $item = $conn->query("SELECT * FROM inventory WHERE id = $item_id")->fetch_assoc();
    if (!$item || $item['quantity'] < $quantity) {
        $errors[] = "Insufficient inventory.";
    } else {
        $total_amount = $quantity * $unit_price;
        $sale_date = date('Y-m-d');

        $stmt = $conn->prepare("INSERT INTO sales (item_id, quantity, unit_price, total_amount, sale_date, customer_name, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiddsss", $item_id, $quantity, $unit_price, $total_amount, $sale_date, $customer_name, $payment_method);

        if ($stmt->execute()) {
            // Update inventory
            $conn->query("UPDATE inventory SET quantity = quantity - $quantity WHERE id = $item_id");
            $success = "Sale recorded successfully.";
        } else {
            $errors[] = "Error recording sale.";
        }
        $stmt->close();
    }
}

// Get all sales
$sales = $conn->query("SELECT s.*, i.name as item_name FROM sales s JOIN inventory i ON s.item_id = i.id ORDER BY s.id DESC");

// Get inventory for dropdown
$inventory = $conn->query("SELECT id, name, unit_price FROM inventory WHERE quantity > 0 ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Management - <?php echo SITE_NAME; ?></title>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Sales Management</h2>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#saleModal">
                    <i class="fas fa-plus me-2"></i>Record New Sale
                </button>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table id="sales-table" class="table table-striped table-no-bordered table-hover" cellspacing="0" width="100%" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Payment</th>
                        <th data-sortable="false" data-exportable="false">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $sales->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['item_name']; ?></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td>₹<?php echo number_format($row['unit_price'], 2); ?></td>
                            <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td><?php echo $row['customer_name'] ?: 'Walk-in'; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['sale_date'])); ?></td>
                            <td><?php echo ucfirst($row['payment_method']); ?></td>
                            <td>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn-icon" title="Delete" onclick="return confirm('Are you sure? This will restore inventory.')"><i class="bi bi-trash"></i></a>
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

    <!-- Sale Modal -->
    <div class="modal fade" id="saleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record New Sale</h5>
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
                                    <label class="form-label">Item *</label>
                                    <select class="form-control" name="item_id" id="item_select" required onchange="updatePrice()">
                                        <option value="">Select Item</option>
                                        <?php while ($item = $inventory->fetch_assoc()): ?>
                                            <option value="<?php echo $item['id']; ?>" data-price="<?php echo $item['unit_price']; ?>"><?php echo $item['name']; ?> (₹<?php echo $item['unit_price']; ?>)</option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Quantity *</label>
                                    <input type="number" class="form-control" name="quantity" id="quantity" min="1" required onchange="calculateTotal()">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Unit Price *</label>
                                    <input type="number" class="form-control" name="unit_price" id="unit_price" step="0.01" required onchange="calculateTotal()">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Total Amount</label>
                                    <input type="text" class="form-control" id="total_amount" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Customer Name</label>
                                    <input type="text" class="form-control" name="customer_name" placeholder="Walk-in customer">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Payment Method *</label>
                                    <select class="form-control" name="payment_method" required>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="upi">UPI</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Record Sale</button>
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
            const table = document.getElementById('sales-table');
            if (table) {
                new DataTable(table, {
                    searchable: true,
                    pagination: true,
                    sortable: true,
                    exportable: true,
                    exportOptions: {
                        fileName: 'Sales'
                    }
                });
            }
        });

        function updatePrice() {
            const select = document.getElementById('item_select');
            const price = select.options[select.selectedIndex].getAttribute('data-price');
            document.getElementById('unit_price').value = price;
            calculateTotal();
        }

        function calculateTotal() {
            const quantity = document.getElementById('quantity').value;
            const price = document.getElementById('unit_price').value;
            const total = quantity * price;
            document.getElementById('total_amount').value = '₹' + total.toFixed(2);
        }
    </script>
</body>
</html>