<?php
require_once '../includes/config.php';
require_permission('attendance', 'add');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['scan_qr'])) {
        $qr_data = trim($_POST['qr_code']);

        if (empty($qr_data)) {
            $error = "QR code data is required.";
        } else {
            // Parse QR code data (format: GMS_MEMBER_{id}_{hash})
            if (preg_match('/GMS_MEMBER_(\d+)_([a-f0-9]{32})/', $qr_data, $matches)) {
                $member_id = $matches[1];
                $hash = $matches[2];

                // Verify member exists and hash matches
                $stmt = $conn->prepare("SELECT id, name, status FROM members WHERE id = ? AND qr_code = ?");
                $stmt->bind_param("is", $member_id, $qr_data);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $member = $result->fetch_assoc();

                    if ($member['status'] != 'active') {
                        $error = "Member account is not active.";
                    } else {
                        // Check if already checked in today
                        $today = date('Y-m-d');
                        $stmt = $conn->prepare("SELECT id, check_in, check_out FROM attendance WHERE user_id = ? AND role = 'member' AND date = ?");
                        $stmt->bind_param("is", $member_id, $today);
                        $stmt->execute();
                        $attendance_result = $stmt->get_result();

                        if ($attendance_result->num_rows > 0) {
                            $attendance = $attendance_result->fetch_assoc();

                            if (is_null($attendance['check_out'])) {
                                // Check out
                                $check_out_time = date('H:i:s');
                                $stmt = $conn->prepare("UPDATE attendance SET check_out = ? WHERE id = ?");
                                $stmt->bind_param("si", $check_out_time, $attendance['id']);

                                if ($stmt->execute()) {
                                    log_activity('qr_checkout', "Member checked out via QR: " . $member['name'], 'attendance');
                                    $message = "Check-out successful for " . $member['name'] . " at " . $check_out_time;
                                } else {
                                    $error = "Failed to record check-out.";
                                }
                            } else {
                                $error = "Member has already checked out today.";
                            }
                        } else {
                            // Check in
                            $check_in_time = date('H:i:s');
                            $stmt = $conn->prepare("INSERT INTO attendance (user_id, role, date, check_in, status) VALUES (?, 'member', ?, ?, 'present')");
                            $stmt->bind_param("iss", $member_id, $today, $check_in_time);

                            if ($stmt->execute()) {
                                log_activity('qr_checkin', "Member checked in via QR: " . $member['name'], 'attendance');
                                $message = "Check-in successful for " . $member['name'] . " at " . $check_in_time;
                            } else {
                                $error = "Failed to record check-in.";
                            }
                        }
                    }
                } else {
                    $error = "Invalid QR code or member not found.";
                }
            } else {
                $error = "Invalid QR code format.";
            }
        }
    }
}

// Get recent QR attendance records
$recent_qr_attendance = $conn->query("
    SELECT a.*, m.name as member_name
    FROM attendance a
    JOIN members m ON a.user_id = m.id
    WHERE a.date = CURDATE()
    ORDER BY a.check_in DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.18.6/umd/index.min.js"></script>
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-qrcode me-2"></i>QR Code Attendance Scanner</h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-camera me-2"></i>Camera Scanner</h5>
                    </div>
                    <div class="card-body text-center">
                        <div id="video-container" class="mb-3">
                            <video id="qr-video" width="100%" height="400" style="border: 1px solid #ccc; border-radius: 5px;"></video>
                        </div>
                        <div class="mb-3">
                            <button id="start-scan" class="btn btn-primary me-2">
                                <i class="fas fa-play me-1"></i>Start Scanning
                            </button>
                            <button id="stop-scan" class="btn btn-secondary" disabled>
                                <i class="fas fa-stop me-1"></i>Stop Scanning
                            </button>
                        </div>
                        <div id="scan-status" class="alert alert-info">
                            Click "Start Scanning" to begin QR code detection
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-keyboard me-2"></i>Manual Entry</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="qr_code" class="form-label">QR Code Data</label>
                                <input type="text" class="form-control" id="qr_code" name="qr_code" placeholder="Scan or enter QR code data" required>
                            </div>
                            <button type="submit" name="scan_qr" class="btn btn-success w-100">
                                <i class="fas fa-check me-2"></i>Process Attendance
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Today's QR Attendance</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php while ($record = $recent_qr_attendance->fetch_assoc()): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex w-100 justify-content-between">
                                        <small class="text-muted"><?php echo $record['member_name']; ?></small>
                                        <small class="text-muted"><?php echo $record['check_in']; ?><?php echo $record['check_out'] ? ' - ' . $record['check_out'] : ''; ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <span class="badge bg-<?php echo is_null($record['check_out']) ? 'success' : 'secondary'; ?>">
                                            <?php echo is_null($record['check_out']) ? 'Checked In' : 'Checked Out'; ?>
                                        </span>
                                    </p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let codeReader;
        let isScanning = false;

        document.getElementById('start-scan').addEventListener('click', startScanning);
        document.getElementById('stop-scan').addEventListener('click', stopScanning);

        async function startScanning() {
            try {
                codeReader = new ZXing.BrowserQRCodeReader();
                const videoInputDevices = await ZXing.BrowserCodeReader.listVideoInputDevices();

                if (videoInputDevices.length === 0) {
                    document.getElementById('scan-status').className = 'alert alert-danger';
                    document.getElementById('scan-status').textContent = 'No camera found. Please use manual entry.';
                    return;
                }

                // Use the first available camera
                const deviceId = videoInputDevices[0].deviceId;

                document.getElementById('start-scan').disabled = true;
                document.getElementById('stop-scan').disabled = false;
                document.getElementById('scan-status').className = 'alert alert-warning';
                document.getElementById('scan-status').textContent = 'Initializing camera...';

                const result = await codeReader.decodeOnceFromVideoDevice(deviceId, 'qr-video');

                if (result) {
                    document.getElementById('qr_code').value = result.text;
                    document.getElementById('scan-status').className = 'alert alert-success';
                    document.getElementById('scan-status').textContent = 'QR code detected! Processing...';

                    // Auto-submit the form
                    setTimeout(() => {
                        document.querySelector('form').submit();
                    }, 1000);
                }

            } catch (err) {
                console.error(err);
                document.getElementById('scan-status').className = 'alert alert-danger';
                document.getElementById('scan-status').textContent = 'Error: ' + err.message;
                stopScanning();
            }
        }

        function stopScanning() {
            if (codeReader) {
                codeReader.reset();
                codeReader = null;
            }

            document.getElementById('start-scan').disabled = false;
            document.getElementById('stop-scan').disabled = true;
            document.getElementById('scan-status').className = 'alert alert-info';
            document.getElementById('scan-status').textContent = 'Scanner stopped. Click "Start Scanning" to begin again.';
            isScanning = false;
        }

        // Stop scanning when page unloads
        window.addEventListener('beforeunload', stopScanning);
    </script>
</body>
</html>