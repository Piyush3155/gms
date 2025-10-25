<?php
require_once '../includes/config.php';
require_permission('feedback', 'view');

// Handle response
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['respond'])) {
    $feedback_id = sanitize($_POST['feedback_id']);
    $response = sanitize($_POST['response']);
    $status = sanitize($_POST['status']);

    $stmt = $conn->prepare("UPDATE feedback SET admin_response = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $response, $status, $feedback_id);
    $stmt->execute();
    $stmt->close();
    $success = "Response updated.";
}

// Get all feedback
$feedback = $conn->query("SELECT f.*, u.name as user_name FROM feedback f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <h2 class="mb-4">Feedback & Complaint Management</h2>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table id="datatables" class="table table-striped table-no-bordered table-hover" cellspacing="0" width="100%" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $feedback->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['user_name']; ?></td>
                            <td><span class="badge bg-<?php echo $row['type'] == 'complaint' ? 'danger' : 'info'; ?>"><?php echo ucfirst($row['type']); ?></span></td>
                            <td><?php echo $row['subject']; ?></td>
                            <td><?php echo substr($row['message'], 0, 50); ?>...</td>
                            <td><?php echo $row['rating'] ? $row['rating'] . '/5' : 'N/A'; ?></td>
                            <td><span class="badge bg-<?php echo $row['status'] == 'resolved' ? 'success' : ($row['status'] == 'reviewed' ? 'warning' : 'secondary'); ?>"><?php echo ucfirst($row['status']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="respondToFeedback(<?php echo $row['id']; ?>, '<?php echo addslashes($row['subject']); ?>', '<?php echo addslashes($row['message']); ?>', '<?php echo addslashes($row['admin_response'] ?? ''); ?>')" title="Respond"><i class="bi bi-reply"></i></button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Response Modal -->
    <div class="modal fade" id="responseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Respond to Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="feedback_id" id="feedback_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" id="modal_subject" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Original Message</label>
                            <textarea class="form-control" id="modal_message" rows="4" readonly></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Your Response</label>
                            <textarea class="form-control" name="response" rows="4" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" required>
                                <option value="reviewed">Reviewed</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="respond" class="btn btn-primary">Send Response</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#datatables').DataTable({
                "pagingType": "full_numbers",
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search records",
                }
            });
        });

        function respondToFeedback(id, subject, message, response) {
            document.getElementById('feedback_id').value = id;
            document.getElementById('modal_subject').value = subject;
            document.getElementById('modal_message').value = message;
            document.getElementById('response').value = response;
            
            var modal = new bootstrap.Modal(document.getElementById('responseModal'));
            modal.show();
        }
    </script>
</body>
</html>