<?php
require_once '../includes/config.php';
require_role('admin');

// Handle expense recording
if (isset($_POST['record_expense'])) {
    $category = sanitize($_POST['category']);
    $amount = sanitize($_POST['amount']);
    $expense_date = sanitize($_POST['expense_date']);
    $description = sanitize($_POST['description']);

    $stmt = $conn->prepare("INSERT INTO expenses (category, amount, expense_date, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdss", $category, $amount, $expense_date, $description);

    if ($stmt->execute()) {
        $success = "Expense recorded successfully!";
    } else {
        $errors[] = "Failed to record expense.";
    }
    $stmt->close();
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
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-money-bill-wave me-2"></i>Expense Management</h2>
            <button class="btn btn-modern" data-bs-toggle="modal" data-bs-target="#expenseModal">
                <i class="fas fa-plus me-2"></i>Add Expense
            </button>
        </div>

        <!-- Expense Summary -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card text-white" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-calendar-alt me-2"></i>This Month</h5>
                        <h2>₹<?php echo number_format($this_month_expenses, 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-white" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-dollar-sign me-2"></i>Total Expenses</h5>
                        <h2>₹<?php echo number_format($total_expenses, 2); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Breakdown -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Monthly Expense Breakdown</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Total Expenses</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($month = $monthly_expenses->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                                    <td>₹<?php echo number_format($month['total'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Expenses</h5>
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
                    <table class="table table-striped" id="expensesTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($expense = $expenses->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <i class="fas fa-tag me-1"></i><?php echo ucfirst($expense['category']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $expense['description']; ?></td>
                                    <td>₹<?php echo number_format($expense['amount'], 2); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteExpense(<?php echo $expense['id']; ?>)">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Expense Modal -->
    <div class="modal fade" id="expenseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select class="form-control" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="equipment">Equipment</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="salary">Salary</option>
                                        <option value="utilities">Utilities</option>
                                        <option value="marketing">Marketing</option>
                                        <option value="supplies">Supplies</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" name="amount" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Expense Date</label>
                            <input type="date" class="form-control" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Describe the expense..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="record_expense" class="btn btn-modern">Record Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function deleteExpense(id) {
            if (confirm('Are you sure you want to delete this expense?')) {
                // In a real application, you'd make an AJAX call or redirect to delete
                window.location.href = `expenses.php?delete=${id}`;
            }
        }

        // Handle delete via GET parameter
        <?php if (isset($_GET['delete'])): ?>
            // This would be handled by PHP, but for demo purposes
            console.log('Delete expense: <?php echo $_GET['delete']; ?>');
        <?php endif; ?>

        // Export to Excel
        function exportToExcel() {
            const table = document.getElementById('expensesTable');
            const wb = XLSX.utils.book_new();
            
            const clonedTable = table.cloneNode(true);
            const rows = clonedTable.querySelectorAll('tr');
            rows.forEach(row => {
                const lastCell = row.querySelector('th:last-child, td:last-child');
                if (lastCell) lastCell.remove();
            });
            
            const ws = XLSX.utils.table_to_sheet(clonedTable);
            ws['!cols'] = [{wch: 15}, {wch: 20}, {wch: 40}, {wch: 12}];
            
            XLSX.utils.book_append_sheet(wb, ws, 'Expenses');
            XLSX.writeFile(wb, 'Expenses_' + new Date().toISOString().slice(0,10) + '.xlsx');
        }

        // Export to PDF
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4');
            
            doc.setFontSize(18);
            doc.text('Expense Report', 14, 15);
            doc.setFontSize(10);
            doc.text('Generated: ' + new Date().toLocaleString(), 14, 22);
            
            const table = document.getElementById('expensesTable');
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
                styles: { fontSize: 9, cellPadding: 2 },
                headStyles: { fillColor: [102, 126, 234], textColor: 255, fontStyle: 'bold' },
                alternateRowStyles: { fillColor: [245, 247, 250] }
            });
            
            doc.save('Expenses_' + new Date().toISOString().slice(0,10) + '.pdf');
        }
    </script>

    <!-- Include libraries for export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
        </div>
    </div>
    </div>
</body>
</html>