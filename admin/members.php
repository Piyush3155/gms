<?php
require_once '../includes/config.php';
require_role('admin');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM members WHERE id = $id");
    redirect('members.php');
}

// Handle add/edit
$errors = [];
$member = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM members WHERE id = $id");
    $member = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $gender = sanitize($_POST['gender']);
    $dob = sanitize($_POST['dob']);
    $contact = sanitize($_POST['contact']);
    $email = sanitize($_POST['email']);
    $address = sanitize($_POST['address']);
    $join_date = sanitize($_POST['join_date']);
    $plan_id = sanitize($_POST['plan_id']);
    $trainer_id = sanitize($_POST['trainer_id']);
    $status = sanitize($_POST['status']);

    if (empty($name) || empty($email)) {
        $errors[] = "Name and email are required.";
    } else {
        if ($member) {
            // Update
            $stmt = $conn->prepare("UPDATE members SET name=?, gender=?, dob=?, contact=?, email=?, address=?, join_date=?, plan_id=?, trainer_id=?, status=? WHERE id=?");
            $stmt->bind_param("ssssssssssi", $name, $gender, $dob, $contact, $email, $address, $join_date, $plan_id, $trainer_id, $status, $member['id']);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO members (name, gender, dob, contact, email, address, join_date, plan_id, trainer_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $name, $gender, $dob, $contact, $email, $address, $join_date, $plan_id, $trainer_id, $status);
        }

        if ($stmt->execute()) {
            redirect('members.php');
        } else {
            $errors[] = "Error saving member.";
        }
        $stmt->close();
    }
}

// Get all members
$members = $conn->query("SELECT m.*, p.name as plan_name, t.name as trainer_name FROM members m LEFT JOIN plans p ON m.plan_id = p.id LEFT JOIN trainers t ON m.trainer_id = t.id");

// Get plans and trainers for dropdowns
$plans = $conn->query("SELECT id, name FROM plans");
$trainers = $conn->query("SELECT id, name FROM trainers");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <h2>Member Management</h2>

        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#memberModal">Add New Member</button>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Plan</th>
                        <th>Trainer</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $members->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['contact']; ?></td>
                            <td><?php echo $row['plan_name']; ?></td>
                            <td><?php echo $row['trainer_name']; ?></td>
                            <td><span class="badge bg-<?php echo $row['status'] == 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($row['status']); ?></span></td>
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

    <!-- Member Modal -->
    <div class="modal fade" id="memberModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $member ? 'Edit' : 'Add'; ?> Member</h5>
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
                                    <label class="form-label">Name *</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo $member['name'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo $member['email'] ?? ''; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-control" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo ($member['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo ($member['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo ($member['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" name="dob" value="<?php echo $member['dob'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Contact</label>
                                    <input type="text" class="form-control" name="contact" value="<?php echo $member['contact'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Join Date</label>
                                    <input type="date" class="form-control" name="join_date" value="<?php echo $member['join_date'] ?? date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="3"><?php echo $member['address'] ?? ''; ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Plan</label>
                                    <select class="form-control" name="plan_id">
                                        <option value="">Select Plan</option>
                                        <?php while ($plan = $plans->fetch_assoc()): ?>
                                            <option value="<?php echo $plan['id']; ?>" <?php echo ($member['plan_id'] ?? '') == $plan['id'] ? 'selected' : ''; ?>><?php echo $plan['name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Trainer</label>
                                    <select class="form-control" name="trainer_id">
                                        <option value="">Select Trainer</option>
                                        <?php while ($trainer = $trainers->fetch_assoc()): ?>
                                            <option value="<?php echo $trainer['id']; ?>" <?php echo ($member['trainer_id'] ?? '') == $trainer['id'] ? 'selected' : ''; ?>><?php echo $trainer['name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-control" name="status">
                                        <option value="active" <?php echo ($member['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="expired" <?php echo ($member['status'] ?? '') == 'expired' ? 'selected' : ''; ?>>Expired</option>
                                        <option value="inactive" <?php echo ($member['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show modal if editing
        <?php if ($member): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('memberModal'));
                modal.show();
            });
        <?php endif; ?>
    </script>
</body>
</html>