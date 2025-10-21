<?php
require_once '../includes/config.php';
require_role('trainer');

$user_id = $_SESSION['user_id'];

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $contact = sanitize($_POST['contact']);
    $email = sanitize($_POST['email']);
    $address = sanitize($_POST['address']);
    $specialization = sanitize($_POST['specialization']);
    $experience = sanitize($_POST['experience']);
    $bio = sanitize($_POST['bio']);

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
            $filename = 'trainer_' . $user_id . '_' . time() . '.' . $ext;
            $upload_path = '../assets/images/' . $filename;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $photo_path = 'assets/images/' . $filename;
            } else {
                $errors[] = "Failed to upload photo.";
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE trainers SET name=?, contact=?, email=?, address=?, specialization=?, experience=?, bio=? WHERE id=?");
        $stmt->bind_param("sssssisi", $name, $contact, $email, $address, $specialization, $experience, $bio, $user_id);

        if ($stmt->execute()) {
            if ($photo_path) {
                $conn->query("UPDATE trainers SET photo='$photo_path' WHERE id=$user_id");
            }
            $success = "Profile updated successfully!";
        } else {
            $errors[] = "Failed to update profile.";
        }
        $stmt->close();
    }
}

// Get trainer info
$trainer = $conn->query("SELECT * FROM trainers WHERE id = $user_id")->fetch_assoc();
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
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <?php if (!empty($trainer['photo'])): ?>
                            <img src="../<?php echo $trainer['photo']; ?>" alt="Profile Photo" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px;">
                                <i class="fas fa-user fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>

                        <h4><?php echo $trainer['name']; ?></h4>
                        <p class="text-muted">Trainer</p>

                        <?php if (!empty($trainer['specialization'])): ?>
                            <p class="text-primary"><strong><?php echo $trainer['specialization']; ?></strong></p>
                        <?php endif; ?>

                        <div class="row text-center mt-3">
                            <div class="col-6">
                                <strong><?php echo $trainer['experience']; ?> Years</strong>
                                <br><small class="text-muted">Experience</small>
                            </div>
                            <div class="col-6">
                                <strong><?php
                                $member_count = $conn->query("SELECT COUNT(*) as count FROM members WHERE trainer_id = $user_id AND status = 'active'")->fetch_assoc()['count'];
                                echo $member_count;
                                ?></strong>
                                <br><small class="text-muted">Active Members</small>
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
                                        <input type="text" class="form-control" name="name" value="<?php echo $trainer['name']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo $trainer['email']; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo $trainer['contact']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Experience (Years)</label>
                                        <input type="number" class="form-control" name="experience" value="<?php echo $trainer['experience']; ?>" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Specialization</label>
                                <input type="text" class="form-control" name="specialization" value="<?php echo $trainer['specialization']; ?>" placeholder="e.g., Weight Training, Cardio, Yoga">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"><?php echo $trainer['address']; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Bio</label>
                                <textarea class="form-control" name="bio" rows="3" placeholder="Tell us about yourself..."><?php echo $trainer['bio']; ?></textarea>
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

        <!-- Statistics -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Training Statistics</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stats = $conn->query("
                            SELECT
                                COUNT(DISTINCT m.id) as total_members,
                                COUNT(DISTINCT wp.id) as workout_plans,
                                COUNT(DISTINCT dp.id) as diet_plans,
                                COUNT(DISTINCT a.id) as attendance_records
                            FROM members m
                            LEFT JOIN workout_plans wp ON m.id = wp.member_id
                            LEFT JOIN diet_plans dp ON m.id = dp.member_id
                            LEFT JOIN attendance a ON m.id = a.user_id
                            WHERE m.trainer_id = $user_id
                        ")->fetch_assoc();
                        ?>

                        <div class="row text-center">
                            <div class="col-6">
                                <h3 class="text-primary"><?php echo $stats['total_members']; ?></h3>
                                <small class="text-muted">Total Members</small>
                            </div>
                            <div class="col-6">
                                <h3 class="text-success"><?php echo $stats['workout_plans']; ?></h3>
                                <small class="text-muted">Workout Plans</small>
                            </div>
                        </div>
                        <hr>
                        <div class="row text-center">
                            <div class="col-6">
                                <h3 class="text-info"><?php echo $stats['diet_plans']; ?></h3>
                                <small class="text-muted">Diet Plans</small>
                            </div>
                            <div class="col-6">
                                <h3 class="text-warning"><?php echo $stats['attendance_records']; ?></h3>
                                <small class="text-muted">Attendance Records</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $recent_activity = $conn->query("
                            SELECT 'workout' as type, wp.title, wp.created_at, m.name as member_name
                            FROM workout_plans wp
                            JOIN members m ON wp.member_id = m.id
                            WHERE m.trainer_id = $user_id
                            UNION ALL
                            SELECT 'diet' as type, dp.title, dp.created_at, m.name as member_name
                            FROM diet_plans dp
                            JOIN members m ON dp.member_id = m.id
                            WHERE m.trainer_id = $user_id
                            ORDER BY created_at DESC LIMIT 5
                        ");

                        if ($recent_activity->num_rows > 0):
                            while ($activity = $recent_activity->fetch_assoc()):
                        ?>
                            <div class="d-flex align-items-center mb-2">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-<?php echo $activity['type'] == 'workout' ? 'dumbbell' : 'utensils'; ?> text-<?php echo $activity['type'] == 'workout' ? 'primary' : 'success'; ?> me-2"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <small class="text-muted"><?php echo ucfirst($activity['type']); ?> plan for <?php echo $activity['member_name']; ?></small>
                                    <br><small class="text-muted"><?php echo date('M j, Y', strtotime($activity['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php
                            endwhile;
                        else:
                        ?>
                            <p class="text-muted mb-0">No recent activity</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>
    </div>

</body>
</html>