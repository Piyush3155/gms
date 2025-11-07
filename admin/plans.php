<?php
require_once '../includes/config.php';
require_role('admin');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM plans WHERE id = $id");
    redirect('plans.php?msg=9');
}

// Handle add/edit
$errors = [];
$plan = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM plans WHERE id = $id");
    $plan = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $duration_months = sanitize($_POST['duration_months']);
    $amount = sanitize($_POST['amount']);
    $description = sanitize($_POST['description']);

    if (empty($name) || empty($amount)) {
        $errors[] = "Name and amount are required.";
    } else {
        if ($plan) {
            // Update
            $stmt = $conn->prepare("UPDATE plans SET name=?, duration_months=?, amount=?, description=? WHERE id=?");
            $stmt->bind_param("sidssi", $name, $duration_months, $amount, $description, $plan['id']);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO plans (name, duration_months, amount, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sids", $name, $duration_months, $amount, $description);
        }

        if ($stmt->execute()) {
            $msg = $plan ? '8' : '7';
            redirect('plans.php?msg=' . $msg);
        } else {
            $errors[] = "Error saving plan.";
        }
        $stmt->close();
    }
}

// Get all plans
$plans = $conn->query("SELECT * FROM plans");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Plans - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
    <h1 class="page-title">Membership Plans</h1>
    <div class="page-options">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#planModal">
            <i class="fas fa-plus me-1"></i>Add New Plan
        </button>
    </div>
</div>

<div class="card-modern">
    <div class="card-body">
        <div class="table-responsive">
            <table id="plans-table" class="table table-modern" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Duration (Months)</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th data-sortable="false" data-exportable="false">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $plans->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['duration_months']); ?></td>
                            <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars(substr($row['description'], 0, 50) . (strlen($row['description']) > 50 ? '...' : '')); ?></td>
                            <td class="actions">
                                <a href="?edit=<?php echo $row['id']; ?>" class="btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this plan?');"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Plan Modal -->
<div class="modal fade" id="planModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $plan ? 'Edit' : 'Add'; ?> Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="form-modern" id="planForm" autocomplete="off">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($plan['name'] ?? ''); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (Months)</label>
                            <input type="number" class="form-control" name="duration_months" value="<?php echo htmlspecialchars($plan['duration_months'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount *</label>
                            <input type="number" step="0.01" class="form-control" name="amount" value="<?php echo htmlspecialchars($plan['amount'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($plan['description'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('plans-table');
    if (table) {
        new DataTable(table, {
            searchable: true,
            pagination: true,
            sortable: true,
            exportable: true,
            exportOptions: {
                fileName: 'GMS_Plans_Export',
            }
        });
    }

    const planModalEl = document.getElementById('planModal');
    if (planModalEl) {
        const modal = new bootstrap.Modal(planModalEl);
        <?php if ((isset($_GET['edit']) && is_numeric($_GET['edit'])) || !empty($errors)): ?>
        modal.show();
        <?php endif; ?>
    }
    
    const planForm = document.getElementById('planForm');
    if(planForm) {
        new FormValidator(planForm);
    }
});
</script>
</body>
</html>