<?php
require_once '../includes/config.php';
require_once '../phpqrcode/qrlib.php'; // Include the QR Code library
require_permission('attendance', 'add');

// Define the sendWhatsAppMessage function if not already defined
if (!function_exists('sendWhatsAppMessage')) {
    function sendWhatsAppMessage($phoneNumber, $message, $mediaUrl = null) {
        // Placeholder implementation for WhatsApp API integration
        // Replace with actual API logic
        return true; // Simulate a successful response
    }
}

$message = '';
$error = '';

if (isset($_GET['generate_qr_download']) && isset($_GET['member_id'])) {
    $memberId = intval($_GET['member_id']);

    // Fetch member details
    $stmt = $conn->prepare("SELECT id, name FROM members WHERE id = ?");
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $member = $result->fetch_assoc();

        // Generate QR Code
        $qrData = "GMS-M-" . str_pad($member['id'], 3, '0', STR_PAD_LEFT);

        // Clear output buffering
        if (ob_get_length()) {
            ob_end_clean();
        }

        // Set headers for download
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="member_qr_' . $member['id'] . '.png"');

        // Output QR Code directly
        QRcode::png($qrData, null, QR_ECLEVEL_L, 10);
        exit;
    } else {
        $error = "Member not found.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['scan_qr'])) {
        $qr_data = trim($_POST['qr_code']);
        $entry_method = isset($_POST['manual_entry']) ? 'manual' : 'scanned';
        error_log("QR Scan Attempt ($entry_method): " . $qr_data);

        // Debug output
        echo "<!-- DEBUG: Processing scan_qr, QR data: '$qr_data', Method: $entry_method -->";

        if (empty($qr_data)) {
            $error = "QR code data is required.";
            error_log("QR scan failed: Empty QR data");
        } else {
            // Parse QR code data (format: GMS-M-001 or GMS-T-001)
            if (preg_match('/GMS-(M|T)-(\d+)/', $qr_data, $matches)) {
                $role = $matches[1] == 'M' ? 'member' : 'trainer';
                $id = intval($matches[2]);
                error_log("Parsed QR: Role=$role, ID=$id");

                if ($role == 'member') {
                    // Verify member exists and qr_code matches
                    $stmt = $conn->prepare("SELECT id, name, status FROM members WHERE id = ? AND qr_code = ?");
                    $stmt->bind_param("is", $id, $qr_data);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $member = $result->fetch_assoc();
                        error_log("Member found: " . $member['name'] . " (Status: " . $member['status'] . ")");

                        if ($member['status'] != 'active') {
                            $error = "Member account is not active.";
                        } else {
                            // Check if already checked in today
                            $today = date('Y-m-d');
                            $stmt = $conn->prepare("SELECT id, check_in, check_out FROM attendance WHERE user_id = ? AND role = 'member' AND date = ?");
                            $stmt->bind_param("is", $id, $today);
                            $stmt->execute();
                            $attendance_result = $stmt->get_result();

                            if ($attendance_result->num_rows > 0) {
                                $attendance = $attendance_result->fetch_assoc();
                                error_log("Existing attendance record found: Check-in=" . $attendance['check_in'] . ", Check-out=" . ($attendance['check_out'] ?? 'NULL'));

                                if (is_null($attendance['check_out'])) {
                                    // Check out
                                    $check_out_time = date('H:i:s');
                                    $stmt = $conn->prepare("UPDATE attendance SET check_out = ? WHERE id = ?");
                                    $stmt->bind_param("si", $check_out_time, $attendance['id']);

                                    if ($stmt->execute()) {
                                        log_activity('qr_checkout', "Member checked out via QR: " . $member['name'], 'attendance');
                                        $message = "Check-out successful for " . $member['name'] . " at " . $check_out_time;
                                        error_log("Check-out recorded successfully for member ID: $id");
                                    } else {
                                        $error = "Failed to record check-out.";
                                        error_log("Check-out failed for member ID: $id - " . $stmt->error);
                                    }
                                } else {
                                    $error = "Member has already checked out today.";
                                    error_log("Member already checked out today: $id");
                                }
                            } else {
                                // Check in
                                $check_in_time = date('H:i:s');
                                $stmt = $conn->prepare("INSERT INTO attendance (user_id, role, date, check_in, status) VALUES (?, 'member', ?, ?, 'present')");
                                $stmt->bind_param("iss", $id, $today, $check_in_time);

                                if ($stmt->execute()) {
                                    log_activity('qr_checkin', "Member checked in via QR: " . $member['name'], 'attendance');
                                    $message = "Check-in successful for " . $member['name'] . " at " . $check_in_time;
                                    error_log("Check-in recorded successfully for member ID: $id");
                                } else {
                                    $error = "Failed to record check-in.";
                                    error_log("Check-in failed for member ID: $id - " . $stmt->error);
                                }
                            }
                        }
                    } else {
                        $error = "Invalid QR code or member not found.";
                        error_log("Member not found or QR mismatch for ID: $id, QR: $qr_data");
                    }
                } elseif ($role == 'trainer') {
                    // Verify trainer exists and qr_code matches
                    $stmt = $conn->prepare("SELECT id, name FROM trainers WHERE id = ? AND qr_code = ?");
                    $stmt->bind_param("is", $id, $qr_data);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $trainer = $result->fetch_assoc();
                        error_log("Trainer found: " . $trainer['name']);

                        // Check if already checked in today
                        $today = date('Y-m-d');
                        $stmt = $conn->prepare("SELECT id, check_in, check_out FROM attendance WHERE user_id = ? AND role = 'trainer' AND date = ?");
                        $stmt->bind_param("is", $id, $today);
                        $stmt->execute();
                        $attendance_result = $stmt->get_result();

                        if ($attendance_result->num_rows > 0) {
                            $attendance = $attendance_result->fetch_assoc();
                            error_log("Existing trainer attendance record found: Check-in=" . $attendance['check_in'] . ", Check-out=" . ($attendance['check_out'] ?? 'NULL'));

                            if (is_null($attendance['check_out'])) {
                                // Check out
                                $check_out_time = date('H:i:s');
                                $stmt = $conn->prepare("UPDATE attendance SET check_out = ? WHERE id = ?");
                                $stmt->bind_param("si", $check_out_time, $attendance['id']);

                                if ($stmt->execute()) {
                                    log_activity('qr_checkout', "Trainer checked out via QR: " . $trainer['name'], 'attendance');
                                    $message = "Check-out successful for " . $trainer['name'] . " at " . $check_out_time;
                                    error_log("Trainer check-out recorded successfully for ID: $id");
                                } else {
                                    $error = "Failed to record check-out.";
                                    error_log("Trainer check-out failed for ID: $id - " . $stmt->error);
                                }
                            } else {
                                $error = "Trainer has already checked out today.";
                                error_log("Trainer already checked out today: $id");
                            }
                        } else {
                            // Check in
                            $check_in_time = date('H:i:s');
                            $stmt = $conn->prepare("INSERT INTO attendance (user_id, role, date, check_in, status) VALUES (?, 'trainer', ?, ?, 'present')");
                            $stmt->bind_param("iss", $id, $today, $check_in_time);

                            if ($stmt->execute()) {
                                log_activity('qr_checkin', "Trainer checked in via QR: " . $trainer['name'], 'attendance');
                                $message = "Check-in successful for " . $trainer['name'] . " at " . $check_in_time;
                                error_log("Trainer check-in recorded successfully for ID: $id");
                            } else {
                                $error = "Failed to record check-in.";
                                error_log("Trainer check-in failed for ID: $id - " . $stmt->error);
                            }
                        }
                    } else {
                        $error = "Invalid QR code or trainer not found.";
                        error_log("Trainer not found or QR mismatch for ID: $id, QR: $qr_data");
                    }
                }
            } else {
                $error = "Invalid QR code format.";
                error_log("Invalid QR format: $qr_data");
            }
        }
    } else if (isset($_POST['generate_qr'])) {
        $memberId = $_POST['member_id'];

        // Fetch member details
        $stmt = $conn->prepare("SELECT id, name, phone FROM members WHERE id = ?");
        $stmt->bind_param("i", $memberId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $member = $result->fetch_assoc();

            // Generate QR Code
            $qrData = "GMS-M-" . str_pad($member['id'], 3, '0', STR_PAD_LEFT);
            $qrFilePath = "../assets/images/qr_codes/{$member['id']}.png";
            $qrDir = dirname($qrFilePath);
            if (!is_dir($qrDir)) {
                mkdir($qrDir, 0777, true);
            }
            QRcode::png($qrData, $qrFilePath, QR_ECLEVEL_L, 10);

            // Send QR Code via WhatsApp
            $message = "Hello {$member['name']}, here is your QR code.";
            $mediaUrl = "https://yourdomain.com/assets/images/qr_codes/{$member['id']}.png";
            $response = sendWhatsAppMessage($member['phone'], $message, $mediaUrl);

            if ($response) {
                $message = "QR code sent successfully to {$member['name']}!";
            } else {
                $error = "Failed to send QR code to {$member['name']}.";
            }
        } else {
            $error = "Member not found.";
        }
    } else if (isset($_POST['generate_qr_download'])) {
        $memberId = $_POST['member_id'];

        // Fetch member details
        $stmt = $conn->prepare("SELECT id, name FROM members WHERE id = ?");
        $stmt->bind_param("i", $memberId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $member = $result->fetch_assoc();

        // Generate QR Code
        $qrData = "GMS-M-" . str_pad($member['id'], 3, '0', STR_PAD_LEFT);

            // Clear output buffering
            if (ob_get_length()) {
                ob_end_clean();
            }

            // Set headers for download
            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="member_qr_' . $member['id'] . '.png"');

            // Output QR Code directly
            QRcode::png($qrData, null, QR_ECLEVEL_L, 10);
            exit;
        } else {
            $error = "Member not found.";
        }
    }
}

