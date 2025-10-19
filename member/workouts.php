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
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-dumbbell me-2"></i>My Workout Plans</h2>
        </div>

        <?php if ($workout_plans->num_rows > 0): ?>
            <div class="row">
                <?php while ($plan = $workout_plans->fetch_assoc()): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><?php echo $plan['title']; ?></h5>
                                <small>Created by: <?php echo $plan['trainer_name'] ?: 'Admin'; ?></small>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($plan['description'])): ?>
                                    <p class="text-muted"><?php echo nl2br($plan['description']); ?></p>
                                <?php endif; ?>

                                <div class="row mb-3">
                                    <div class="col-6">
                                        <strong>Duration:</strong><br>
                                        <?php echo $plan['duration']; ?> weeks
                                    </div>
                                    <div class="col-6">
                                        <strong>Level:</strong><br>
                                        <span class="badge bg-<?php
                                            echo $plan['difficulty'] == 'beginner' ? 'success' :
                                                 ($plan['difficulty'] == 'intermediate' ? 'warning' : 'danger');
                                        ?>">
                                            <?php echo ucfirst($plan['difficulty']); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <strong>Exercises:</strong>
                                    <?php
                                    $exercises = json_decode($plan['exercises'], true);
                                    if ($exercises && is_array($exercises)):
                                    ?>
                                        <ul class="list-group list-group-flush mt-2">
                                            <?php foreach ($exercises as $exercise): ?>
                                                <li class="list-group-item px-0 py-1">
                                                    <strong><?php echo $exercise['name']; ?></strong>
                                                    <?php if (!empty($exercise['sets']) && !empty($exercise['reps'])): ?>
                                                        - <?php echo $exercise['sets']; ?> sets × <?php echo $exercise['reps']; ?> reps
                                                    <?php endif; ?>
                                                    <?php if (!empty($exercise['duration'])): ?>
                                                        - <?php echo $exercise['duration']; ?> minutes
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No exercises specified</p>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($plan['notes'])): ?>
                                    <div class="alert alert-info">
                                        <strong>Notes:</strong><br>
                                        <?php echo nl2br($plan['notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer text-muted">
                                <small>Created: <?php echo date('M j, Y', strtotime($plan['created_at'])); ?></small>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-dumbbell fa-4x text-muted mb-3"></i>
                <h4>No Workout Plans Yet</h4>
                <p class="text-muted">Your trainer hasn't assigned any workout plans to you yet.</p>
                <p class="text-muted">Contact your trainer or gym administrator for personalized workout plans.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>