<?php
require_once '../includes/config.php';
require_role('admin');

// Get system statistics for overview cards
$system_stats = [];

// Database size
$result = $conn->query("SELECT 
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS db_size 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE()");
$system_stats['db_size'] = $result->fetch_assoc()['db_size'] ?? 0;

// Total users
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$system_stats['total_users'] = $result->fetch_assoc()['total'];

// Active sessions today
$today = date('Y-m-d');
$result = $conn->query("SELECT COUNT(DISTINCT user_id) as total FROM activity_log WHERE DATE(created_at) = '$today'");
$system_stats['active_sessions'] = $result->fetch_assoc()['total'];

// System uptime (approx based on oldest log entry)
$result = $conn->query("SELECT DATEDIFF(NOW(), MIN(created_at)) as days FROM activity_log");
$system_stats['uptime_days'] = $result->fetch_assoc()['days'] ?? 0;

// Recent backup count (last 7 days)
$result = $conn->query("SELECT COUNT(*) as total FROM activity_log WHERE action LIKE '%backup%' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$system_stats['recent_backups'] = $result->fetch_assoc()['total'];

// System errors (last 24 hours)
$result = $conn->query("SELECT COUNT(*) as total FROM activity_log WHERE action LIKE '%error%' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
$system_stats['system_errors'] = $result->fetch_assoc()['total'];

// Handle user operations
$user_errors = [];
$user_success = '';

if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    // Don't allow deleting yourself
    if ($user_id == $_SESSION['user_id']) {
        $user_errors[] = "You cannot delete your own account.";
    } else {
        $conn->query("DELETE FROM users WHERE id = $user_id");
        $user_success = "User deleted successfully.";
    }
}

// Handle add/edit user
$user = null;
if (isset($_GET['edit_user']) && is_numeric($_GET['edit_user'])) {
    $id = $_GET['edit_user'];
    $result = $conn->query("SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = $id");
    $user = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_user'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $role_id = sanitize($_POST['role_id']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($role_id)) {
        $user_errors[] = "Name, email, and role are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $user_errors[] = "Please enter a valid email address.";
    } else {
        // Check if email already exists (excluding current user if editing)
        $email_check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($email_check_query);
        $exclude_id = $user ? $user['id'] : 0;
        $stmt->bind_param("si", $email, $exclude_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $user_errors[] = "Email address already exists.";
        }
        $stmt->close();

        if (!$user && (empty($password) || strlen($password) < 6)) {
            $user_errors[] = "Password must be at least 6 characters long.";
        } elseif (!$user && $password !== $confirm_password) {
            $user_errors[] = "Passwords do not match.";
        }

        if (empty($user_errors)) {
            if ($user) {
                // Update
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role_id=?, phone=?, address=?, password=? WHERE id=?");
                    $stmt->bind_param("ssisssi", $name, $email, $role_id, $phone, $address, $hashed_password, $user['id']);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role_id=?, phone=?, address=? WHERE id=?");
                    $stmt->bind_param("ssissi", $name, $email, $role_id, $phone, $address, $user['id']);
                }
            } else {
                // Insert
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role_id, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssiss", $name, $email, $hashed_password, $role_id, $phone, $address);
            }

            if ($stmt->execute()) {
                $user_success = $user ? "User updated successfully." : "User added successfully.";
                if ($user) {
                    // Refresh user data after update
                    $result = $conn->query("SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = {$user['id']}");
                    $user = $result->fetch_assoc();
                } else {
                    $user = null; // Clear form for new user
                }
            } else {
                $user_errors[] = "Error saving user.";
            }
            $stmt->close();
        }
    }
}

