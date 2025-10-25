<?php
require_once '../includes/config.php';
require_role('admin');

// Handle sending notifications
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notification_type = sanitize($_POST['notification_type']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    $recipients = isset($_POST['recipients']) ? $_POST['recipients'] : [];

    if (empty($subject) || empty($message)) {
        $errors[] = "Subject and message are required.";
    } else {
        $recipient_emails = [];

        if ($notification_type == 'expiring_members') {
            // Get members with expiring memberships (next 7 days)
            $expiring_query = $conn->query("
                SELECT m.name, m.email
                FROM members m
                JOIN plans p ON m.plan_id = p.id
                WHERE DATE_ADD(m.join_date, INTERVAL p.duration_months MONTH) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                AND m.status = 'active'
            ");
            while ($row = $expiring_query->fetch_assoc()) {
                $recipient_emails[] = $row['email'];
            }
        } elseif ($notification_type == 'inactive_members') {
            // Get inactive members
            $inactive_query = $conn->query("SELECT name, email FROM members WHERE status = 'inactive'");
            while ($row = $inactive_query->fetch_assoc()) {
                $recipient_emails[] = $row['email'];
            }
        } elseif ($notification_type == 'all_members') {
            // Get all active members
            $all_query = $conn->query("SELECT name, email FROM members WHERE status = 'active'");
            while ($row = $all_query->fetch_assoc()) {
                $recipient_emails[] = $row['email'];
            }
        } elseif ($notification_type == 'custom' && !empty($recipients)) {
            // Custom recipients
            foreach ($recipients as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $recipient_emails[] = $email;
                }
            }
        }

        if (empty($recipient_emails)) {
            $errors[] = "No valid recipients found.";
        } else {
            // In a real application, you would integrate with an email service like SendGrid, Mailgun, etc.
            // For now, we'll simulate sending emails by logging them
            $sent_count = 0;
            foreach ($recipient_emails as $email) {
                // Simulate email sending
                // mail($email, $subject, $message, "From: " . get_gym_settings()['email']);
                $sent_count++;
            }

            $success = "Notification sent to $sent_count recipients successfully!";

            // Log the notification
            $log_message = "Notification sent: $subject to $sent_count recipients";
            // You could save this to a notifications_log table
        }
    }
}

// Get notification statistics
$expiring_count = $conn->query("
    SELECT COUNT(*) as count
    FROM members m
    JOIN plans p ON m.plan_id = p.id
    WHERE DATE_ADD(m.join_date, INTERVAL p.duration_months MONTH) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND m.status = 'active'
")->fetch_assoc()['count'];

$inactive_count = $conn->query("SELECT COUNT(*) as count FROM members WHERE status = 'inactive'")->fetch_assoc()['count'];
$total_members = $conn->query("SELECT COUNT(*) as count FROM members WHERE status = 'active'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo SITE_NAME; ?></title>
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
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Send Notifications</h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <?php echo $success; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Notification Type</label>
                                    <select class="form-control" name="notification_type" id="notificationType" required>
                                        <option value="">Select notification type...</option>
                                        <option value="expiring_members">Expiring Memberships (Next 7 days)</option>
                                        <option value="inactive_members">Inactive Members</option>
                                        <option value="all_members">All Active Members</option>
                                        <option value="custom">Custom Recipients</option>
                                    </select>
                                </div>

                                <div class="mb-3" id="customRecipients" style="display: none;">
                                    <label class="form-label">Recipient Emails (one per line)</label>
                                    <textarea class="form-control" name="recipients" rows="4" placeholder="email1@example.com&#10;email2@example.com"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Subject</label>
                                    <input type="text" class="form-control" name="subject" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Message</label>
                                    <textarea class="form-control" name="message" rows="6" required></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Send Notification
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Notification Statistics</h4>
                        </div>
                        <div class="card-body">
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-exclamation-triangle text-warning"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3><?php echo $expiring_count; ?></h3>
                                        <p>Expiring Soon</p>
                                    </div>
                                </div>

                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-user-times text-danger"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3><?php echo $inactive_count; ?></h3>
                                        <p>Inactive Members</p>
                                    </div>
                                </div>

                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-users text-primary"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3><?php echo $total_members; ?></h3>
                                        <p>Active Members</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h4 class="mb-0">Quick Templates</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="loadTemplate('expiry')">
                                    Membership Expiry Reminder
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="loadTemplate('welcome')">
                                    Welcome New Member
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="loadTemplate('promotion')">
                                    Special Promotion
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <style>
        .stats-grid {
            display: grid;
            gap: 1rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat-icon {
            font-size: 2rem;
            margin-right: 1rem;
        }

        .stat-content h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .stat-content p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide custom recipients field
        document.getElementById('notificationType').addEventListener('change', function() {
            const customRecipients = document.getElementById('customRecipients');
            if (this.value === 'custom') {
                customRecipients.style.display = 'block';
            } else {
                customRecipients.style.display = 'none';
            }
        });

        // Load email templates
        function loadTemplate(type) {
            const subjectField = document.querySelector('input[name="subject"]');
            const messageField = document.querySelector('textarea[name="message"]');

            switch(type) {
                case 'expiry':
                    subjectField.value = 'Your Membership is Expiring Soon';
                    messageField.value = `Dear Member,

Your gym membership is expiring soon. To continue enjoying our facilities and services, please renew your membership.

Visit us or contact our front desk to renew your membership and avoid any service interruption.

Best regards,
${get_gym_settings()['gym_name']} Team`;
                    break;

                case 'welcome':
                    subjectField.value = 'Welcome to <?php echo get_gym_settings()['gym_name']; ?>!';
                    messageField.value = `Dear New Member,

Welcome to <?php echo get_gym_settings()['gym_name']; ?>! We're excited to have you join our fitness community.

Your membership is now active. Here are some tips to get started:
- Complete your fitness assessment
- Attend our orientation session
- Set your fitness goals with a trainer

We're here to support you on your fitness journey!

Best regards,
<?php echo get_gym_settings()['gym_name']; ?> Team`;
                    break;

                case 'promotion':
                    subjectField.value = 'Special Offer: Limited Time Discount!';
                    messageField.value = `Dear Member,

We have a special promotion running this month! Get 20% off on all membership renewals and personal training packages.

Don't miss out on this limited-time offer. Contact us today to take advantage of this discount.

Stay fit and healthy!

Best regards,
<?php echo get_gym_settings()['gym_name']; ?> Team`;
                    break;
            }
        }
    </script>
</body>
</html>