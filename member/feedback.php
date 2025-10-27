<?php
require_once '../includes/config.php';
if (!is_logged_in()) {
    redirect('../login.php');
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = sanitize($_POST['type']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    $rating = $type == 'feedback' ? sanitize($_POST['rating']) : null;

    $stmt = $conn->prepare("INSERT INTO feedback (user_id, type, subject, message, rating) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $_SESSION['user_id'], $type, $subject, $message, $rating);
    
    if ($stmt->execute()) {
        $success = "Your " . $type . " has been submitted successfully.";
    } else {
        $error = "Error submitting " . $type . ".";
    }
    $stmt->close();
}

// Get user's previous feedback
$feedback = $conn->query("SELECT * FROM feedback WHERE user_id = {$_SESSION['user_id']} ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback & Complaints - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <h2 class="mb-4">Submit Feedback or Complaint</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type" id="feedback" value="feedback" checked onchange="toggleRating()">
                                    <label class="form-check-label" for="feedback">
                                        Feedback
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type" id="complaint" value="complaint" onchange="toggleRating()">
                                    <label class="form-check-label" for="complaint">
                                        Complaint
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" class="form-control" name="subject" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" name="message" rows="5" required></textarea>
                            </div>

                            <div class="mb-3" id="rating_div">
                                <label class="form-label">Rating (1-5)</label>
                                <select class="form-control" name="rating">
                                    <option value="">Select Rating</option>
                                    <option value="5">5 - Excellent</option>
                                    <option value="4">4 - Very Good</option>
                                    <option value="3">3 - Good</option>
                                    <option value="2">2 - Fair</option>
                                    <option value="1">1 - Poor</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <h5>Your Previous Submissions</h5>
                <div class="list-group">
                    <?php while ($item = $feedback->fetch_assoc()): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo ucfirst($item['type']); ?>: <?php echo $item['subject']; ?></h6>
                                <small><?php echo date('M d', strtotime($item['created_at'])); ?></small>
                            </div>
                            <p class="mb-1"><?php echo substr($item['message'], 0, 100); ?>...</p>
                            <small class="text-muted">Status: <?php echo ucfirst($item['status']); ?></small>
                            <?php if ($item['admin_response']): ?>
                                <div class="mt-2 p-2 bg-light rounded">
                                    <small><strong>Admin Response:</strong> <?php echo $item['admin_response']; ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleRating() {
            const type = document.querySelector('input[name="type"]:checked').value;
            const ratingDiv = document.getElementById('rating_div');
            if (type === 'feedback') {
                ratingDiv.style.display = 'block';
            } else {
                ratingDiv.style.display = 'none';
            }
        }
        
        // Initialize
        toggleRating();
    </script>
</body>
</html>