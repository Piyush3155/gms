<?php
require_once '../includes/config.php';
require_role('member');

$user_id = $_SESSION['user_id'];

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $contact = sanitize($_POST['contact']);
    $email = sanitize($_POST['email']);
    $address = sanitize($_POST['address']);
    $dob = sanitize($_POST['dob']);
    $gender = sanitize($_POST['gender']);

    // Handle photo upload
    $photo_path = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($_FILES['photo']['type'], $allowed_types)) {
            $errors[] = "Only JPG, PNG, and GIF files are allowed.";
        } elseif ($_FILES['photo']['size'] > $max_size) {
            $errors[] = "File size must be less than 2MB.";
        } else {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'member_' . $user_id . '_' . time() . '.' . $ext;
            $upload_path = '../assets/images/' . $filename;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $photo_path = 'assets/images/' . $filename;
            } else {
                $errors[] = "Failed to upload photo.";
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE members SET name=?, contact=?, email=?, address=?, dob=?, gender=? WHERE id=?");
        $stmt->bind_param("ssssssi", $name, $contact, $email, $address, $dob, $gender, $user_id);

        if ($stmt->execute()) {
            if ($photo_path) {
                $conn->query("UPDATE members SET photo='$photo_path' WHERE id=$user_id");
            }
            $success = "Profile updated successfully!";
        } else {
            $errors[] = "Failed to update profile.";
        }
        $stmt->close();
    }
}

// Get member info
$member = $conn->query("SELECT m.*, p.name as plan_name, t.name as trainer_name FROM members m LEFT JOIN plans p ON m.plan_id = p.id LEFT JOIN trainers t ON m.trainer_id = t.id WHERE m.id = $user_id")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <?php if (!empty($member['photo'])): ?>
                            <img src="../<?php echo $member['photo']; ?>" alt="Profile Photo" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px;">
                                <i class="fas fa-user fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>

                        <h4><?php echo $member['name']; ?></h4>
                        <p class="text-muted">Member</p>

                        <div class="row text-center mt-3">
                            <div class="col-6">
                                <strong><?php echo $member['plan_name'] ?: 'No Plan'; ?></strong>
                                <br><small class="text-muted">Plan</small>
                            </div>
                            <div class="col-6">
                                <strong><?php echo $member['trainer_name'] ?: 'Not Assigned'; ?></strong>
                                <br><small class="text-muted">Trainer</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Profile</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo $member['name']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo $member['email']; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo $member['contact']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" name="dob" value="<?php echo $member['dob']; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-control" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo ($member['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($member['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($member['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="3"><?php echo $member['address']; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Profile Photo</label>
                                <input type="file" class="form-control" name="photo" accept="image/*">
                                <div class="form-text">Upload JPG, PNG, or GIF file (max 2MB)</div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="update_profile" class="btn btn-modern">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Membership Info -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Membership Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <strong>Join Date:</strong><br>
                                <?php echo date('M j, Y', strtotime($member['join_date'])); ?>
                            </div>
                            <div class="col-6">
                                <strong>Status:</strong><br>
                                <span class="badge bg-<?php echo $member['status'] == 'active' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($member['status']); ?>
                                </span>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-6">
                                <strong>Current Plan:</strong><br>
                                <?php echo $member['plan_name'] ?: 'No Plan Assigned'; ?>
                            </div>
                            <div class="col-6">
                                <strong>Assigned Trainer:</strong><br>
                                <?php echo $member['trainer_name'] ?: 'Not Assigned'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Quick Stats</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get some quick stats
                        $this_month_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE user_id = $user_id AND DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') AND status = 'present'")->fetch_assoc()['count'];
                        $total_workouts = $conn->query("SELECT COUNT(*) as count FROM workout_plans WHERE member_id = $user_id")->fetch_assoc()['count'];
                        $total_diets = $conn->query("SELECT COUNT(*) as count FROM diet_plans WHERE member_id = $user_id")->fetch_assoc()['count'];
                        ?>

                        <div class="row text-center">
                            <div class="col-4">
                                <h3 class="text-primary"><?php echo $this_month_attendance; ?></h3>
                                <small class="text-muted">This Month<br>Attendance</small>
                            </div>
                            <div class="col-4">
                                <h3 class="text-success"><?php echo $total_workouts; ?></h3>
                                <small class="text-muted">Workout<br>Plans</small>
                            </div>
                            <div class="col-4">
                                <h3 class="text-info"><?php echo $total_diets; ?></h3>
                                <small class="text-muted">Diet<br>Plans</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>