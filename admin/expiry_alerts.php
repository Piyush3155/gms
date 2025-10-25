<?php
require_once '../includes/config.php';
require_role('admin');

// Get expiry alert days from URL parameter or default to 30 days
$alert_days = isset($_GET['days']) ? (int)$_GET['days'] : 30;

// Get members with expiring memberships using prepared statement
$sql = "
    SELECT
        m.id,
        m.name,
        m.contact,
        m.email,
        m.join_date,
        p.name as plan_name,
        p.duration_months,
        p.amount,
        COALESCE(latest_payment.payment_date, m.join_date) as start_date,
        DATE_ADD(COALESCE(latest_payment.payment_date, m.join_date), INTERVAL p.duration_months MONTH) as expiry_date,
        DATEDIFF(DATE_ADD(COALESCE(latest_payment.payment_date, m.join_date), INTERVAL p.duration_months MONTH), CURDATE()) as days_remaining
    FROM members m
    JOIN plans p ON m.plan_id = p.id
    LEFT JOIN (
        SELECT member_id, MAX(payment_date) as payment_date
        FROM payments
        WHERE status = 'paid'
        GROUP BY member_id
    ) latest_payment ON m.id = latest_payment.member_id
    WHERE m.status = 'active'
    AND DATEDIFF(DATE_ADD(COALESCE(latest_payment.payment_date, m.join_date), INTERVAL p.duration_months MONTH), CURDATE()) <= ?
    ORDER BY days_remaining ASC
";
$stmt = $conn->prepare($sql);
if (!$stmt) { die('Prepare failed: ' . $conn->error); }
$stmt->bind_param('i', $alert_days);
$stmt->execute();
$expiring_members = $stmt->get_result();

// Get summary stats using prepared statements
// Critical: <= 7 days
$critical_sql = "
    SELECT COUNT(*) as count
    FROM (
        SELECT m.id,
               DATEDIFF(DATE_ADD(COALESCE(lp.payment_date, m.join_date), INTERVAL p.duration_months MONTH), CURDATE()) as days_remaining
        FROM members m
        JOIN plans p ON m.plan_id = p.id
        LEFT JOIN (
            SELECT member_id, MAX(payment_date) as payment_date
            FROM payments
            WHERE status = 'paid'
            GROUP BY member_id
        ) lp ON m.id = lp.member_id
        WHERE m.status = 'active'
    ) t
    WHERE days_remaining <= 7
";
$critical_stmt = $conn->prepare($critical_sql);
if (!$critical_stmt) { die('Prepare failed: ' . $conn->error); }
$critical_stmt->execute();
$critical_result = $critical_stmt->get_result();
$critical_row = $critical_result->fetch_assoc();
$critical_count = (int)$critical_row['count'];

// Warning: 8 - 30 days
$warning_sql = "
    SELECT COUNT(*) as count
    FROM (
        SELECT m.id,
               DATEDIFF(DATE_ADD(COALESCE(lp.payment_date, m.join_date), INTERVAL p.duration_months MONTH), CURDATE()) as days_remaining
        FROM members m
        JOIN plans p ON m.plan_id = p.id
        LEFT JOIN (
            SELECT member_id, MAX(payment_date) as payment_date
            FROM payments
            WHERE status = 'paid'
            GROUP BY member_id
        ) lp ON m.id = lp.member_id
        WHERE m.status = 'active'
    ) t
    WHERE days_remaining BETWEEN 8 AND 30
";
$warning_stmt = $conn->prepare($warning_sql);
if (!$warning_stmt) { die('Prepare failed: ' . $conn->error); }
$warning_stmt->execute();
$warning_result = $warning_stmt->get_result();
$warning_row = $warning_result->fetch_assoc();
$warning_count = (int)$warning_row['count'];

