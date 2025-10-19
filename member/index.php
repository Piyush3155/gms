<?php
require_once '../includes/config.php';
require_role('member');

$user_id = $_SESSION['user_id'];

// Get member info
$member = $conn->query("SELECT m.*, p.name as plan_name, t.name as trainer_name FROM members m LEFT JOIN plans p ON m.plan_id = p.id LEFT JOIN trainers t ON m.trainer_id = t.id WHERE m.id = $user_id")->fetch_assoc();

// Get attendance this month
$this_month = date('Y-m');
$monthly_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE user_id = $user_id AND DATE_FORMAT(date, '%Y-%m') = '$this_month' AND status = 'present'")->fetch_assoc()['count'];

// Get workout plans
$workout_plans = $conn->query("SELECT wp.*, t.name as trainer_name FROM workout_plans wp JOIN trainers t ON wp.trainer_id = t.id WHERE wp.member_id = $user_id ORDER BY wp.created_at DESC");

// Get diet plans
$diet_plans = $conn->query("SELECT dp.*, t.name as trainer_name FROM diet_plans dp JOIN trainers t ON dp.trainer_id = t.id WHERE dp.member_id = $user_id ORDER BY dp.created_at DESC");

// Get recent payments
$payments = $conn->query("SELECT p.*, pl.name as plan_name FROM payments p JOIN plans pl ON p.plan_id = pl.id WHERE p.member_id = $user_id ORDER BY p.payment_date DESC LIMIT 3");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <h2>Welcome, <?php echo $member['name']; ?>!</h2>
                <p class="text-muted">Member Dashboard</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="card">
                    <div class="card-body">
                        <h6>Membership Status</h6>
                        <span class="badge bg-<?php echo $member['status'] == 'active' ? 'success' : 'warning'; ?> fs-6">
                            <?php echo ucfirst($member['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mt-4">
            <div class="col-md-3 mb-4">
                <div class="stats-card text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-id-card me-2"></i>My Plan</h5>
                        <h4><?php echo $member['plan_name'] ?: 'No Plan'; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stats-card text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user-tie me-2"></i>My Trainer</h5>
                        <h4><?php echo $member['trainer_name'] ?: 'Not Assigned'; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stats-card text-white" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-calendar-check me-2"></i>This Month</h5>
                        <h2><?php echo $monthly_attendance; ?> days</h2>
                        <small>Attendance</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stats-card text-white" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-dumbbell me-2"></i>Workout Plans</h5>
                        <h2><?php echo $workout_plans->num_rows; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-dumbbell me-2"></i>My Workout Plans</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($workout_plans->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($plan = $workout_plans->fetch_assoc()): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">From <?php echo $plan['trainer_name']; ?></h6>
                                            <small><?php echo date('M j', strtotime($plan['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo substr($plan['description'], 0, 100) . (strlen($plan['description']) > 100 ? '...' : ''); ?></p>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewPlan('workout', <?php echo $plan['id']; ?>)">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </button>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-dumbbell fa-3x mb-3"></i>
                                <p>No workout plans assigned yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-utensils me-2"></i>My Diet Plans</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($diet_plans->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($plan = $diet_plans->fetch_assoc()): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">From <?php echo $plan['trainer_name']; ?></h6>
                                            <small><?php echo date('M j', strtotime($plan['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo substr($plan['description'], 0, 100) . (strlen($plan['description']) > 100 ? '...' : ''); ?></p>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewPlan('diet', <?php echo $plan['id']; ?>)">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </button>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-utensils fa-3x mb-3"></i>
                                <p>No diet plans assigned yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Recent Payments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($payment = $payments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $payment['invoice_no']; ?></td>
                                    <td><?php echo $payment['plan_name']; ?></td>
                                    <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $payment['status'] == 'paid' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
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
</body>
</html>