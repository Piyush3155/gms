<?php
require_once '../includes/config.php';
require_permission('payroll', 'view');

// Handle add working hours
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_hours'])) {
    $user_id = sanitize($_POST['user_id']);
    $date = sanitize($_POST['date']);
    $hours = sanitize($_POST['hours']);

    $stmt = $conn->prepare("INSERT INTO working_hours (user_id, date, hours_worked) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE hours_worked = ?");
    $stmt->bind_param("isdd", $user_id, $date, $hours, $hours);
    $stmt->execute();
    $stmt->close();
    $success = "Working hours updated.";
}

// Handle generate payroll
if (isset($_GET['generate_payroll'])) {
    $user_id = $_GET['generate_payroll'];
    $month = date('m');
    $year = date('Y');

    // Get base salary from trainers table or default
    $salary_result = $conn->query("SELECT salary FROM trainers WHERE id = (SELECT id FROM trainers WHERE email = (SELECT email FROM users WHERE id = $user_id) LIMIT 1)");
    $base_salary = $salary_result->fetch_assoc()['salary'] ?? 0;

    // Calculate total hours this month
    $hours_result = $conn->query("SELECT SUM(hours_worked) as total_hours FROM working_hours WHERE user_id = $user_id AND MONTH(date) = $month AND YEAR(date) = $year");
    $total_hours = $hours_result->fetch_assoc()['total_hours'] ?? 0;

    // Simple calculation: base salary is monthly, overtime if hours > 160
    $overtime_hours = max(0, $total_hours - 160);
    $regular_hours = min($total_hours, 160);
    $overtime_rate = $base_salary / 160 * 1.5; // 1.5x for overtime
    $overtime_pay = $overtime_hours * ($overtime_rate / 160);
    $net_salary = $base_salary + $overtime_pay;

    $stmt = $conn->prepare("INSERT INTO payroll (user_id, month, year, base_salary, hours_worked, overtime_hours, overtime_rate, net_salary, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending') ON DUPLICATE KEY UPDATE hours_worked = ?, overtime_hours = ?, net_salary = ?");
    $stmt->bind_param("iiiidddddddd", $user_id, $month, $year, $base_salary, $total_hours, $overtime_hours, $overtime_rate, $net_salary, $total_hours, $overtime_hours, $net_salary);
    $stmt->execute();
    $stmt->close();
    $success = "Payroll generated.";
}

// Handle mark as paid
if (isset($_GET['mark_paid'])) {
    $payroll_id = $_GET['mark_paid'];
    $conn->query("UPDATE payroll SET status = 'paid', payment_date = CURDATE() WHERE id = $payroll_id");
    $success = "Payroll marked as paid.";
}

// Get all payroll records
$payroll = $conn->query("SELECT p.*, u.name as user_name FROM payroll p JOIN users u ON p.user_id = u.id ORDER BY p.year DESC, p.month DESC, u.name");