// Get recent QR attendance records
$recent_qr_attendance = $conn->query("
    SELECT a.*,
           CASE
               WHEN a.role = 'member' THEN m.name
               WHEN a.role = 'trainer' THEN t.name
           END as person_name,
           a.role as person_role
    FROM attendance a
    LEFT JOIN members m ON a.user_id = m.id AND a.role = 'member'
    LEFT JOIN trainers t ON a.user_id = t.id AND a.role = 'trainer'
    WHERE a.date = CURDATE()
    ORDER BY a.check_in DESC
    LIMIT 10
");

// Debug: Check total attendance records
$debug_info = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE date = CURDATE()")->fetch_assoc();
error_log("Total attendance records today: " . $debug_info['total']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="../assets/js/qr-scanner.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let qrScanner;
            const videoElement = document.getElementById('qr-video');
            const startBtn = document.getElementById('start-scan');
            const stopBtn = document.getElementById('stop-scan');
            const statusDiv = document.getElementById('scan-status');
            const scanOverlay = document.getElementById('scan-overlay');
            const qrInput = document.getElementById('qr_code');

            startBtn.addEventListener('click', startScanning);
            stopBtn.addEventListener('click', stopScanning);

            async function startScanning() {
                try {
                    console.log('Initializing QR scanner...');
                    qrScanner = new QrScanner(
                        videoElement,
                        result => {
                            console.log('QR Code detected:', result.data);
                            qrInput.value = result.data;
                            statusDiv.className = 'status-badge status-success';
                            statusDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>QR Code Detected! Processing attendance...';

                            // Stop scanner and submit form
                            console.log('Stopping scanner and submitting form...');
                            stopScanning();
                            setTimeout(() => {
                                console.log('Submitting attendance form...');
                                document.getElementById('manual-entry-form').submit();
                            }, 800);
                        },
                        {
                            returnDetailedScanResult: true,
                            highlightScanRegion: true,
                            highlightCodeOutline: true,
                            maxScansPerSecond: 5,
                            onDecodeError: handleDecodeError,
                            canvasElement: createOptimizedCanvas()
                        }
                    );

                    console.log('Starting QR scanner...');
                    await qrScanner.start();
                    console.log('QR scanner started successfully');

                    // Update UI
                    startBtn.disabled = true;
                    stopBtn.disabled = false;
                    scanOverlay.style.display = 'block';
                    statusDiv.className = 'status-badge status-scanning';
                    statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Scanning for QR codes... Position QR code in the frame';

                } catch (err) {
                    console.error('Error starting QR scanner:', err);
                    handleScannerError();
                }
            }

            function stopScanning() {
                if (qrScanner) {
                    qrScanner.stop();
                    qrScanner.destroy();
                    qrScanner = null;
                }

                startBtn.disabled = false;
                stopBtn.disabled = true;
                scanOverlay.style.display = 'none';
                statusDiv.className = 'status-badge status-info';
                statusDiv.innerHTML = '<i class="fas fa-info-circle me-2"></i>Scanner stopped. Click "Start Scanning" to begin again.';
            }

            function createOptimizedCanvas() {
                const canvas = document.createElement('canvas');
                canvas.getContext('2d', { willReadFrequently: true });
                return canvas;
            }

            function handleScannerError() {
                statusDiv.className = 'status-badge status-error';
                statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Unable to initialize the scanner. Please try again.';
            }

            function handleDecodeError() {
                statusDiv.className = 'status-badge status-error';
                statusDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Unable to decode QR code. Please try again.';
            }

            // Stop scanning when page unloads
            window.addEventListener('beforeunload', () => {
                if (qrScanner) {
                    qrScanner.stop();
                    qrScanner.destroy();
                }
            });

            // Auto-focus on manual input when clicked
            qrInput.addEventListener('focus', function() {
                if (qrScanner) {
                    stopScanning();
                }
            });

            // Debug form submission
            const manualForm = document.getElementById('manual-entry-form');
            manualForm.addEventListener('submit', function(e) {
                console.log('Manual entry form submitted');
                console.log('Form data:', new FormData(this));
                const formData = new FormData(this);
                for (let [key, value] of formData.entries()) {
                    console.log(key + ': ' + value);
                }
            });
        });
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .scanner-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-header {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .page-header h2 {
            margin: 0;
            color: #1a202c;
            font-weight: 700;
            font-size: 2rem;
        }
        
        .scanner-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            border: none;
        }
        
        .scanner-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1.5rem;
        }
        
        .scanner-card .card-header h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1.25rem;
        }
        
        .video-wrapper {
            position: relative;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            margin: 1.5rem 0;
        }
        
        #qr-video {
            width: 100%;
            height: 450px;
            object-fit: cover;
            display: block;
        }
        
        .scan-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 250px;
            height: 250px;
            border: 3px solid #667eea;
            border-radius: 12px;
            pointer-events: none;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
        }
        
        .scan-overlay::before,
        .scan-overlay::after {
            content: '';
            position: absolute;
            width: 30px;
            height: 30px;
            border: 4px solid #667eea;
        }
        
        .scan-overlay::before {
            top: -4px;
            left: -4px;
            border-right: none;
            border-bottom: none;
        }
        
        .scan-overlay::after {
            bottom: -4px;
            right: -4px;
            border-left: none;
            border-top: none;
        }
        
        .scanner-controls {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin: 1.5rem 0;
        }
        
        .btn-scanner {
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .btn-scanner-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-scanner-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-scanner-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn-scanner-secondary:hover:not(:disabled) {
            background: #cbd5e0;
        }
        
        .btn-scanner:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .status-badge {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            text-align: center;
            margin: 1rem 0;
        }
        
        .status-info {
            background: #e6f7ff;
            color: #0066cc;
            border: 2px solid #91d5ff;
        }
        
        .status-success {
            background: #f6ffed;
            color: #389e0d;
            border: 2px solid #b7eb8f;
        }
        
        .status-scanning {
            background: #fff7e6;
            color: #d46b08;
            border: 2px solid #ffd591;
            animation: pulse 2s infinite;
        }
        
        .status-error {
            background: #fff2f0;
            color: #cf1322;
            border: 2px solid #ffccc7;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .manual-entry-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .manual-entry-card .card-header {
            background: #f7fafc;
            border-bottom: 2px solid #e2e8f0;
            padding: 1.25rem;
        }
        
        .form-control-modern {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control-modern:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-process {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 0.75rem;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-process:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(72, 187, 120, 0.4);
        }
        
        .attendance-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .attendance-item {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.2s ease;
        }
        
        .attendance-item:hover {
            background: #f7fafc;
        }
        
        .attendance-item:last-child {
            border-bottom: none;
        }
        
        .member-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }
        
        .time-info {
            color: #718096;
            font-size: 0.875rem;
        }
        
        .status-badge-small {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 0.5rem;
        }
        
        .badge-checked-in {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .badge-checked-out {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .alert-modern {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success-modern {
            background: #f6ffed;
            color: #389e0d;
            border-left: 4px solid #52c41a;
        }
        
        .alert-danger-modern {
            background: #fff2f0;
            color: #cf1322;
            border-left: 4px solid #ff4d4f;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #a0aec0;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include '../includes/header.php'; ?>

        <div class="page-content">
            <div class="page-header">
                <h2><i class="fas fa-qrcode me-3"></i>QR Code Attendance Scanner</h2>
                <p class="text-muted mb-0 mt-2">Scan member QR codes for instant check-in and check-out</p>
                <small class="text-info">Debug: <?php echo $debug_info['total']; ?> attendance records today</small>
                <div style="background: #f0f8ff; padding: 10px; margin-top: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;">
                    <strong>Debug Info:</strong><br>
                    REQUEST_METHOD: <?php echo $_SERVER['REQUEST_METHOD']; ?><br>
                    POST data: <?php echo isset($_POST) && !empty($_POST) ? json_encode($_POST) : 'No POST data'; ?><br>
                    Session user_id: <?php echo $_SESSION['user_id'] ?? 'Not set'; ?><br>
                    Last message: <?php echo $message ?: 'None'; ?><br>
                    Last error: <?php echo $error ?: 'None'; ?>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert-modern alert-success-modern">
                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert-modern alert-danger-modern">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="scanner-card card">
                        <div class="card-header">
                            <h5><i class="fas fa-camera me-2"></i>Webcam Scanner</h5>
                        </div>
                        <div class="card-body">
                            <div class="video-wrapper">
                                <video id="qr-video"></video>
                                <div class="scan-overlay" id="scan-overlay" style="display: none;"></div>
                            </div>
                            <div class="scanner-controls">
                                <button id="start-scan" class="btn btn-scanner btn-scanner-primary">
                                    <i class="fas fa-play me-2"></i>Start Scanning
                                </button>
                                <button id="stop-scan" class="btn btn-scanner btn-scanner-secondary" disabled>
                                    <i class="fas fa-stop me-2"></i>Stop Scanning
                                </button>
                            </div>
                            <div id="scan-status" class="status-badge status-info">
                                <i class="fas fa-info-circle me-2"></i>Click "Start Scanning" to begin QR code detection
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="manual-entry-card card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-keyboard me-2"></i>Manual Entry</h5>
                        </div>
                        <div class="card-body">
                            <div style="background: #fff3cd; padding: 10px; margin-bottom: 15px; border-radius: 5px; font-size: 12px;">
                                <strong>Sample QR Codes (copy and paste to test):</strong><br>
                                <?php
                                $sample_qrs = $conn->query("SELECT id, name, qr_code FROM members WHERE status = 'active' LIMIT 3");
                                while ($sample = $sample_qrs->fetch_assoc()) {
                                    echo "• " . htmlspecialchars($sample['qr_code']) . " (" . htmlspecialchars($sample['name']) . ")<br>";
                                }
                                ?>
                            </div>
                            <form method="POST" id="manual-entry-form">
                                <input type="hidden" name="manual_entry" value="1">
                                <input type="hidden" name="scan_qr" value="Process Attendance">
                                <div class="mb-3">
                                    <label for="qr_code" class="form-label fw-semibold">QR Code Data</label>
                                    <input type="text" class="form-control form-control-modern" id="qr_code" name="qr_code" placeholder="Enter QR code data manually" required>
                                    <small class="text-muted">Or scan using the camera above</small>
                                </div>
                                <button type="submit" class="btn btn-process w-100">
                                    <i class="fas fa-check-circle me-2"></i>Process Attendance
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="manual-entry-card card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Today's Attendance</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="attendance-list">
                                <?php if ($recent_qr_attendance->num_rows > 0): ?>
                                    <?php while ($record = $recent_qr_attendance->fetch_assoc()): ?>
                                        <div class="attendance-item">
                                            <div class="member-name">
                                                <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($record['person_name']); ?>
                                                <small class="text-muted">(<?php echo ucfirst($record['person_role']); ?>)</small>
                                            </div>
                                            <div class="time-info">
                                                <i class="fas fa-clock me-1"></i>In: <?php echo date('g:i A', strtotime($record['check_in'])); ?>
                                                <?php if ($record['check_out']): ?>
                                                    | Out: <?php echo date('g:i A', strtotime($record['check_out'])); ?>
                                                <?php endif; ?>
                                            </div>
                                            <span class="status-badge-small <?php echo is_null($record['check_out']) ? 'badge-checked-in' : 'badge-checked-out'; ?>">
                                                <?php echo is_null($record['check_out']) ? '✓ Checked In' : '✓ Checked Out'; ?>
                                            </span>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p class="mb-0">No attendance records yet today</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add a form to trigger QR code generation -->
            <div class="manual-entry-card card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i>Generate and Send QR Code</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="member_id" class="form-label fw-semibold">Member ID</label>
                            <input type="number" class="form-control form-control-modern" id="member_id" name="member_id" placeholder="Enter Member ID" required>
                        </div>
                        <button type="submit" name="generate_qr" class="btn btn-process w-100">
                            <i class="fas fa-paper-plane me-2"></i>Generate and Send QR Code
                        </button>
                    </form>
                </div>
            </div>

            <!-- Add a form to trigger QR code download -->
            <div class="manual-entry-card card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i>Generate and Download QR Code</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="member_id" class="form-label fw-semibold">Member ID</label>
                            <input type="number" class="form-control form-control-modern" id="member_id" name="member_id" placeholder="Enter Member ID" required>
                        </div>
                        <button type="submit" name="generate_qr_download" class="btn btn-process w-100">
                            <i class="fas fa-download me-2"></i>Generate and Download QR Code
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>


</html>