// Handle form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    // Fetch current settings to get existing logo
    $current_settings = $conn->query("SELECT * FROM settings WHERE id = 1")->fetch_assoc();

    $gym_name = sanitize($_POST['gym_name']);
    $contact = sanitize($_POST['contact']);
    $address = sanitize($_POST['address']);
    $email = sanitize($_POST['email']);
    $tagline = sanitize($_POST['tagline']);
    $logo_path = $current_settings['logo'] ?? '';

    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (in_array($_FILES['logo']['type'], $allowed_types) && $_FILES['logo']['size'] <= $max_size) {
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . '.' . $ext;
            $upload_path = '../assets/images/' . $filename;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                $logo_path = 'assets/images/' . $filename;
            } else {
                $errors[] = "Failed to upload logo.";
            }
        } else {
            $errors[] = "Invalid file type or size for logo.";
        }
    }

    if (empty($gym_name)) {
        $errors[] = "Gym name is required.";
    }

    if (empty($errors)) {
        // Update settings in the database
        $stmt = $conn->prepare("UPDATE settings SET gym_name=?, contact=?, address=?, email=?, tagline=?, logo=? WHERE id=1");
        $stmt->bind_param("ssssss", $gym_name, $contact, $address, $email, $tagline, $logo_path);

        if ($stmt->execute()) {
            $success = "Settings updated successfully!";
            // Refresh settings after update
            $settings = $conn->query("SELECT * FROM settings WHERE id = 1")->fetch_assoc();
        } else {
            $errors[] = "Failed to update settings. Error: " . $stmt->error;
        }
        $stmt->close();
    }
}


// Get all roles for dropdown
$roles = $conn->query("SELECT * FROM roles ORDER BY name");

// Get all users for the User Management table
$users = $conn->query("SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.id");

// Get current settings
$settings = $conn->query("SELECT * FROM settings WHERE id = 1")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Settings - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet"/>
     <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css?v=1.0">