$total_expiring = $critical_count + $warning_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Expiry Alerts - <?php echo SITE_NAME; ?></title>
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
            <h2><i class="fas fa-exclamation-triangle me-2"></i>Membership Expiry Alerts</h2>
            <div class="d-flex gap-2">
                <select class="form-select" onchange="changeAlertDays(this.value)" style="width: auto;">
                    <option value="7" <?php echo $alert_days == 7 ? 'selected' : ''; ?>>Next 7 days</option>
                    <option value="30" <?php echo $alert_days == 30 ? 'selected' : ''; ?>>Next 30 days</option>
                    <option value="60" <?php echo $alert_days == 60 ? 'selected' : ''; ?>>Next 60 days</option>
                    <option value="90" <?php echo $alert_days == 90 ? 'selected' : ''; ?>>Next 90 days</option>
                </select>
                <button class="btn btn-success" onclick="exportToExcel()">
                    <i class="fas fa-file-excel me-1"></i>Excel
                </button>
                <button class="btn btn-danger" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf me-1"></i>PDF
                </button>
            </div>
        </div>
        <!-- Alert Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-exclamation-circle me-2"></i>Critical (≤7 days)</h5>
                        <h2><?php echo $critical_count; ?></h2>
                        <small>Expire very soon</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white" style="background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-clock me-2"></i>Warning (8-30 days)</h5>
                        <h2><?php echo $warning_count; ?></h2>
                        <small>Expire soon</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white" style="background: linear-gradient(135deg, #42a5f5 0%, #1976d2 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-calendar-alt me-2"></i>Total Expiring</h5>
                        <h2><?php echo $total_expiring; ?></h2>
                        <small>Within <?php echo $alert_days; ?> days</small>
                    </div>
                </div>
            </div>
        </div>
        <!-- Expiring Members Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Members with Expiring Memberships</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="expiryTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Joining Date</th>
                                <th>Plan</th>
                                <th>Expiry Date</th>
                                <th>Days Remaining</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($member = $expiring_members->fetch_assoc()):
                                $days_remaining = $member['days_remaining'];
                                $status_class = $days_remaining <= 7 ? 'danger' : ($days_remaining <= 30 ? 'warning' : 'info');
                                $status_text = $days_remaining <= 7 ? 'Critical' : ($days_remaining <= 30 ? 'Warning' : 'Info');
                                
                                // Create WhatsApp message
                                $wa_number = preg_replace('/[^0-9]/', '', $member['contact']); // Remove non-numeric characters
                                $message = urlencode("Dear {$member['name']}, your {$member['plan_name']} membership is expiring on {$member['expiry_date']} ({$days_remaining} days remaining). Please renew to continue your fitness journey. Visit us soon!");
                                $wa_link = "https://api.whatsapp.com/send?phone={$wa_number}&text={$message}";
                            ?>
                                <tr>
                                    <td><?php echo $member['name']; ?></td>
                                    <td><?php echo $member['contact']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($member['join_date'])); ?></td>
                                    <td><?php echo $member['plan_name']; ?> (₹<?php echo number_format($member['amount'], 2); ?>)</td>
                                    <td><?php echo date('M j, Y', strtotime($member['expiry_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo $days_remaining; ?> days
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo $wa_link; ?>" target="_blank" class="btn btn-sm btn-success me-1" title="Send WhatsApp Message">
                                            <i class="fab fa-whatsapp"></i> WhatsApp
                                        </a>
                                        <button class="btn btn-sm btn-primary" onclick="sendEmail('<?php echo $member['email']; ?>', '<?php echo $member['name']; ?>', '<?php echo $member['expiry_date']; ?>', <?php echo $days_remaining; ?>)">
                                            <i class="fas fa-envelope"></i> Email
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
    </div>
        function changeAlertDays(days) {le; ?>
            window.location.href = `expiry_alerts.php?days=${days}`;
        }           </table>
                </div>
        function sendEmail(email, name, expiryDate, daysRemaining) {
            fetch('send_expiry_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',pt>
                },        function changeAlertDays(days) {
                body: JSON.stringify({n.href = `expiry_alerts.php?days=${days}`;
                    member_id: 0, // Not used in current script, but included for future
                    name: name,
                    email: email,yDate, daysRemaining) {
                    expiry_date: expiryDate,            // Send AJAX request to server to send email
                    days_remaining: daysRemaining
                })
            })'Content-Type', 'application/x-www-form-urlencoded');
            .then(response => response.json())
            .then(data => {quest.DONE) {
                if (data.success) {     if (xhr.status === 200) {
                    alert('Email sent successfully!');                        alert('Email sent successfully!');
                } else {
                    alert('Failed to send email: ' + data.message);
                }                    }
            })
            .catch(error => {
                alert('Error: ' + error.message);   xhr.send(`email=${encodeURIComponent(email)}&name=${encodeURIComponent(name)}&expiryDate=${encodeURIComponent(expiryDate)}&daysRemaining=${encodeURIComponent(daysRemaining)}`);
            });        }
        }

        // Export to Excel
        function exportToExcel() {expiryTable');
            const table = document.getElementById('expiryTable');            const wb = XLSX.utils.book_new();
            const wb = XLSX.utils.book_new();

            const clonedTable = table.cloneNode(true);able.querySelectorAll('tr');
            const rows = clonedTable.querySelectorAll('tr');
            rows.forEach(row => {
                const lastCell = row.querySelector('th:last-child, td:last-child');                if (lastCell) lastCell.remove();
                if (lastCell) lastCell.remove();
            });
ls.table_to_sheet(clonedTable);
            const ws = XLSX.utils.table_to_sheet(clonedTable);            ws['!cols'] = [{wch: 20}, {wch: 15}, {wch: 15}, {wch: 20}, {wch: 15}, {wch: 12}, {wch: 10}];
            ws['!cols'] = [{wch: 20}, {wch: 15}, {wch: 15}, {wch: 20}, {wch: 15}, {wch: 12}, {wch: 10}];
Expiry_Alerts');
            XLSX.utils.book_append_sheet(wb, ws, 'Expiry_Alerts');rts_' + new Date().toISOString().slice(0,10) + '.xlsx');
            XLSX.writeFile(wb, 'Membership_Expiry_Alerts_' + new Date().toISOString().slice(0,10) + '.xlsx');
        }