// Get users for working hours
$users = $conn->query("SELECT u.id, u.name, t.salary FROM users u LEFT JOIN trainers t ON u.name = t.name WHERE u.role_id = 2 ORDER BY u.name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management - <?php echo SITE_NAME; ?></title>
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
        <div class="row">
            <div class="col-md-8">
                <h2 class="mb-4">Payroll Management</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table id="payroll-table" class="table table-striped table-no-bordered table-hover" cellspacing="0" width="100%" style="width:100%">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Month/Year</th>
                                <th>Base Salary</th>
                                <th>Hours Worked</th>
                                <th>Overtime</th>
                                <th>Net Salary</th>
                                <th>Status</th>
                                <th data-sortable="false" data-exportable="false">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $payroll->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['user_name']; ?></td>
                                    <td><?php echo date('M Y', strtotime($row['year'] . '-' . $row['month'] . '-01')); ?></td>
                                    <td>₹<?php echo number_format($row['base_salary'], 2); ?></td>
                                    <td><?php echo $row['hours_worked']; ?> hrs</td>
                                    <td><?php echo $row['overtime_hours']; ?> hrs</td>
                                    <td>₹<?php echo number_format($row['net_salary'], 2); ?></td>
                                    <td><span class="badge bg-<?php echo $row['status'] == 'paid' ? 'success' : 'warning'; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                    <td>
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <a href="?mark_paid=<?php echo $row['id']; ?>" class="btn btn-sm btn-success" title="Mark as Paid"><i class="bi bi-check-circle"></i></a>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-info" onclick="exportSlip(<?php echo $row['id']; ?>)" title="Export PDF"><i class="bi bi-file-earmark-pdf"></i></button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Add Working Hours -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Add Working Hours</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">User</label>
                                <select class="form-control" name="user_id" required>
                                    <option value="">Select User</option>
                                    <?php while ($user = $users->fetch_assoc()): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo $user['name']; ?> (₹<?php echo number_format($user['salary'], 2); ?>/month)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" class="form-control" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hours Worked</label>
                                <input type="number" class="form-control" name="hours" step="0.5" min="0" max="24" required>
                            </div>
                            <button type="submit" name="add_hours" class="btn btn-primary w-100">Add Hours</button>
                        </form>
                    </div>
                </div>

                <!-- Generate Payroll -->
                <div class="card">
                    <div class="card-header">
                        <h5>Generate Payroll</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Generate payroll for current month for all trainers.</p>
                        <form method="GET">
                            <input type="hidden" name="generate_all" value="1">
                            <button type="submit" class="btn btn-warning w-100">Generate for All</button>
                        </form>
                        <hr>
                        <p class="text-muted small">Or generate for individual:</p>
                        <form method="GET">
                            <div class="mb-3">
                                <select class="form-control" name="generate_payroll" required>
                                    <option value="">Select User</option>
                                    <?php 
                                    $users->data_seek(0);
                                    while ($user = $users->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo $user['name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-secondary w-100">Generate Individual</button>
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
            const payrollTable = new DataTable('payroll-table', {
                search: true,
                pagination: true,
                sortable: true,
                exportable: true,
                exportOptions: {
                    excel: {
                        filename: 'Payroll_' + new Date().toISOString().slice(0,10) + '.xlsx',
                        sheetName: 'Payroll'
                    },
                    pdf: {
                        filename: 'Payroll_' + new Date().toISOString().slice(0,10) + '.pdf',
                        title: 'Payroll Management'
                    },
                    csv: {
                        filename: 'Payroll_' + new Date().toISOString().slice(0,10) + '.csv'
                    }
                }
            });
        });

        function exportSlip(payrollId) {
            // Simple PDF export - in real implementation, you'd fetch data and create proper slip
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            doc.text('Salary Slip', 20, 20);
            doc.text('Payroll ID: ' + payrollId, 20, 40);
            doc.text('Generated on: ' + new Date().toLocaleDateString(), 20, 50);

            doc.save('salary_slip_' + payrollId + '.pdf');
        }

        // Handle generate all
        <?php if (isset($_GET['generate_all'])): ?>
            <?php
            $users->data_seek(0);
            while ($user = $users->fetch_assoc()) {
                // Generate for each user
                $user_id = $user['id'];
                $month = date('m');
                $year = date('Y');
                $base_salary = $user['salary'] ?? 0;
                $hours_result = $conn->query("SELECT SUM(hours_worked) as total_hours FROM working_hours WHERE user_id = $user_id AND MONTH(date) = $month AND YEAR(date) = $year");
                $total_hours = $hours_result->fetch_assoc()['total_hours'] ?? 0;
                $overtime_hours = max(0, $total_hours - 160);
                $overtime_rate = $base_salary / 160 * 1.5;
                $overtime_pay = $overtime_hours * ($overtime_rate / 160);
                $net_salary = $base_salary + $overtime_pay;
                $stmt = $conn->prepare("INSERT INTO payroll (user_id, month, year, base_salary, hours_worked, overtime_hours, overtime_rate, net_salary, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending') ON DUPLICATE KEY UPDATE hours_worked = ?, overtime_hours = ?, net_salary = ?");
                $stmt->bind_param("iiiidddddddd", $user_id, $month, $year, $base_salary, $total_hours, $overtime_hours, $overtime_rate, $net_salary, $total_hours, $overtime_hours, $net_salary);
                $stmt->execute();
                $stmt->close();
            }
            ?>
            window.location.href = 'payroll.php';
        <?php endif; ?>
    </script>
</body>
</html>