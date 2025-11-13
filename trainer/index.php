<?php
require_once '../includes/config.php';
require_role('trainer');

$user_id = $_SESSION['user_id'];

// Get trainer info
$trainer = $conn->query("SELECT * FROM trainers WHERE id = $user_id")->fetch_assoc();

// Get assigned members count
$assigned_members = $conn->query("SELECT COUNT(*) as count FROM members WHERE trainer_id = $user_id")->fetch_assoc()['count'];

// Get active members count
$active_members = $conn->query("SELECT COUNT(*) as count FROM members WHERE trainer_id = $user_id AND status = 'active'")->fetch_assoc()['count'];

// Get today's attendance
$today = date('Y-m-d');
$today_sessions = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE user_id = $user_id AND date = '$today' AND status = 'present'")->fetch_assoc()['count'];

// Get workout plans created
$workout_plans_count = $conn->query("SELECT COUNT(*) as count FROM workout_plans WHERE trainer_id = $user_id")->fetch_assoc()['count'];

// Get diet plans created
$diet_plans_count = $conn->query("SELECT COUNT(*) as count FROM diet_plans WHERE trainer_id = $user_id")->fetch_assoc()['count'];

// Get upcoming classes
$upcoming_classes = $conn->query("SELECT gc.*, COUNT(cb.id) as bookings FROM group_classes gc LEFT JOIN class_bookings cb ON gc.id = cb.class_id WHERE gc.trainer_id = $user_id AND gc.class_date >= CURDATE() GROUP BY gc.id ORDER BY gc.class_date, gc.start_time LIMIT 5");

// Get recent members
$recent_members = $conn->query("SELECT m.name, m.join_date FROM members m WHERE m.trainer_id = $user_id ORDER BY m.join_date DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css?v=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Welcome back, <?php echo $trainer['name']; ?>!</h2>
            <div>
                <span class="text-muted"><?php echo $trainer['specialization']; ?> Trainer</span>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card enhanced-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <h4><?php echo $assigned_members; ?></h4>
                        <p class="text-muted mb-0">Assigned Members</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card enhanced-card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-check fa-2x text-success mb-2"></i>
                        <h4><?php echo $active_members; ?></h4>
                        <p class="text-muted mb-0">Active Members</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card enhanced-card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-2x text-info mb-2"></i>
                        <h4><?php echo $today_sessions; ?></h4>
                        <p class="text-muted mb-0">Today's Sessions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card enhanced-card">
                    <div class="card-body text-center">
                        <i class="fas fa-award fa-2x text-warning mb-2"></i>
                        <h4><?php echo $trainer['experience']; ?> yrs</h4>
                        <p class="text-muted mb-0">Experience</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card enhanced-card">
                    <div class="card-body text-center">
                        <i class="fas fa-dumbbell fa-2x text-secondary mb-2"></i>
                        <h4><?php echo $workout_plans_count; ?></h4>
                        <p class="text-muted mb-0">Workout Plans Created</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card enhanced-card">
                    <div class="card-body text-center">
                        <i class="fas fa-utensils fa-2x text-dark mb-2"></i>
                        <h4><?php echo $diet_plans_count; ?></h4>
                        <p class="text-muted mb-0">Diet Plans Created</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-md-6">
                <div class="card enhanced-card">
                    <div class="card-header">
                        <h5>Upcoming Classes</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($upcoming_classes->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($class = $upcoming_classes->fetch_assoc()): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo $class['name']; ?></h6>
                                            <small class="text-muted"><?php echo date('M d', strtotime($class['class_date'])); ?> <?php echo date('H:i', strtotime($class['start_time'])); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo substr($class['description'], 0, 80) . (strlen($class['description']) > 80 ? '...' : ''); ?></p>
                                        <small class="text-muted">Capacity: <?php echo $class['bookings']; ?>/<?php echo $class['capacity']; ?></small>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No upcoming classes scheduled.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card enhanced-card">
                    <div class="card-header">
                        <h5>Recent Members</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_members->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($member = $recent_members->fetch_assoc()): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo $member['name']; ?></h6>
                                            <small class="text-muted">Joined <?php echo date('M d, Y', strtotime($member['join_date'])); ?></small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No members assigned yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card enhanced-card">
                    <div class="card-header">
                        <h5>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <a href="index.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-users me-2"></i>My Members
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="plans.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-clipboard-list me-2"></i>Training Plans
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="attendance.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-calendar-check me-2"></i>Mark Attendance
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="profile.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-user me-2"></i>My Profile
                                </a>
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