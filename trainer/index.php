<?php
require_once '../includes/config.php';
require_role('trainer');

$user_id = $_SESSION['user_id'];

// Get trainer info
$trainer = $conn->query("SELECT * FROM trainers WHERE id = $user_id")->fetch_assoc();

// Get assigned members
$members = $conn->query("SELECT m.*, p.name as plan_name FROM members m LEFT JOIN plans p ON m.plan_id = p.id WHERE m.trainer_id = $user_id");

// Get today's attendance
$today = date('Y-m-d');
$today_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE user_id = $user_id AND date = '$today' AND status = 'present'")->fetch_assoc()['count'];

// Get workout plans created
$workout_plans = $conn->query("SELECT COUNT(*) as count FROM workout_plans WHERE trainer_id = $user_id")->fetch_assoc()['count'];

// Get diet plans created
$diet_plans = $conn->query("SELECT COUNT(*) as count FROM diet_plans WHERE trainer_id = $user_id")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <h2>Welcome, <?php echo $trainer['name']; ?>!</h2>
                <p class="text-muted"><?php echo $trainer['specialization']; ?> Trainer</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="card">
                    <div class="card-body">
                        <h6>Experience</h6>
                        <h4><?php echo $trainer['experience']; ?> years</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mt-4">
            <div class="col-md-3 mb-4">
                <div class="stats-card text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users me-2"></i>My Members</h5>
                        <h2><?php echo $members->num_rows; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stats-card text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-calendar-check me-2"></i>Today's Attendance</h5>
                        <h2><?php echo $today_attendance ? 'Present' : 'Not Marked'; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stats-card text-white" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-dumbbell me-2"></i>Workout Plans</h5>
                        <h2><?php echo $workout_plans; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stats-card text-white" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-utensils me-2"></i>Diet Plans</h5>
                        <h2><?php echo $diet_plans; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Members -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>My Members</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Plan</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($member = $members->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $member['name']; ?></td>
                                    <td><?php echo $member['email']; ?></td>
                                    <td><?php echo $member['contact']; ?></td>
                                    <td><?php echo $member['plan_name'] ?: 'No Plan'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $member['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($member['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="plans.php?member=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-plus me-1"></i>Plans
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>