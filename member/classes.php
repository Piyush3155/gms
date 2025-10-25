<?php
require_once '../includes/config.php';
require_role('member');

$user_id = $_SESSION['user_id'];

// Handle booking
if (isset($_POST['book_class']) && isset($_POST['class_id'])) {
    $class_id = sanitize($_POST['class_id']);

    // Check if already booked
    $check_booking = $conn->prepare("SELECT id FROM class_bookings WHERE class_id = ? AND member_id = ?");
    $check_booking->bind_param("ii", $class_id, $user_id);
    $check_booking->execute();
    $result = $check_booking->get_result();

    if ($result->num_rows > 0) {
        $booking_error = "You have already booked this class.";
    } else {
        // Check class capacity
        $capacity_check = $conn->prepare("
            SELECT gc.capacity, COUNT(cb.id) as booked
            FROM group_classes gc
            LEFT JOIN class_bookings cb ON gc.id = cb.class_id AND cb.status IN ('confirmed', 'attended')
            WHERE gc.id = ?
            GROUP BY gc.id
        ");
        $capacity_check->bind_param("i", $class_id);
        $capacity_check->execute();
        $capacity_result = $capacity_check->get_result();
        $capacity_data = $capacity_result->fetch_assoc();

        if ($capacity_data['booked'] >= $capacity_data['capacity']) {
            $booking_error = "This class is fully booked.";
        } else {
            // Book the class
            $booking_stmt = $conn->prepare("INSERT INTO class_bookings (class_id, member_id) VALUES (?, ?)");
            $booking_stmt->bind_param("ii", $class_id, $user_id);

            if ($booking_stmt->execute()) {
                $booking_success = "Class booked successfully!";
            } else {
                $booking_error = "Error booking class. Please try again.";
            }
            $booking_stmt->close();
        }
        $capacity_check->close();
    }
    $check_booking->close();
}

// Handle cancellation
if (isset($_POST['cancel_booking']) && isset($_POST['booking_id'])) {
    $booking_id = sanitize($_POST['booking_id']);

    $cancel_stmt = $conn->prepare("UPDATE class_bookings SET status = 'cancelled' WHERE id = ? AND member_id = ?");
    $cancel_stmt->bind_param("ii", $booking_id, $user_id);

    if ($cancel_stmt->execute()) {
        $booking_success = "Booking cancelled successfully!";
    } else {
        $booking_error = "Error cancelling booking.";
    }
    $cancel_stmt->close();
}

// Get upcoming classes
$upcoming_classes = $conn->query("
    SELECT gc.*,
           t.name as trainer_name,
           COUNT(cb.id) as booked_count,
           CASE WHEN cb2.id IS NOT NULL THEN 1 ELSE 0 END as is_booked
    FROM group_classes gc
    LEFT JOIN trainers t ON gc.trainer_id = t.id
    LEFT JOIN class_bookings cb ON gc.id = cb.class_id AND cb.status IN ('confirmed', 'attended')
    LEFT JOIN class_bookings cb2 ON gc.id = cb2.class_id AND cb2.member_id = $user_id AND cb2.status IN ('confirmed', 'attended')
    WHERE gc.class_date >= CURDATE() AND gc.status = 'scheduled'
    GROUP BY gc.id
    ORDER BY gc.class_date, gc.start_time
");

// Get user's bookings
$user_bookings = $conn->query("
    SELECT cb.id as booking_id, gc.*, t.name as trainer_name
    FROM class_bookings cb
    JOIN group_classes gc ON cb.class_id = gc.id
    LEFT JOIN trainers t ON gc.trainer_id = t.id
    WHERE cb.member_id = $user_id AND cb.status IN ('confirmed', 'attended')
    AND gc.class_date >= CURDATE()
    ORDER BY gc.class_date, gc.start_time
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Classes - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
            <?php if (isset($booking_success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $booking_success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($booking_error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $booking_error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Available Classes</h4>
                        </div>
                        <div class="card-body">
                            <?php if ($upcoming_classes->num_rows > 0): ?>
                                <div class="row">
                                    <?php while ($class = $upcoming_classes->fetch_assoc()): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo $class['name']; ?></h5>
                                                    <p class="card-text"><?php echo $class['description'] ? substr($class['description'], 0, 100) . '...' : 'No description available.'; ?></p>

                                                    <div class="mb-2">
                                                        <small class="text-muted">
                                                            <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($class['class_date'])); ?><br>
                                                            <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($class['start_time'])) . ' - ' . date('H:i', strtotime($class['end_time'])); ?><br>
                                                            <i class="fas fa-user"></i> <?php echo $class['trainer_name'] ?? 'TBA'; ?><br>
                                                            <i class="fas fa-users"></i> <?php echo $class['booked_count']; ?>/<?php echo $class['capacity']; ?> booked
                                                        </small>
                                                    </div>

                                                    <?php if ($class['is_booked']): ?>
                                                        <span class="badge bg-success">Booked</span>
                                                    <?php elseif ($class['booked_count'] >= $class['capacity']): ?>
                                                        <span class="badge bg-danger">Full</span>
                                                    <?php else: ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                                            <button type="submit" name="book_class" class="btn btn-primary btn-sm">Book Class</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                    <h5>No upcoming classes available</h5>
                                    <p class="text-muted">Check back later for new class schedules.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">My Bookings</h4>
                        </div>
                        <div class="card-body">
                            <?php if ($user_bookings->num_rows > 0): ?>
                                <?php while ($booking = $user_bookings->fetch_assoc()): ?>
                                    <div class="mb-3 p-3 border rounded">
                                        <h6><?php echo $booking['name']; ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($booking['class_date'])); ?><br>
                                            <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($booking['start_time'])) . ' - ' . date('H:i', strtotime($booking['end_time'])); ?><br>
                                            <i class="fas fa-user"></i> <?php echo $booking['trainer_name'] ?? 'TBA'; ?>
                                        </small>
                                        <form method="POST" class="mt-2">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                            <button type="submit" name="cancel_booking" class="btn btn-outline-danger btn-sm"
                                                    onclick="return confirm('Are you sure you want to cancel this booking?')">
                                                Cancel Booking
                                            </button>
                                        </form>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-bookmark fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No upcoming bookings</p>
                                </div>
                            <?php endif; ?>
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