</head>
<body>
        <div class="main-wrapper">
         <?php include '../includes/header.php'; ?>  
        <div class="page-content">
            <div class="container-fluid">
                <!-- System Overview Cards -->
                <div class="page-header mb-4">
                    <h1 class="page-title">System Settings</h1>
                    <p class="text-muted">Manage your gym settings and system configuration</p>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-4 col-sm-6">
                        <div class="info-card fade-in" style="animation-delay: 0.1s;">
                            <div class="info-card-top">
                                <div class="info-card-icon" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                                    <i class="fas fa-database"></i>
                                </div>
                                <div class="info-card-content">
                                    <div class="info-card-title">Database Size</div>
                                    <h2 class="info-card-value"><?php echo $system_stats['db_size']; ?>MB</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="info-card fade-in" style="animation-delay: 0.2s;">
                            <div class="info-card-top">
                                <div class="info-card-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="info-card-content">
                                    <div class="info-card-title">Total Users</div>
                                    <h2 class="info-card-value"><?php echo $system_stats['total_users']; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="info-card fade-in" style="animation-delay: 0.3s;">
                            <div class="info-card-top">
                                <div class="info-card-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                                    <i class="fas fa-user-clock"></i>
                                </div>
                                <div class="info-card-content">
                                    <div class="info-card-title">Active Sessions</div>
                                    <h2 class="info-card-value"><?php echo $system_stats['active_sessions']; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="info-card fade-in" style="animation-delay: 0.4s;">
                            <div class="info-card-top">
                                <div class="info-card-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                                    <i class="fas fa-server"></i>
                                </div>
                                <div class="info-card-content">
                                    <div class="info-card-title">Uptime</div>
                                    <h2 class="info-card-value d-flex"><?php echo $system_stats['uptime_days']; ?>
                                 <p class="info-card-subtitle">days</p></h2>
                                    
                                </div>
                            </div>
                           
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="info-card fade-in" style="animation-delay: 0.5s;">
                            <div class="info-card-top">
                                <div class="info-card-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="info-card-content">
                                    <div class="info-card-title">Recent Backups</div>
                                    <h2 class="info-card-value"><?php echo $system_stats['recent_backups']; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="info-card fade-in" style="animation-delay: 0.6s;">
                            <div class="info-card-top">
                                <div class="info-card-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>
                                <div class="info-card-content">
                                    <div class="info-card-title">System Errors</div>
                                    <h2 class="info-card-value"><?php echo $system_stats['system_errors']; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
            <div class="col-12">
                <div class="card card-modern fade-in">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-cog me-2"></i>System Settings</h4>
                    </div>
                    <div class="card-body">
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="gym-tab" data-bs-toggle="tab" data-bs-target="#gym" type="button" role="tab">
                                    <i class="fas fa-building me-2"></i>Gym Settings
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                                    <i class="fas fa-users me-2"></i>User Management
                                </button>
                            </li>
                        </ul>

                        <!-- Tab content -->
                        <div class="tab-content" id="settingsTabsContent">
                            <!-- Gym Settings Tab -->
                            <div class="tab-pane fade show active" id="gym" role="tabpanel">
                                <?php if (!empty($errors)): ?>
                                    <div class="alert alert-danger alert-modern">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <ul class="mb-0">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?php echo $error; ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <?php if ($success): ?>
                                    <div class="alert alert-success alert-modern">
                                        <i class="fas fa-check-circle"></i>
                                        <div><?php echo $success; ?></div>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" enctype="multipart/form-data" class="form-modern">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Gym Name *</label>
                                                <input type="text" class="form-control" name="gym_name" value="<?php echo htmlspecialchars($settings['gym_name'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Contact Number</label>
                                                <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($settings['contact'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($settings['email'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($settings['address'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Tagline</label>
                                        <input type="text" class="form-control" name="tagline" value="<?php echo htmlspecialchars($settings['tagline'] ?? ''); ?>" placeholder="e.g., Train Hard, Stay Fit">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Logo</label>
                                        <input type="file" class="form-control" name="logo" accept="image/*">
                                        <div class="form-text">Upload JPG, PNG, or GIF file (max 2MB)</div>
                                        <?php if (!empty($settings['logo'])): ?>
                                            <div class="mt-2">
                                                <img src="../<?php echo $settings['logo']; ?>" alt="Current Logo" style="max-height: 100px;">
                                                <p class="text-muted small mt-1">Current logo</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" name="save_settings" class="btn btn-modern">
                                            <i class="fas fa-save"></i>Save Settings
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- User Management Tab -->
                            <div class="tab-pane fade" id="users" role="tabpanel">
                                <?php if (!empty($user_errors)): ?>
                                    <div class="alert alert-danger alert-modern">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <ul class="mb-0">
                                            <?php foreach ($user_errors as $error): ?>
                                                <li><?php echo $error; ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <?php if ($user_success): ?>
                                    <div class="alert alert-success alert-modern">
                                        <i class="fas fa-check-circle"></i>
                                        <div><?php echo $user_success; ?></div>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>System Users</h5>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                                        <i class="fas fa-plus me-2"></i>Add New User
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-modern table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Phone</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $users->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $row['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                                    <td>
                                                        <span class="badge badge-status badge-<?php echo $row['role_id'] == 1 ? 'active' : ($row['role_id'] == 2 ? 'pending' : 'inactive'); ?>">
                                                            <?php echo htmlspecialchars($row['role_name'] ?? 'No Role'); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['phone'] ?? ''); ?></td>
                                                    <td>
                                                        <a href="?edit_user=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                                            <a href="?delete_user=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Delete" 
                                                               onclick="return confirm('Are you sure you want to delete this user?')">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
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
                </div>
            </div>
        </div>
        </div>
    </div>
    <!-- User Modal -->
    <div class="modal fade modal-modern" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i><?php echo $user ? 'Edit' : 'Add'; ?> User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" class="form-modern">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Name *</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Role *</label>
                                    <select class="form-control" name="role_id" required>
                                        <option value="">Select Role</option>
                                        <?php 
                                        $roles->data_seek(0);
                                        while ($role = $roles->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $role['id']; ?>" <?php echo (isset($user['role_id']) && $user['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($role['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>

                        <?php if (!$user): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Password *</label>
                                        <input type="password" class="form-control" name="password" required>
                                        <div class="form-text">Minimum 6 characters</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Confirm Password *</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label">Password (leave blank to keep current)</label>
                                <input type="password" class="form-control" name="password">
                                <div class="form-text">Minimum 6 characters if changing</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_user" class="btn btn-primary">Save User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show user modal if editing
        <?php if ($user): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('userModal'));
                modal.show();
            });
        <?php endif; ?>

        // Handle tab switching to users tab if editing
        <?php if (isset($_GET['edit_user']) || isset($_GET['delete_user'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var usersTab = new bootstrap.Tab(document.getElementById('users-tab'));
                usersTab.show();
            });
        <?php endif; ?>
    </script>
   
</body>
</html>