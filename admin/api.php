<?php
require_once '../includes/config.php';
require_permission('api', 'view');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM api_keys WHERE id = $id");
    redirect('api.php');
}

// Handle add/edit
$errors = [];
$key = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM api_keys WHERE id = $id");
    $key = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = sanitize($_POST['user_id']);
    $name = sanitize($_POST['name']);
    $permissions = isset($_POST['permissions']) ? json_encode($_POST['permissions']) : '[]';
    $status = sanitize($_POST['status']);

    if (empty($name) || empty($user_id)) {
        $errors[] = "Name and user are required.";
    } else {
        if ($key) {
            // Update
            $stmt = $conn->prepare("UPDATE api_keys SET user_id=?, name=?, permissions=?, status=? WHERE id=?");
            $stmt->bind_param("isssi", $user_id, $name, $permissions, $status, $key['id']);
        } else {
            // Insert
            $api_key = bin2hex(random_bytes(16)); // Generate random API key
            $stmt = $conn->prepare("INSERT INTO api_keys (user_id, api_key, name, permissions, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $api_key, $name, $permissions, $status);
        }

        if ($stmt->execute()) {
            if (!$key) {
                $new_key = $api_key;
            }
            redirect('api.php');
        } else {
            $errors[] = "Error saving API key.";
        }
        $stmt->close();
    }
}

// Get all API keys
$api_keys = $conn->query("SELECT k.*, u.name as user_name FROM api_keys k JOIN users u ON k.user_id = u.id ORDER BY k.created_at DESC");

// Get users for dropdown
$users = $conn->query("SELECT id, name FROM users ORDER BY name");

// Available permissions
$available_permissions = ['members', 'attendance', 'payments', 'inventory', 'feedback'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Management - <?php echo SITE_NAME; ?></title>
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
            <h2 class="mb-0">API Key Management</h2>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#keyModal">
                    <i class="fas fa-plus me-2"></i>Add New API Key
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="datatables" class="table table-striped table-no-bordered table-hover" cellspacing="0" width="100%" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Name</th>
                        <th>API Key</th>
                        <th>Permissions</th>
                        <th>Status</th>
                        <th>Last Used</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $api_keys->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['user_name']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><code><?php echo substr($row['api_key'], 0, 20) . '...'; ?></code></td>
                            <td><?php echo implode(', ', json_decode($row['permissions'], true)); ?></td>
                            <td><span class="badge bg-<?php echo $row['status'] == 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                            <td><?php echo $row['last_used'] ? date('M d, Y H:i', strtotime($row['last_used'])) : 'Never'; ?></td>
                            <td>
                                <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- API Key Modal -->
    <div class="modal fade" id="keyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $key ? 'Edit' : 'Add'; ?> API Key</h5>
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
                                    <label class="form-label">User *</label>
                                    <select class="form-control" name="user_id" required>
                                        <option value="">Select User</option>
                                        <?php while ($user = $users->fetch_assoc()): ?>
                                            <option value="<?php echo $user['id']; ?>" <?php echo ($key['user_id'] ?? '') == $user['id'] ? 'selected' : ''; ?>><?php echo $user['name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Name *</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo $key['name'] ?? ''; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Permissions</label>
                            <div class="row">
                                <?php foreach ($available_permissions as $perm): ?>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="perm_<?php echo $perm; ?>" <?php echo $key && in_array($perm, json_decode($key['permissions'], true)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_<?php echo $perm; ?>">
                                                <?php echo ucfirst($perm); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status">
                                <option value="active" <?php echo ($key['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($key['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>

                        <?php if (isset($new_key)): ?>
                            <div class="alert alert-success">
                                <strong>New API Key Generated:</strong><br>
                                <code><?php echo $new_key; ?></code><br>
                                <small class="text-muted">Keep this key secure and share only with authorized applications.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#datatables').DataTable({
                "pagingType": "full_numbers",
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search records",
                }
            });

            var table = $('#datatables').DataTable();
        });

        // Show modal if editing
        <?php if ($key): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('keyModal'));
                modal.show();
            });
        <?php endif; ?>
    </script>
</body>
</html>