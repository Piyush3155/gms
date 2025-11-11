<?php
require_once '../includes/config.php';
require_role('member');

$user_id = $_SESSION['user_id'];

// Get member's workout plans
$workout_plans = $conn->query("
    SELECT wp.*, t.name as trainer_name
    FROM workout_plans wp
    LEFT JOIN trainers t ON wp.trainer_id = t.id
    WHERE wp.member_id = $user_id
    ORDER BY wp.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Workout Plans - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
            <!-- Modern Page Header -->
            <div class="page-title-section mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1><i class="fas fa-dumbbell me-3"></i>My Workout Plans</h1>
                        <p class="lead mb-0">Personalized training programs designed just for you</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="<?php echo SITE_URL; ?>member/index.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($workout_plans->num_rows > 0): ?>
                <div class="row">
                    <?php while ($plan = $workout_plans->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-6 mb-4 fade-in">
                            <div class="workout-card">
                                <div class="workout-card-header">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-2">
                                                <i class="fas fa-fire me-2"></i><?php echo $plan['title']; ?>
                                            </h5>
                                            <small>
                                                <i class="fas fa-user-tie me-1"></i>
                                                Created by: <?php echo $plan['trainer_name'] ?: 'Admin'; ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-light text-dark" style="font-size: 0.875rem;">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?php echo date('M j, Y', strtotime($plan['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="workout-card-body">
                                    <?php if (!empty($plan['description'])): ?>
                                        <div class="alert alert-modern alert-info mb-3">
                                            <i class="fas fa-info-circle"></i>
                                            <div>
                                                <strong>Description:</strong><br>
                                                <?php echo nl2br($plan['description']); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <div class="info-card">
                                                <div class="info-card-top">
                                                    <div class="info-card-icon">
                                                        <i class="fas fa-clock"></i>
                                                    </div>
                                                    <div class="info-card-content">
                                                        <div class="info-card-title">Duration</div>
                                                        <h2 class="info-card-value"><?php echo $plan['duration']; ?> weeks</h2>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="info-card">
                                                <div class="info-card-top">
                                                    <div class="info-card-icon">
                                                        <i class="fas fa-chart-line"></i>
                                                    </div>
                                                    <div class="info-card-content">
                                                        <div class="info-card-title">Level</div>
                                                        <div class="info-card-value">
                                                            <span class="badge badge-status badge-<?php
                                                                echo $plan['difficulty'] == 'beginner' ? 'active' :
                                                                     ($plan['difficulty'] == 'intermediate' ? 'pending' : 'inactive');
                                                            ?>">
                                                                <?php echo ucfirst($plan['difficulty']); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <h6 class="text-gradient" style="font-weight: 700; margin-bottom: 1rem;">
                                            <i class="fas fa-list-ul me-2"></i>Exercise Routine
                                        </h6>
                                        <?php
                                        $exercises = json_decode($plan['exercises'], true);
                                        if ($exercises && is_array($exercises)):
                                        ?>
                                            <ul class="exercise-list">
                                                <?php foreach ($exercises as $index => $exercise): ?>
                                                    <li class="exercise-item" style="animation-delay: <?php echo ($index * 0.1); ?>s;">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <strong>
                                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                                    <?php echo $exercise['name']; ?>
                                                                </strong>
                                                                <div class="exercise-meta mt-1">
                                                                    <?php if (!empty($exercise['sets']) && !empty($exercise['reps'])): ?>
                                                                        <i class="fas fa-repeat me-1"></i><?php echo $exercise['sets']; ?> sets × <?php echo $exercise['reps']; ?> reps
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($exercise['duration'])): ?>
                                                                        <i class="fas fa-stopwatch me-1 ms-2"></i><?php echo $exercise['duration']; ?> minutes
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <div class="empty-state-modern" style="padding: 2rem;">
                                                <div class="icon" style="width: 60px; height: 60px; margin: 0 auto 1rem; font-size: 2rem;">
                                                    <i class="fas fa-dumbbell"></i>
                                                </div>
                                                <p class="mb-0">No exercises specified</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($plan['notes'])): ?>
                                        <div class="alert alert-modern alert-warning mt-3">
                                            <i class="fas fa-sticky-note"></i>
                                            <div>
                                                <strong>Trainer's Notes:</strong><br>
                                                <?php echo nl2br($plan['notes']); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state-modern">
                    <div class="icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h3>No Workout Plans Yet</h3>
                    <p>Your trainer hasn't assigned any workout plans to you yet.<br>
                    Contact your trainer or gym administrator for personalized workout plans.</p>
                    <a href="<?php echo SITE_URL; ?>member/index.php" class="btn btn-modern">
                        <i class="fas fa-home me-2"></i>Back to Dashboard
                    </a>
                </div>
            <?php endif; ?>

            <?php include '../includes/footer.php'; ?>
        </div>
    </div>
    </div>

</body>
</html>