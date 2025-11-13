<?php
require_once '../includes/config.php';
require_role('admin');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM trainers WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        log_activity("Deleted trainer", "trainers", "Trainer ID: $id");
    }
    $stmt->close();
    redirect('trainers.php?msg=6');
}

// Handle add/edit
$errors = [];
$trainer = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM trainers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $trainer = $result->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $specialization = trim($_POST['specialization']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $experience = !empty($_POST['experience']) ? intval($_POST['experience']) : 0;
    $salary = !empty($_POST['salary']) ? floatval($_POST['salary']) : 0.00;
    $join_date = trim($_POST['join_date']);

    if (empty($name) || empty($email)) {
        $errors[] = "Name and email are required.";
    } elseif (!validate_email($email)) {
        $errors[] = "Invalid email format.";
    } else {
        if ($trainer) {
            // Update
            $stmt = $conn->prepare("UPDATE trainers SET name=?, specialization=?, contact=?, email=?, experience=?, salary=?, join_date=? WHERE id=?");
            $stmt->bind_param("sssisdsi", $name, $specialization, $contact, $email, $experience, $salary, $join_date, $trainer['id']);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO trainers (name, specialization, contact, email, experience, salary, join_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssisds", $name, $specialization, $contact, $email, $experience, $salary, $join_date);
        }

        if ($stmt->execute()) {
            $msg = $trainer ? '5' : '4';
            $action = $trainer ? "Updated trainer" : "Added new trainer";
            $trainer_id = $trainer ? $trainer['id'] : $conn->insert_id;
            log_activity($action, "trainers", "Trainer ID: $trainer_id, Name: $name");
            redirect('trainers.php?msg=' . $msg);
        } else {
            $errors[] = "Error saving trainer.";
        }
        $stmt->close();
    }
}

// If POST submission produced errors, repopulate $trainer with submitted values
// so the modal can show the user's input when the page reloads.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($errors)) {
    $trainer = [
        'name' => $name ?? '',
        'specialization' => $specialization ?? '',
        'contact' => $contact ?? '',
        'email' => $email ?? '',
        'experience' => $experience ?? '',
        'salary' => $salary ?? '',
        'join_date' => $join_date ?? date('Y-m-d'),
    ];
}

// Get trainer statistics
$trainer_stats = [];

// Total trainers
$result = $conn->query("SELECT COUNT(*) as total FROM trainers");
$trainer_stats['total_trainers'] = $result->fetch_assoc()['total'];

// Average members per trainer
$result = $conn->query("SELECT AVG(member_count) as avg_members FROM (SELECT COUNT(m.id) as member_count FROM trainers t LEFT JOIN members m ON t.id = m.trainer_id GROUP BY t.id) as trainer_data");
$trainer_stats['avg_members_per_trainer'] = round($result->fetch_assoc()['avg_members'] ?? 0, 1);

// Today's training sessions (attendance records for trainers)
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(DISTINCT m.trainer_id) as active_trainers FROM attendance a JOIN members m ON a.user_id = m.id WHERE a.date = ? AND a.status = 'present'");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$trainer_stats['todays_active_trainers'] = $result->fetch_assoc()['active_trainers'];
$stmt->close();

// Most experienced trainer
$result = $conn->query("SELECT MAX(experience) as max_experience FROM trainers");
$trainer_stats['max_experience'] = $result->fetch_assoc()['max_experience'] ?? 0;

// Average experience
$result = $conn->query("SELECT AVG(experience) as avg_experience FROM trainers WHERE experience IS NOT NULL");
$trainer_stats['avg_experience'] = round($result->fetch_assoc()['avg_experience'] ?? 0, 1);

// Total salary expense
$result = $conn->query("SELECT SUM(salary) as total_salary FROM trainers");
$trainer_stats['total_salary'] = $result->fetch_assoc()['total_salary'] ?? 0;

// Get all trainers
$trainers = $conn->query("SELECT t.*, COUNT(m.id) as member_count FROM trainers t LEFT JOIN members m ON t.id = m.trainer_id GROUP BY t.id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css?v=1.0" rel="stylesheet">
    <link href="../assets/css/custom.css?v=1.0" rel="stylesheet">
    <link href="../assets/css/components.css?v=1.0" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css?v=1.0">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="page-header">
    <h1 class="page-title">Trainer Management</h1>
    <div class="page-options">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#trainerModal">
            <i class="fas fa-plus me-1"></i>Add New Trainer
        </button>
    </div>
</div>

<!-- Trainer Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.1s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Total Trainers</div>
                    <h2 class="info-card-value"><?php echo $trainer_stats['total_trainers']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.2s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Active Today</div>
                    <h2 class="info-card-value"><?php echo $trainer_stats['todays_active_trainers']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.3s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                    <i class="fas fa-user-friends"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Avg Members</div>
                    <h2 class="info-card-value"><?php echo $trainer_stats['avg_members_per_trainer']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.4s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <i class="fas fa-award"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Avg Experience</div>
                    <h2 class="info-card-value"><?php echo $trainer_stats['avg_experience']; ?>y</h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.5s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Max Experience</div>
                    <h2 class="info-card-value"><?php echo $trainer_stats['max_experience']; ?>y</h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.6s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Total Salary</div>
                    <h2 class="info-card-value">₹<?php echo number_format($trainer_stats['total_salary'] / 1000, 0); ?>k</h2>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card-modern">
    <div class="card-body">
        <div class="table-responsive">
            <table id="trainers-table" class="table table-modern" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Specialization</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Experience</th>
                        <th>Salary</th>
                        <th>Members</th>
                        <th data-sortable="false" data-exportable="false">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $trainers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['contact']); ?></td>
                            <td><?php echo htmlspecialchars($row['experience']); ?> years</td>
                            <td>₹<?php echo number_format($row['salary'], 2); ?></td>
                            <td><span class="badge bg-secondary-light"><?php echo $row['member_count']; ?></span></td>
                            <td class="actions">
                                <a href="?edit=<?php echo $row['id']; ?>" class="btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this trainer?');"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
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
            <form method="POST" class="form-modern" id="trainerForm" autocomplete="off">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($trainer['name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($trainer['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Specialization</label>
                            <input type="text" class="form-control" name="specialization" value="<?php echo htmlspecialchars($trainer['specialization'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact</label>
                            <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($trainer['contact'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Experience (years)</label>
                            <input type="number" class="form-control" name="experience" value="<?php echo htmlspecialchars($trainer['experience'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Salary</label>
                            <input type="number" step="0.01" class="form-control" name="salary" value="<?php echo htmlspecialchars($trainer['salary'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Join Date</label>
                            <input type="date" class="form-control" name="join_date" value="<?php echo htmlspecialchars($trainer['join_date'] ?? date('Y-m-d')); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Trainer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('trainers-table');
    if (table) {
        new DataTable(table, {
            searchable: true,
            pagination: true,
            sortable: true,
            exportable: true,
            exportOptions: {
                fileName: 'GMS_Trainers_Export',
            }
        });
    }

    const trainerModalEl = document.getElementById('trainerModal');
    if (trainerModalEl) {
        const modal = new bootstrap.Modal(trainerModalEl);
        <?php if ((isset($_GET['edit']) && is_numeric($_GET['edit'])) || !empty($errors)): ?>
        modal.show();
        <?php endif; ?>
    }
    
    const trainerForm = document.getElementById('trainerForm');
    if(trainerForm) {
        new FormValidator(trainerForm);
    }
});
</script>
</body>
</html>