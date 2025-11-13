<?php
require_once '../includes/config.php';
require_permission('rbac', 'view');

// Handle role operations
if (isset($_GET['delete_role']) && is_numeric($_GET['delete_role'])) {
    $role_id = $_GET['delete_role'];
    // Don't delete if it's the last admin role or has users
    $user_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role_id = $role_id")->fetch_assoc()['count'];
    if ($user_count > 0) {
        $error = "Cannot delete role that has assigned users.";
    } elseif ($role_id == 1) {
        $error = "Cannot delete Admin role.";
    } else {
        $conn->query("DELETE FROM role_permissions WHERE role_id = $role_id");
        $conn->query("DELETE FROM roles WHERE id = $role_id");
        redirect('rbac.php');
    }
}

// Handle permission updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_permissions'])) {
    $role_id = $_POST['role_id'];
    
    // Delete existing permissions for this role
    $conn->query("DELETE FROM role_permissions WHERE role_id = $role_id");
    
    // Insert new permissions
    if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
        $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($_POST['permissions'] as $permission_id) {
            $stmt->bind_param("ii", $role_id, $permission_id);
            $stmt->execute();
        }
        $stmt->close();
    }
    
    $success = "Permissions updated successfully.";
}

// Handle role add/edit
$errors = [];
$role = null;

if (isset($_GET['edit_role']) && is_numeric($_GET['edit_role'])) {
    $id = $_GET['edit_role'];
    $result = $conn->query("SELECT * FROM roles WHERE id = $id");
    $role = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_role'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);

    if (empty($name)) {
        $errors[] = "Role name is required.";
    } else {
        if ($role) {
            // Update
            $stmt = $conn->prepare("UPDATE roles SET name=?, description=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $description, $role['id']);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO roles (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
        }

        if ($stmt->execute()) {
            redirect('rbac.php');
        } else {
            $errors[] = "Error saving role.";
        }
        $stmt->close();
    }
}

// Get all roles
$roles = $conn->query("SELECT r.*, COUNT(u.id) as user_count FROM roles r LEFT JOIN users u ON r.id = u.role_id GROUP BY r.id");

// Get all permissions grouped by module
$permissions = $conn->query("SELECT * FROM permissions ORDER BY module, action");

// Get permissions for a specific role (for editing)
$selected_role_permissions = [];
if (isset($_GET['manage_permissions']) && is_numeric($_GET['manage_permissions'])) {
    $manage_role_id = $_GET['manage_permissions'];
    $result = $conn->query("SELECT permission_id FROM role_permissions WHERE role_id = $manage_role_id");
    while ($row = $result->fetch_assoc()) {
        $selected_role_permissions[] = $row['permission_id'];
    }
    $manage_role = $conn->query("SELECT * FROM roles WHERE id = $manage_role_id")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role & Permission Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css?v=1.0">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Role & Permission Management</h2>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#roleModal">
                    <i class="fas fa-plus me-2"></i>Add New Role
                </button>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Roles</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Users</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $roles->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo $row['name']; ?></td>
                                            <td><?php echo $row['description']; ?></td>
                                            <td><?php echo $row['user_count']; ?></td>
                                            <td>
                                                <a href="?edit_role=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                                <a href="?manage_permissions=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Manage Permissions"><i class="bi bi-shield-check"></i></a>
                                                <?php if ($row['id'] != 1 && $row['user_count'] == 0): ?>
                                                    <a href="?delete_role=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Permissions Overview</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $permissions->data_seek(0);
                        $current_module = '';
                        while ($perm = $permissions->fetch_assoc()) {
                            if ($current_module != $perm['module']) {
                                if ($current_module != '') echo '</ul>';
                                $current_module = $perm['module'];
                                echo '<h6 class="text-capitalize">' . str_replace('_', ' ', $current_module) . '</h6><ul class="list-unstyled">';
                            }
                            echo '<li><small class="text-muted">' . ucfirst($perm['action']) . '</small></li>';
                        }
                        if ($current_module != '') echo '</ul>';
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($manage_role)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5>Manage Permissions for <?php echo $manage_role['name']; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="role_id" value="<?php echo $manage_role_id; ?>">
                    <?php
                    $permissions->data_seek(0);
                    $current_module = '';
                    while ($perm = $permissions->fetch_assoc()) {
                        if ($current_module != $perm['module']) {
                            if ($current_module != '') echo '</div>';
                            $current_module = $perm['module'];
                            echo '<div class="mb-3"><h6 class="text-capitalize">' . str_replace('_', ' ', $current_module) . '</h6>';
                        }
                        $checked = in_array($perm['id'], $selected_role_permissions) ? 'checked' : '';
                        echo '<div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="' . $perm['id'] . '" id="perm_' . $perm['id'] . '" ' . $checked . '>
                            <label class="form-check-label" for="perm_' . $perm['id'] . '">
                                ' . ucfirst($perm['action']) . '
                            </label>
                        </div>';
                    }
                    if ($current_module != '') echo '</div>';
                    ?>
                    <button type="submit" name="update_permissions" class="btn btn-primary">Update Permissions</button>
                    <a href="rbac.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Role Modal -->
    <div class="modal fade" id="roleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $role ? 'Edit' : 'Add'; ?> Role</h5>
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
                            <label class="form-label">Role Name *</label>
                            <input type="text" class="form-control" name="name" value="<?php echo $role['name'] ?? ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo $role['description'] ?? ''; ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_role" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show modal if editing
        <?php if ($role): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('roleModal'));
                modal.show();
            });
        <?php endif; ?>
    </script>
</body>
</html>