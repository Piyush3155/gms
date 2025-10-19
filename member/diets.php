<?php
require_once '../includes/config.php';
require_role('member');

$user_id = $_SESSION['user_id'];

// Get member's diet plans
$diet_plans = $conn->query("
    SELECT dp.*, t.name as trainer_name
    FROM diet_plans dp
    LEFT JOIN trainers t ON dp.trainer_id = t.id
    WHERE dp.member_id = $user_id
    ORDER BY dp.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Diet Plans - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-utensils me-2"></i>My Diet Plans</h2>
        </div>

        <?php if ($diet_plans->num_rows > 0): ?>
            <div class="row">
                <?php while ($plan = $diet_plans->fetch_assoc()): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
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
                                        <strong>Goal:</strong><br>
                                        <span class="badge bg-<?php
                                            echo $plan['goal'] == 'weight_loss' ? 'warning' :
                                                 ($plan['goal'] == 'weight_gain' ? 'info' :
                                                  ($plan['goal'] == 'maintenance' ? 'secondary' : 'primary'));
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $plan['goal'])); ?>
                                        </span>
                                    </div>
                                </div>

                                <?php
                                $meals = json_decode($plan['meals'], true);
                                if ($meals && is_array($meals)):
                                    foreach ($meals as $meal_type => $meal_data):
                                        if (!empty($meal_data)):
                                ?>
                                    <div class="mb-3">
                                        <strong><?php echo ucfirst($meal_type); ?>:</strong>
                                        <ul class="list-group list-group-flush mt-2">
                                            <?php if (is_array($meal_data)): ?>
                                                <?php foreach ($meal_data as $meal): ?>
                                                    <li class="list-group-item px-0 py-1">
                                                        <?php echo $meal; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <li class="list-group-item px-0 py-1">
                                                    <?php echo $meal_data; ?>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                <?php
                                        endif;
                                    endforeach;
                                ?>
                                <?php else: ?>
                                    <p class="text-muted mb-0">No meals specified</p>
                                <?php endif; ?>

                                <?php if (!empty($plan['calories_target'])): ?>
                                    <div class="alert alert-info">
                                        <strong>Daily Calorie Target:</strong> <?php echo $plan['calories_target']; ?> calories
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($plan['notes'])): ?>
                                    <div class="alert alert-warning">
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
                <i class="fas fa-utensils fa-4x text-muted mb-3"></i>
                <h4>No Diet Plans Yet</h4>
                <p class="text-muted">Your trainer hasn't assigned any diet plans to you yet.</p>
                <p class="text-muted">Contact your trainer or gym administrator for personalized diet plans.</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>