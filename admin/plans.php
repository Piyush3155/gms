<?php
require_once '../includes/config.php';
require_role('admin');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM plans WHERE id = $id");
    redirect('plans.php');
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
            redirect('plans.php');
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
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <h2>Membership Plans</h2>

        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#planModal">Add New Plan</button>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Duration (Months)</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $plans->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['duration_months']; ?></td>
                            <td>$<?php echo number_format($row['amount'], 2); ?></td>
                            <td><?php echo substr($row['description'], 0, 50) . (strlen($row['description']) > 50 ? '...' : ''); ?></td>
                            <td>
                                <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
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

                        <div class="mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" name="name" value="<?php echo $plan['name'] ?? ''; ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Duration (Months)</label>
                                    <input type="number" class="form-control" name="duration_months" value="<?php echo $plan['duration_months'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Amount *</label>
                                    <input type="number" step="0.01" class="form-control" name="amount" value="<?php echo $plan['amount'] ?? ''; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo $plan['description'] ?? ''; ?></textarea>
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

    <script>
        <?php if ($plan): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('planModal'));
                modal.show();
            });
        <?php endif; ?>
    </script>
        </div>
    </div>
    </div>
</body>
</html>