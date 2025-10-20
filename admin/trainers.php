<?php
require_once '../includes/config.php';
require_role('admin');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM trainers WHERE id = $id");
    redirect('trainers.php');
}

// Handle add/edit
$errors = [];
$trainer = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM trainers WHERE id = $id");
    $trainer = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $specialization = sanitize($_POST['specialization']);
    $contact = sanitize($_POST['contact']);
    $email = sanitize($_POST['email']);
    $experience = sanitize($_POST['experience']);
    $salary = sanitize($_POST['salary']);
    $join_date = sanitize($_POST['join_date']);

    if (empty($name) || empty($email)) {
        $errors[] = "Name and email are required.";
    } else {
        if ($trainer) {
            // Update
            $stmt = $conn->prepare("UPDATE trainers SET name=?, specialization=?, contact=?, email=?, experience=?, salary=?, join_date=? WHERE id=?");
            $stmt->bind_param("sssssdsi", $name, $specialization, $contact, $email, $experience, $salary, $join_date, $trainer['id']);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO trainers (name, specialization, contact, email, experience, salary, join_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssds", $name, $specialization, $contact, $email, $experience, $salary, $join_date);
        }

        if ($stmt->execute()) {
            redirect('trainers.php');
        } else {
            $errors[] = "Error saving trainer.";
        }
        $stmt->close();
    }
}

// Get all trainers
$trainers = $conn->query("SELECT t.*, COUNT(m.id) as member_count FROM trainers t LEFT JOIN members m ON t.id = m.trainer_id GROUP BY t.id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <h2>Trainer Management</h2>

        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#trainerModal">Add New Trainer</button>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Specialization</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Experience (years)</th>
                        <th>Salary</th>
                        <th>Members</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $trainers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['specialization']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['contact']; ?></td>
                            <td><?php echo $row['experience']; ?></td>
                            <td>$<?php echo number_format($row['salary'], 2); ?></td>
                            <td><?php echo $row['member_count']; ?></td>
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

    <!-- Trainer Modal -->
    <div class="modal fade" id="trainerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $trainer ? 'Edit' : 'Add'; ?> Trainer</h5>
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
                                    <input type="text" class="form-control" name="name" value="<?php echo $trainer['name'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo $trainer['email'] ?? ''; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Specialization</label>
                                    <input type="text" class="form-control" name="specialization" value="<?php echo $trainer['specialization'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Contact</label>
                                    <input type="text" class="form-control" name="contact" value="<?php echo $trainer['contact'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Experience (years)</label>
                                    <input type="number" class="form-control" name="experience" value="<?php echo $trainer['experience'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Salary</label>
                                    <input type="number" step="0.01" class="form-control" name="salary" value="<?php echo $trainer['salary'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Join Date</label>
                                    <input type="date" class="form-control" name="join_date" value="<?php echo $trainer['join_date'] ?? date('Y-m-d'); ?>" required>
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
    </div>
    </div>

    <script>
        <?php if ($trainer): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('trainerModal'));
                modal.show();
            });
        <?php endif; ?>
    </script>
</body>
</html>