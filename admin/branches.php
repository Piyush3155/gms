<?php
require_once '../includes/config.php';
require_permission('branches', 'view');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_branch'])) {
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $manager_id = !empty($_POST['manager_id']) ? $_POST['manager_id'] : null;

        if (empty($name) || empty($address)) {
            $error = "Branch name and address are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO branches (name, address, phone, email, manager_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $address, $phone, $email, $manager_id);

            if ($stmt->execute()) {
                log_activity('branch_added', "New branch added: $name", 'branches');
                redirect('branches.php?msg=10');
            } else {
                $error = "Failed to add branch.";
            }
        }
    } elseif (isset($_POST['edit_branch'])) {
        $id = $_POST['branch_id'];
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $manager_id = !empty($_POST['manager_id']) ? $_POST['manager_id'] : null;

        if (empty($name) || empty($address)) {
            $error = "Branch name and address are required.";
        } else {
            $stmt = $conn->prepare("UPDATE branches SET name=?, address=?, phone=?, email=?, manager_id=? WHERE id=?");
            $stmt->bind_param("sssssi", $name, $address, $phone, $email, $manager_id, $id);

            if ($stmt->execute()) {
                log_activity('branch_updated', "Branch updated: $name", 'branches');
                redirect('branches.php?msg=11');
            } else {
                $error = "Failed to update branch.";
            }
        }
    } elseif (isset($_POST['delete_branch'])) {
        $id = $_POST['branch_id'];

        // Check if branch has members or other dependencies
        $result = $conn->query("SELECT COUNT(*) as count FROM members WHERE branch_id = $id");
        $member_count = $result->fetch_assoc()['count'];

        if ($member_count > 0) {
            $error = "Cannot delete branch. It has $member_count member(s) assigned.";
        } else {
            $stmt = $conn->prepare("DELETE FROM branches WHERE id=?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                log_activity('branch_deleted', "Branch deleted: ID $id", 'branches');
                redirect('branches.php?msg=12');
            } else {
                $error = "Failed to delete branch.";
            }
        }
    }
}

// Get all branches
$branches = $conn->query("SELECT b.*, u.name as manager_name FROM branches b LEFT JOIN users u ON b.manager_id = u.id ORDER BY b.created_at DESC");

// Get available managers (admins and trainers)
$managers = $conn->query("SELECT u.id, u.name, r.name as role FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('Admin', 'Trainer') ORDER BY u.name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-building me-2"></i>Branch Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBranchModal">
                <i class="fas fa-plus me-2"></i>Add New Branch
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5>All Branches</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Manager</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($branch = $branches->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $branch['id']; ?></td>
                                    <td><?php echo htmlspecialchars($branch['name']); ?></td>
                                    <td><?php echo htmlspecialchars($branch['address']); ?></td>
                                    <td><?php echo htmlspecialchars($branch['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($branch['email']); ?></td>
                                    <td><?php echo htmlspecialchars($branch['manager_name'] ?? 'Not Assigned'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($branch['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editBranch(<?php echo $branch['id']; ?>, '<?php echo addslashes($branch['name']); ?>', '<?php echo addslashes($branch['address']); ?>', '<?php echo addslashes($branch['phone']); ?>', '<?php echo addslashes($branch['email']); ?>', '<?php echo $branch['manager_id']; ?>')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteBranch(<?php echo $branch['id']; ?>, '<?php echo addslashes($branch['name']); ?>')">
                                            <i class="fas fa-trash"></i> Delete
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

    <!-- Add Branch Modal -->
    <div class="modal fade" id="addBranchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="branch_name" class="form-label">Branch Name *</label>
                            <input type="text" class="form-control" id="branch_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="branch_address" class="form-label">Address *</label>
                            <textarea class="form-control" id="branch_address" name="address" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="branch_phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="branch_phone" name="phone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="branch_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="branch_email" name="email">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="branch_manager" class="form-label">Branch Manager</label>
                            <select class="form-control" id="branch_manager" name="manager_id">
                                <option value="">Select Manager</option>
                                <?php
                                $managers->data_seek(0); // Reset pointer
                                while ($manager = $managers->fetch_assoc()): ?>
                                    <option value="<?php echo $manager['id']; ?>">
                                        <?php echo htmlspecialchars($manager['name']); ?> (<?php echo $manager['role']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_branch" class="btn btn-primary">Add Branch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Branch Modal -->
    <div class="modal fade" id="editBranchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="branch_id" id="edit_branch_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_branch_name" class="form-label">Branch Name *</label>
                            <input type="text" class="form-control" id="edit_branch_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_branch_address" class="form-label">Address *</label>
                            <textarea class="form-control" id="edit_branch_address" name="address" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_branch_phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="edit_branch_phone" name="phone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_branch_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="edit_branch_email" name="email">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_branch_manager" class="form-label">Branch Manager</label>
                            <select class="form-control" id="edit_branch_manager" name="manager_id">
                                <option value="">Select Manager</option>
                                <?php
                                $managers->data_seek(0); // Reset pointer
                                while ($manager = $managers->fetch_assoc()): ?>
                                    <option value="<?php echo $manager['id']; ?>">
                                        <?php echo htmlspecialchars($manager['name']); ?> (<?php echo $manager['role']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_branch" class="btn btn-primary">Update Branch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Branch Modal -->
    <div class="modal fade" id="deleteBranchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="branch_id" id="delete_branch_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete the branch "<span id="delete_branch_name"></span>"?</p>
                        <div class="alert alert-warning">
                            <strong>Warning:</strong> This action cannot be undone. All associated data will be permanently removed.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_branch" class="btn btn-danger">Delete Branch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editBranch(id, name, address, phone, email, managerId) {
            document.getElementById('edit_branch_id').value = id;
            document.getElementById('edit_branch_name').value = name;
            document.getElementById('edit_branch_address').value = address;
            document.getElementById('edit_branch_phone').value = phone;
            document.getElementById('edit_branch_email').value = email;
            document.getElementById('edit_branch_manager').value = managerId;

            new bootstrap.Modal(document.getElementById('editBranchModal')).show();
        }

        function deleteBranch(id, name) {
            document.getElementById('delete_branch_id').value = id;
            document.getElementById('delete_branch_name').textContent = name;

            new bootstrap.Modal(document.getElementById('deleteBranchModal')).show();
        }
    </script>
</body>
</html>