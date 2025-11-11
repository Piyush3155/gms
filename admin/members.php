<?php
require_once '../includes/config.php';
require_role('admin');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM members WHERE id = $id");
    redirect('members.php?msg=3');
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
    $expiry_date = sanitize($_POST['expiry_date']);
    $plan_id = sanitize($_POST['plan_id']);
    $trainer_id = sanitize($_POST['trainer_id']);
    $status = sanitize($_POST['status']);

    if (empty($name) || empty($email)) {
        $errors[] = "Name and email are required.";
    } else {
        if ($member) {
            // Update
            $stmt = $conn->prepare("UPDATE members SET name=?, gender=?, dob=?, contact=?, email=?, address=?, join_date=?, expiry_date=?, plan_id=?, trainer_id=?, status=? WHERE id=?");
            $stmt->bind_param("sssssssssssi", $name, $gender, $dob, $contact, $email, $address, $join_date, $expiry_date, $plan_id, $trainer_id, $status, $member['id']);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO members (name, gender, dob, contact, email, address, join_date, expiry_date, plan_id, trainer_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssss", $name, $gender, $dob, $contact, $email, $address, $join_date, $expiry_date, $plan_id, $trainer_id, $status);
        }

        if ($stmt->execute()) {
            if (!$member) {
                // New member added - generate admission receipt
                $new_member_id = $conn->insert_id;
                
                // Generate QR code
                $qr_code = 'GMS_MEMBER_' . $new_member_id . '_' . md5($new_member_id . $email);
                $update_stmt = $conn->prepare("UPDATE members SET qr_code = ? WHERE id = ?");
                $update_stmt->bind_param("si", $qr_code, $new_member_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Redirect to generate PDF receipt
                redirect("generate_admission_receipt.php?member_id=$new_member_id&msg=1");
            } else {
                redirect('members.php?msg=2');
            }
        } else {
            $errors[] = "Error saving member.";
        }
        $stmt->close();
    }
}

// Get member statistics
$member_stats = [];

// Total members
$result = $conn->query("SELECT COUNT(*) as total FROM members");
$member_stats['total_members'] = $result->fetch_assoc()['total'];

// Active members
$result = $conn->query("SELECT COUNT(*) as total FROM members WHERE status = 'active'");
$member_stats['active_members'] = $result->fetch_assoc()['total'];

// Recent registrations (last 7 days)
$result = $conn->query("SELECT COUNT(*) as total FROM members WHERE join_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$member_stats['recent_registrations'] = $result->fetch_assoc()['total'];

// Expiring soon (next 30 days)
$result = $conn->query("SELECT COUNT(*) as total FROM members WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE() AND status = 'active'");
$member_stats['expiring_soon'] = $result->fetch_assoc()['total'];

// Inactive members
$result = $conn->query("SELECT COUNT(*) as total FROM members WHERE status = 'inactive'");
$member_stats['inactive_members'] = $result->fetch_assoc()['total'];

// Average age
$result = $conn->query("SELECT AVG(TIMESTAMPDIFF(YEAR, dob, CURDATE())) as avg_age FROM members WHERE dob IS NOT NULL");
$member_stats['avg_age'] = round($result->fetch_assoc()['avg_age'] ?? 0);

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="../assets/css/components.css" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="page-header">
    <h1 class="page-title">Member Management</h1>
    <div class="page-options">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#memberModal">
            <i class="fas fa-plus me-1"></i>Add New Member
        </button>
    </div>
</div>

<!-- Member Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.1s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Total Members</div>
                    <h2 class="info-card-value"><?php echo $member_stats['total_members']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.2s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Active Members</div>
                    <h2 class="info-card-value"><?php echo $member_stats['active_members']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.3s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Recent (7 days)</div>
                    <h2 class="info-card-value"><?php echo $member_stats['recent_registrations']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.4s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Expiring Soon</div>
                    <h2 class="info-card-value"><?php echo $member_stats['expiring_soon']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.5s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Inactive</div>
                    <h2 class="info-card-value"><?php echo $member_stats['inactive_members']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="info-card fade-in" style="animation-delay: 0.6s;">
            <div class="info-card-top">
                <div class="info-card-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                    <i class="fas fa-birthday-cake"></i>
                </div>
                <div class="info-card-content">
                    <div class="info-card-title">Avg Age</div>
                    <h2 class="info-card-value"><?php echo $member_stats['avg_age']; ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>

        <div class="card-modern">
    <div class="card-body">
        <div class="table-responsive">
            <table id="members-table" class="table table-modern" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Plan</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th data-sortable="false" data-exportable="false">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $members->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['contact']); ?></td>
                            <td><?php echo htmlspecialchars($row['plan_name']); ?></td>
                            <td><?php echo $row['expiry_date'] ? date('M d, Y', strtotime($row['expiry_date'])) : 'N/A'; ?></td>
                            <td><span class="status-indicator <?php echo $row['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                            <td class="actions">
                                <a href="?edit=<?php echo $row['id']; ?>" class="btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="renew_membership.php?member_id=<?php echo $row['id']; ?>" class="btn-icon" title="Renew Membership"><i class="bi bi-arrow-clockwise"></i></a>
                                <a href="generate_admission_receipt.php?member_id=<?php echo $row['id']; ?>" class="btn-icon" title="Receipt" target="_blank"><i class="bi bi-receipt"></i></a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
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
                <form method="POST" class="form-modern" autocomplete="off" id="memberForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($member['name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo ($member['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($member['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($member['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="dob" value="<?php echo htmlspecialchars($member['dob'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact</label>
                                <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($member['contact'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Join Date</label>
                                <input type="date" class="form-control" name="join_date" value="<?php echo htmlspecialchars($member['join_date'] ?? date('Y-m-d')); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" name="expiry_date" value="<?php echo htmlspecialchars($member['expiry_date'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($member['address'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Plan</label>
                                <select class="form-select" name="plan_id">
                                    <option value="">Select Plan</option>
                                    <?php mysqli_data_seek($plans, 0); while ($plan = $plans->fetch_assoc()): ?>
                                        <option value="<?php echo $plan['id']; ?>" <?php echo ($member['plan_id'] ?? '') == $plan['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($plan['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Trainer</label>
                                <select class="form-select" name="trainer_id">
                                    <option value="">Select Trainer</option>
                                    <?php mysqli_data_seek($trainers, 0); while ($trainer = $trainers->fetch_assoc()): ?>
                                        <option value="<?php echo $trainer['id']; ?>" <?php echo ($member['trainer_id'] ?? '') == $trainer['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($trainer['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active" <?php echo ($member['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="expired" <?php echo ($member['status'] ?? '') == 'expired' ? 'selected' : ''; ?>>Expired</option>
                                    <option value="inactive" <?php echo ($member['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('members-table');
    if (table) {
        new DataTable(table, {
            searchable: true,
            pagination: true,
            sortable: true,
            exportable: true,
            exportOptions: {
                fileName: 'GMS_Members_Export',
            }
        });
    }

    const memberModalEl = document.getElementById('memberModal');
    if (memberModalEl) {
        const modalManager = new ModalManager();
        const memberModal = new bootstrap.Modal(memberModalEl);

        // Handle the "Add New Member" button to open the modal
        const addMemberBtn = document.querySelector('[data-bs-target="#memberModal"]');
        if (addMemberBtn) {
            addMemberBtn.addEventListener('click', function () {
                const form = memberModalEl.querySelector('form');
                form.reset();
                form.action = '';
                memberModalEl.querySelector('.modal-title').textContent = 'Add New Member';
                // Any other reset logic for the form
            });
        }

        // If PHP indicates an edit or errors, show the modal on page load
        <?php if ((isset($_GET['edit']) && is_numeric($_GET['edit'])) || !empty($errors)): ?>
        memberModal.show();
        <?php endif; ?>
    }
    
    // Initialize form validation
    const memberForm = document.getElementById('memberForm');
    if(memberForm) {
        new FormValidator(memberForm);
    }
});
</script>
</body>
</html>