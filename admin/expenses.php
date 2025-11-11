<?php
require_once '../includes/config.php';
require_role('admin');

// Handle expense recording
if (isset($_POST['record_expense'])) {
    $category = trim($_POST['category']);
    $amount = floatval($_POST['amount']);
    $expense_date = trim($_POST['expense_date']);
    $description = trim($_POST['description']);

    $stmt = $conn->prepare("INSERT INTO expenses (category, amount, expense_date, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdss", $category, $amount, $expense_date, $description);

    if ($stmt->execute()) {
        log_activity("Recorded expense", "expenses", "Category: $category, Amount: $amount");
        redirect('expenses.php?msg=13');
    } else {
        $errors[] = "Failed to record expense.";
    }
    $stmt->close();
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        log_activity("Deleted expense", "expenses", "Expense ID: $id");
    }
    $stmt->close();
    redirect('expenses.php?msg=15');
}

// Get all expenses
$expenses = $conn->query("SELECT * FROM expenses ORDER BY expense_date DESC");

// Get expense categories
$categories = $conn->query("SELECT DISTINCT category FROM expenses ORDER BY category");

// Calculate totals
$this_month_expenses = $conn->query("SELECT SUM(amount) as total FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')")->fetch_assoc()['total'] ?? 0;
$total_expenses = $conn->query("SELECT SUM(amount) as total FROM expenses")->fetch_assoc()['total'] ?? 0;

// Get monthly expense breakdown
$monthly_expenses = $conn->query("
    SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, SUM(amount) as total
    FROM expenses
    GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management - <?php echo SITE_NAME; ?></title>
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
    <h1 class="page-title">Expense Management</h1>
    <div class="page-options">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#expenseModal">
            <i class="fas fa-plus me-1"></i>Add New Expense
        </button>
    </div>
</div>

<!-- Expense Summary -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="feature-card">
            <div class="card-icon bg-danger-light text-danger">
                <i class="bi bi-calendar-month"></i>
            </div>
            <div class="card-content">
                <h3 class="card-title">₹<?php echo number_format($this_month_expenses, 2); ?></h3>
                <p class="card-text">This Month's Expenses</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="feature-card">
            <div class="card-icon bg-secondary-light text-secondary">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="card-content">
                <h3 class="card-title">₹<?php echo number_format($total_expenses, 2); ?></h3>
                <p class="card-text">Total Expenses</p>
            </div>
        </div>
    </div>
</div>

<!-- Expenses Table -->
<div class="card-modern">
    <div class="card-header">
        <h5 class="card-title mb-0">All Expenses</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="expenses-table" class="table table-modern" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th data-sortable="false" data-exportable="false">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($expense = $expenses->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></td>
                            <td>
                                <span class="badge bg-primary-light">
                                    <?php echo htmlspecialchars(ucfirst($expense['category'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($expense['description']); ?></td>
                            <td>₹<?php echo number_format($expense['amount'], 2); ?></td>
                            <td class="actions">
                                <a href="?delete=<?php echo $expense['id']; ?>" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this expense?');"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Expense Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="expenseForm" autocomplete="off">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <select class="form-select" name="category" required>
                                <option value="">Select Category</option>
                                <?php mysqli_data_seek($categories, 0); while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category']); ?>"><?php echo htmlspecialchars(ucfirst($cat['category'])); ?></option>
                                <?php endwhile; ?>
                                <option value="equipment">Equipment</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="salary">Salary</option>
                                <option value="utilities">Utilities</option>
                                <option value="marketing">Marketing</option>
                                <option value="supplies">Supplies</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" name="amount" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expense Date *</label>
                        <input type="date" class="form-control" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="record_expense" class="btn btn-primary">Record Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('expenses-table');
    if (table) {
        new DataTable(table, {
            searchable: true,
            pagination: true,
            sortable: true,
            exportable: true,
            exportOptions: {
                fileName: 'GMS_Expenses_Export',
            }
        });
    }
    
    const expenseForm = document.getElementById('expenseForm');
    if(expenseForm) {
        new FormValidator(expenseForm);
    }
});
</script>
</body>
</html>