rt to PDF
        // Export to PDF        function exportToPDF() {
        function exportToPDF() {
            const { jsPDF } = window.jspdf;, 'mm', 'a4');
            const doc = new jsPDF('l', 'mm', 'a4');

            doc.setFontSize(18); 14, 15);
            doc.text('Membership Expiry Alerts', 14, 15);
            doc.setFontSize(10);(), 14, 22);
            doc.text('Generated: ' + new Date().toLocaleString(), 14, 22);'Alert Period: Next <?php echo $alert_days; ?> days', 14, 27);
            doc.text('Alert Period: Next <?php echo $alert_days; ?> days', 14, 27);
getElementById('expiryTable');
            const table = document.getElementById('expiryTable');st rows = [];
            const rows = [];            const headers = [];
            const headers = [];
table.querySelectorAll('thead th');
            const headerCells = table.querySelectorAll('thead th');Each((cell, index) => {
            headerCells.forEach((cell, index) => { headerCells.length - 1) {
                if (index < headerCells.length - 1) {sh(cell.textContent.trim());
                    headers.push(cell.textContent.trim());
                }
            });
st bodyRows = table.querySelectorAll('tbody tr');
            const bodyRows = table.querySelectorAll('tbody tr');            bodyRows.forEach(row => {
            bodyRows.forEach(row => {
                const rowData = [];       const cells = row.querySelectorAll('td');
                const cells = row.querySelectorAll('td');   cells.forEach((cell, index) => {
                cells.forEach((cell, index) => {                    if (index < cells.length - 1) {
                    if (index < cells.length - 1) {.textContent.trim());
                        rowData.push(cell.textContent.trim());
                    }
                });
                rows.push(rowData);;
            });
  doc.autoTable({
            doc.autoTable({         head: [headers],
                head: [headers],         body: rows,





















</html></body>    </div>    </div>        </div>    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>    <!-- Include libraries for export -->    </script>        }            doc.save('Membership_Expiry_Alerts_' + new Date().toISOString().slice(0,10) + '.pdf');            });                alternateRowStyles: { fillColor: [245, 247, 250] }                headStyles: { fillColor: [102, 126, 234], textColor: 255, fontStyle: 'bold' },                styles: { fontSize: 8, cellPadding: 2 },                theme: 'grid',                startY: 33,                body: rows,                startY: 33,
                theme: 'grid',
                styles: { fontSize: 8, cellPadding: 2 },
                headStyles: { fillColor: [102, 126, 234], textColor: 255, fontStyle: 'bold' },
                alternateRowStyles: { fillColor: [245, 247, 250] }
            });

            doc.save('Membership_Expiry_Alerts_' + new Date().toISOString().slice(0,10) + '.pdf');
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