<?php
require_once '../includes/config.php';
require_role('trainer');

$user_id = $_SESSION['user_id'];
$selected_member = isset($_GET['member']) ? sanitize($_GET['member']) : null;

// Handle workout plan creation
if (isset($_POST['create_workout'])) {
    $member_id = sanitize($_POST['member_id']);
    $description = sanitize($_POST['workout_description']);

    $stmt = $conn->prepare("INSERT INTO workout_plans (trainer_id, member_id, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $member_id, $description);

    if ($stmt->execute()) {
        $success = "Workout plan created successfully!";
    } else {
        $errors[] = "Failed to create workout plan.";
    }
    $stmt->close();
}

// Handle diet plan creation
if (isset($_POST['create_diet'])) {
    $member_id = sanitize($_POST['member_id']);
    $description = sanitize($_POST['diet_description']);

    $stmt = $conn->prepare("INSERT INTO diet_plans (trainer_id, member_id, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $member_id, $description);

    if ($stmt->execute()) {
        $success = "Diet plan created successfully!";
    } else {
        $errors[] = "Failed to create diet plan.";
    }
    $stmt->close();
}

// Get assigned members
$members = $conn->query("SELECT id, name FROM members WHERE trainer_id = $user_id ORDER BY name");

// Get existing plans
$workout_plans = $conn->query("
    SELECT wp.*, m.name as member_name
    FROM workout_plans wp
    JOIN members m ON wp.member_id = m.id
    WHERE wp.trainer_id = $user_id
    ORDER BY wp.created_at DESC
");

$diet_plans = $conn->query("
    SELECT dp.*, m.name as member_name
    FROM diet_plans dp
    JOIN members m ON dp.member_id = m.id
    WHERE dp.trainer_id = $user_id
    ORDER BY dp.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Plans - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <h2><i class="fas fa-clipboard-list me-2"></i>Manage Workout & Diet Plans</h2>

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

        <div class="row">
            <!-- Create Plans -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-dumbbell me-2"></i>Create Workout Plan</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Select Member</label>
                                <select class="form-control" name="member_id" required>
                                    <option value="">Choose Member</option>
                                    <?php
                                    $members->data_seek(0);
                                    while ($member = $members->fetch_assoc()): ?>
                                        <option value="<?php echo $member['id']; ?>" <?php echo ($selected_member == $member['id']) ? 'selected' : ''; ?>>
                                            <?php echo $member['name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Workout Description</label>
                                <textarea class="form-control" name="workout_description" rows="4" placeholder="Describe the workout routine, exercises, sets, reps, etc." required></textarea>
                            </div>
                            <button type="submit" name="create_workout" class="btn btn-modern w-100">
                                <i class="fas fa-plus me-2"></i>Create Workout Plan
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-utensils me-2"></i>Create Diet Plan</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Select Member</label>
                                <select class="form-control" name="member_id" required>
                                    <option value="">Choose Member</option>
                                    <?php
                                    $members->data_seek(0);
                                    while ($member = $members->fetch_assoc()): ?>
                                        <option value="<?php echo $member['id']; ?>" <?php echo ($selected_member == $member['id']) ? 'selected' : ''; ?>>
                                            <?php echo $member['name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Diet Description</label>
                                <textarea class="form-control" name="diet_description" rows="4" placeholder="Describe the diet plan, meals, calories, nutritional advice, etc." required></textarea>
                            </div>
                            <button type="submit" name="create_diet" class="btn btn-modern w-100">
                                <i class="fas fa-plus me-2"></i>Create Diet Plan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Existing Plans -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-dumbbell me-2"></i>Workout Plans Created</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($plan = $workout_plans->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $plan['member_name']; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($plan['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info" onclick="viewPlan('workout', <?php echo $plan['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
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
                        <h5 class="mb-0"><i class="fas fa-utensils me-2"></i>Diet Plans Created</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($plan = $diet_plans->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $plan['member_name']; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($plan['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info" onclick="viewPlan('diet', <?php echo $plan['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
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

    <!-- Plan View Modal -->
    <div class="modal fade" id="planModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="planModalTitle">Plan Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="planContent">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewPlan(type, id) {
            const modal = new bootstrap.Modal(document.getElementById('planModal'));
            const title = document.getElementById('planModalTitle');
            const content = document.getElementById('planContent');

            title.textContent = type.charAt(0).toUpperCase() + type.slice(1) + ' Plan Details';

            // In a real application, you'd fetch the plan details via AJAX
            // For now, we'll show a placeholder
            content.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Plan details would be loaded here via AJAX call to fetch ${type} plan with ID: ${id}
                </div>
                <p>This would display the full workout or diet plan description, including exercises, meals, schedules, etc.</p>
            `;

            modal.show();
        }
    </script>
        </div>
    </div>
    </div>
</body>
</html>