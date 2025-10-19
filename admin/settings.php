<?php
require_once '../includes/config.php';
require_role('admin');

// Handle form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $gym_name = sanitize($_POST['gym_name']);
    $contact = sanitize($_POST['contact']);
    $address = sanitize($_POST['address']);
    $email = sanitize($_POST['email']);
    $tagline = sanitize($_POST['tagline']);

    // Handle logo upload
    $logo_path = '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($_FILES['logo']['type'], $allowed_types)) {
            $errors[] = "Only JPG, PNG, and GIF files are allowed.";
        } elseif ($_FILES['logo']['size'] > $max_size) {
            $errors[] = "File size must be less than 2MB.";
        } else {
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . '.' . $ext;
            $upload_path = '../assets/images/' . $filename;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                $logo_path = 'assets/images/' . $filename;
            } else {
                $errors[] = "Failed to upload logo.";
            }
        }
    }

    if (empty($gym_name)) {
        $errors[] = "Gym name is required.";
    }

    if (empty($errors)) {
        // Check if settings exist
        $result = $conn->query("SELECT id FROM settings LIMIT 1");
        if ($result->num_rows > 0) {
            // Update existing
            $stmt = $conn->prepare("UPDATE settings SET gym_name=?, contact=?, address=?, email=?, tagline=? WHERE id=1");
            $stmt->bind_param("sssss", $gym_name, $contact, $address, $email, $tagline);
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO settings (gym_name, contact, address, email, tagline) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $gym_name, $contact, $address, $email, $tagline);
        }

        if ($stmt->execute()) {
            // Update logo if uploaded
            if ($logo_path) {
                $conn->query("UPDATE settings SET logo='$logo_path' WHERE id=1");
            }
            $success = "Settings updated successfully!";
        } else {
            $errors[] = "Failed to update settings.";
        }
        $stmt->close();
    }
}

// Get current settings
$settings = $conn->query("SELECT * FROM settings LIMIT 1")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Settings - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-cog me-2"></i>Gym Settings</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Gym Name *</label>
                                        <input type="text" class="form-control" name="gym_name" value="<?php echo $settings['gym_name'] ?? ''; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo $settings['contact'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" value="<?php echo $settings['email'] ?? ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="3"><?php echo $settings['address'] ?? ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tagline</label>
                                <input type="text" class="form-control" name="tagline" value="<?php echo $settings['tagline'] ?? ''; ?>" placeholder="e.g., Train Hard, Stay Fit">
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
                                <button type="submit" class="btn btn-modern">
                                    <i class="fas fa-save me-2"></i>Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>