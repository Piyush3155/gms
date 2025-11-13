<?php
require_once '../includes/config.php';
require_once '../phpqrcode/qrlib.php';
require_permission('attendance', 'add');

if (!function_exists('sendWhatsAppMessage')) {
    function sendWhatsAppMessage($phoneNumber, $message, $mediaUrl = null) {
        return true;
    }
}

// <CHANGE> Handle QR code download requests FIRST (before any HTML output)
if (isset($_POST['generate_qr_download'])) {
    error_log("=== DOWNLOAD REQUEST STARTED ===");
    // Debug: Log all POST data
    error_log("Download request received. POST data: " . print_r($_POST, true));

    $memberId = $_POST['member_id'] ?? null;
    error_log("Initial member_id: " . ($memberId ?? 'null'));

    if (!$memberId && isset($_POST['member_search'])) {
        error_log("member_search provided: " . $_POST['member_search']);
        // Try to extract ID from search string like "Name (ID: 123)"
        if (preg_match('/\(ID:\s*(\d+)\)/', $_POST['member_search'], $matches)) {
            $memberId = $matches[1];
            error_log("Extracted member ID from search: $memberId");
        } else {
            error_log("Could not extract ID from search string");
        }
    }

    error_log("Final member_id: " . ($memberId ?? 'null'));

    if ($memberId) {
        error_log("Querying database for member ID: $memberId");
        $stmt = $conn->prepare("SELECT id, name FROM members WHERE id = ?");
        $stmt->bind_param("i", $memberId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $member = $result->fetch_assoc();
            error_log("Member found: " . $member['name'] . " (ID: " . $member['id'] . ")");

            $qrData = "GMS-M-" . str_pad($member['id'], 3, '0', STR_PAD_LEFT);
            error_log("Generated QR data: $qrData");

            // Clear any output buffering
            if (ob_get_length()) {
                ob_end_clean();
                error_log("Cleared output buffer");
            }

            // Set headers for download
            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="member_qr_' . $member['id'] . '.png"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            error_log("Headers set, generating QR code...");
            // Output QR Code directly
            QRcode::png($qrData, null, QR_ECLEVEL_L, 10);
            error_log("QR code generated and sent");
            exit;
        } else {
            error_log("Member not found in database for ID: $memberId");
            // Redirect back with error if member not found
            header('Location: ' . $_SERVER['PHP_SELF'] . '?error=member_not_found');
            exit;
        }
    } else {
        error_log("No member ID provided");
        // Redirect back with error if no member selected
        header('Location: ' . $_SERVER['PHP_SELF'] . '?error=invalid_member');
        exit;
    }
}

// <CHANGE> Handle GET download requests (legacy support)
if (isset($_GET['generate_qr_download']) && isset($_GET['member_id'])) {
    $memberId = intval($_GET['member_id']);
    $stmt = $conn->prepare("SELECT id, name FROM members WHERE id = ?");
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $member = $result->fetch_assoc();
        $qrData = "GMS-M-" . str_pad($member['id'], 3, '0', STR_PAD_LEFT);

        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="member_qr_' . $member['id'] . '.png"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        QRcode::png($qrData, null, QR_ECLEVEL_L, 10);
        exit;
    } else {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?error=member_not_found');
        exit;
    }
}

$message = '';
$error = '';

// Check for error from redirect
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'member_not_found') {
        $error = "Member not found.";
    } elseif ($_GET['error'] === 'invalid_member') {
        $error = "Please select a valid member.";
    }
}

// <CHANGE> Main form processing logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("=== POST REQUEST RECEIVED ===");
    error_log("POST Keys: " . implode(', ', array_keys($_POST)));
    error_log("Full POST data: " . print_r($_POST, true));
    
    if (isset($_POST['scan_qr'])) {
        $qr_data = trim($_POST['qr_code']);
        $entry_method = isset($_POST['manual_entry']) ? 'manual' : 'scanned';
        error_log("QR Scan Attempt ($entry_method): " . $qr_data);

        if (empty($qr_data)) {
            $error = "QR code data is required.";
            error_log("QR scan failed: Empty QR data");
        } else {
            if (preg_match('/GMS-(M|T)-(\d+)/', $qr_data, $matches)) {
                $role = $matches[1] == 'M' ? 'member' : 'trainer';
                $id = intval($matches[2]);
                error_log("Parsed QR: Role=$role, ID=$id");

                if ($role == 'member') {
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
                            $today = date('Y-m-d');
                            $stmt = $conn->prepare("SELECT id, check_in, check_out FROM attendance WHERE user_id = ? AND role = 'member' AND date = ?");
                            $stmt->bind_param("is", $id, $today);
                            $stmt->execute();
                            $attendance_result = $stmt->get_result();

                            if ($attendance_result->num_rows > 0) {
                                $attendance = $attendance_result->fetch_assoc();
                                error_log("Existing attendance record found: Check-in=" . $attendance['check_in'] . ", Check-out=" . ($attendance['check_out'] ?? 'NULL'));

                                if (is_null($attendance['check_out'])) {
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
                    $stmt = $conn->prepare("SELECT id, name FROM trainers WHERE id = ? AND qr_code = ?");
                    $stmt->bind_param("is", $id, $qr_data);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $trainer = $result->fetch_assoc();
                        error_log("Trainer found: " . $trainer['name']);

                        $today = date('Y-m-d');
                        $stmt = $conn->prepare("SELECT id, check_in, check_out FROM attendance WHERE user_id = ? AND role = 'trainer' AND date = ?");
                        $stmt->bind_param("is", $id, $today);
                        $stmt->execute();
                        $attendance_result = $stmt->get_result();

                        if ($attendance_result->num_rows > 0) {
                            $attendance = $attendance_result->fetch_assoc();
                            error_log("Existing trainer attendance record found: Check-in=" . $attendance['check_in'] . ", Check-out=" . ($attendance['check_out'] ?? 'NULL'));

                            if (is_null($attendance['check_out'])) {
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
        error_log("=== GENERATE QR REQUEST STARTED ===");
        error_log("Generate QR POST data: " . print_r($_POST, true));

        $memberId = $_POST['member_id'] ?? null;
        error_log("Generate QR - Initial member_id: " . ($memberId ?? 'null'));

        if (!$memberId && isset($_POST['member_search'])) {
            error_log("Generate QR - member_search provided: " . $_POST['member_search']);
            // Try to extract ID from search string like "Name (ID: 123)"
            if (preg_match('/\(ID:\s*(\d+)\)/', $_POST['member_search'], $matches)) {
                $memberId = $matches[1];
                error_log("Generate QR - Extracted member ID from search: $memberId");
            } else {
                error_log("Generate QR - Could not extract ID from search string");
            }
        }

        error_log("Generate QR - Final member_id: " . ($memberId ?? 'null'));

        if ($memberId) {
            error_log("Generate QR - Querying database for member ID: $memberId");
            $stmt = $conn->prepare("SELECT id, name, phone FROM members WHERE id = ?");
            $stmt->bind_param("i", $memberId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $member = $result->fetch_assoc();
                error_log("Generate QR - Member found: " . $member['name'] . " (ID: " . $member['id'] . ", Phone: " . $member['phone'] . ")");

                $qrData = "GMS-M-" . str_pad($member['id'], 3, '0', STR_PAD_LEFT);
                error_log("Generate QR - Generated QR data: $qrData");

                $qrFilePath = "../assets/images/qr_codes/{$member['id']}.png";
                $qrDir = dirname($qrFilePath);
                if (!is_dir($qrDir)) {
                    mkdir($qrDir, 0777, true);
                    error_log("Generate QR - Created QR directory: $qrDir");
                }

                error_log("Generate QR - Generating QR code to file: $qrFilePath");
                QRcode::png($qrData, $qrFilePath, QR_ECLEVEL_L, 10);
                error_log("Generate QR - QR code saved to file");

                $message = "Hello {$member['name']}, here is your QR code.";
                $mediaUrl = "https://yourdomain.com/assets/images/qr_codes/{$member['id']}.png";
                error_log("Generate QR - Attempting WhatsApp send to: " . $member['phone']);
                $response = sendWhatsAppMessage($member['phone'], $message, $mediaUrl);

                if ($response) {
                    $message = "QR code sent successfully to {$member['name']}!";
                    error_log("Generate QR - WhatsApp send successful");
                } else {
                    $error = "Failed to send QR code to {$member['name']}.";
                    error_log("Generate QR - WhatsApp send failed");
                }
            } else {
                $error = "Member not found.";
                error_log("Generate QR - Member not found in database for ID: $memberId");
            }
        } else {
            $error = "Please select a valid member.";
            error_log("Generate QR - No member ID provided");
        }
    } else if (isset($_POST['generate_qr_download'])) {
        error_log("=== DOWNLOAD QR REQUEST STARTED ===");
        error_log("Download QR POST data: " . print_r($_POST, true));

        $memberId = $_POST['member_id'] ?? null;
        error_log("Download QR - Initial member_id: " . ($memberId ?? 'null'));

        if (!$memberId && isset($_POST['member_search'])) {
            error_log("Download QR - member_search provided: " . $_POST['member_search']);
            // Try to extract ID from search string like "Name (ID: 123)"
            if (preg_match('/\(ID:\s*(\d+)\)/', $_POST['member_search'], $matches)) {
                $memberId = $matches[1];
                error_log("Download QR - Extracted member ID from search: $memberId");
            } else {
                error_log("Download QR - Could not extract ID from search string");
            }
        }

        error_log("Download QR - Final member_id: " . ($memberId ?? 'null'));

        if ($memberId) {
            error_log("Download QR - Querying database for member ID: $memberId");
            $stmt = $conn->prepare("SELECT id, name FROM members WHERE id = ?");
            $stmt->bind_param("i", $memberId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $member = $result->fetch_assoc();
                error_log("Download QR - Member found: " . $member['name'] . " (ID: " . $member['id'] . ")");

                $qrData = "GMS-M-" . str_pad($member['id'], 3, '0', STR_PAD_LEFT);
                error_log("Download QR - Generated QR data: $qrData");

                $qrFilePath = "../assets/images/qr_codes/{$member['id']}.png";
                $qrDir = dirname($qrFilePath);
                if (!is_dir($qrDir)) {
                    mkdir($qrDir, 0777, true);
                    error_log("Download QR - Created QR directory: $qrDir");
                }

                error_log("Download QR - Generating QR code to file: $qrFilePath");
                QRcode::png($qrData, $qrFilePath, QR_ECLEVEL_L, 10);
                error_log("Download QR - QR code saved to file");

                // Force download of the QR code
                if (file_exists($qrFilePath)) {
                    error_log("Download QR - File exists, initiating download");
                    header('Content-Type: image/png');
                    header('Content-Disposition: attachment; filename="' . $member['name'] . '_QR_Code.png"');
                    header('Content-Length: ' . filesize($qrFilePath));
                    header('Cache-Control: no-cache, no-store, must-revalidate');
                    header('Pragma: no-cache');
                    header('Expires: 0');
                    readfile($qrFilePath);
                    exit;
                } else {
                    $error = "Failed to generate QR code file.";
                    error_log("Download QR - File not found after generation: $qrFilePath");
                }
            } else {
                $error = "Member not found.";
                error_log("Download QR - Member not found in database for ID: $memberId");
            }
        } else {
            $error = "Please select a valid member.";
            error_log("Download QR - No member ID provided");
        }
    }
}

// <CHANGE> Fetch attendance records
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

$debug_info = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE date = CURDATE()")->fetch_assoc();
error_log("Total attendance records today: " . $debug_info['total']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css?v=1.0">
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
                            statusDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>QR Code Detected! Processing...';

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

                    startBtn.disabled = true;
                    stopBtn.disabled = false;
                    scanOverlay.style.display = 'block';
                    statusDiv.className = 'status-badge status-scanning';
                    statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Scanning for QR codes...';

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
                statusDiv.innerHTML = '<i class="fas fa-info-circle me-2"></i>Click "Start Scanning" to begin';
            }

            function createOptimizedCanvas() {
                const canvas = document.createElement('canvas');
                canvas.getContext('2d', { willReadFrequently: true });
                return canvas;
            }

            function handleScannerError() {
                statusDiv.className = 'status-badge status-error';
                statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Unable to initialize scanner';
            }

            function handleDecodeError() {
                statusDiv.className = 'status-badge status-error';
                statusDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Unable to decode QR code';
            }

            window.addEventListener('beforeunload', () => {
                if (qrScanner) {
                    qrScanner.stop();
                    qrScanner.destroy();
                }
            });

            qrInput.addEventListener('focus', function() {
                if (qrScanner) {
                    stopScanning();
                }
            });

            const manualForm = document.getElementById('manual-entry-form');
            manualForm.addEventListener('submit', function(e) {
                console.log('Manual entry form submitted');
                console.log('Form data:', new FormData(this));
            });

            // Initialize autocomplete for both search fields
            initializeAutocomplete('member_search_send', 'member_id_send', 'member_suggestions_send');
            initializeAutocomplete('member_search_download', 'member_id_download', 'member_suggestions_download');
        });
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8f9fa;
            color: #2c3e50;
            min-height: 100vh;
        }

        .main-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .page-content {
            flex: 1;
            padding: 2rem 1rem;
        }

        /* <CHANGE> Improved header styling with cleaner look */
        .page-header {
            background: white;
            border-radius: 12px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #3b82f6;
        }

        .page-header h2 {
            margin: 0 0 0.5rem 0;
            color: #1a202c;
            font-weight: 700;
            font-size: 1.875rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-header p {
            margin: 0.5rem 0 0 0;
            color: #64748b;
            font-size: 0.95rem;
        }

        /* <CHANGE> Remove debug info display for cleaner UI */
        .debug-info {
            display: none;
        }

        /* <CHANGE> Modern alert styling */
        .alert-modern {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success-modern {
            background: #ecfdf5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-danger-modern {
            background: #fef2f2;
            color: #7f1d1d;
            border-left: 4px solid #ef4444;
        }

        /* <CHANGE> Enhanced card styling */
        .card {
            border: none;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            overflow: hidden;
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .card-header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            padding: 1.5rem;
            font-weight: 600;
        }

        .card-header h5 {
            margin: 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-body {
            padding: 1.75rem;
        }

        /* <CHANGE> Video wrapper improvements */
        .video-wrapper {
            position: relative;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            aspect-ratio: 16 / 9;
            max-height: 400px;
        }

        #qr-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* <CHANGE> Improved scan overlay */
        .scan-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 220px;
            height: 220px;
            border: 3px solid #3b82f6;
            border-radius: 10px;
            pointer-events: none;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.6);
        }

        .scan-overlay::before,
        .scan-overlay::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 25px;
            border: 3px solid #3b82f6;
        }

        .scan-overlay::before {
            top: -3px;
            left: -3px;
            border-right: none;
            border-bottom: none;
        }

        .scan-overlay::after {
            bottom: -3px;
            right: -3px;
            border-left: none;
            border-top: none;
        }

        /* <CHANGE> Improved button styling */
        .scanner-controls {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .btn-scanner {
            padding: 0.75rem 1.75rem;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-scanner-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn-scanner-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-scanner-secondary {
            background: #e5e7eb;
            color: #374151;
        }

        .btn-scanner-secondary:hover:not(:disabled) {
            background: #d1d5db;
            transform: translateY(-2px);
        }

        .btn-scanner:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* <CHANGE> Improved status badge */
        .status-badge {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }

        .status-info {
            background: #eff6ff;
            color: #1e40af;
            border-left-color: #3b82f6;
        }

        .status-success {
            background: #ecfdf5;
            color: #065f46;
            border-left-color: #10b981;
        }

        .status-scanning {
            background: #fef3c7;
            color: #92400e;
            border-left-color: #f59e0b;
            animation: pulse 2s infinite;
        }

        .status-error {
            background: #fef2f2;
            color: #7f1d1d;
            border-left-color: #ef4444;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* <CHANGE> Form styling improvements */
        .form-control-modern {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: #f9fafb;
        }

        .form-control-modern:focus {
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        /* <CHANGE> Action button styling */
        .btn-process {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            cursor: pointer;
            width: 100%;
        }

        .btn-process:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }

        /* <CHANGE> Attendance list improvements */
        .attendance-list {
            max-height: 450px;
            overflow-y: auto;
        }

        .attendance-list::-webkit-scrollbar {
            width: 6px;
        }

        .attendance-list::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 10px;
        }

        .attendance-list::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }

        .attendance-list::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        .attendance-item {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s ease;
        }

        .attendance-item:hover {
            background: #f9fafb;
        }

        .attendance-item:last-child {
            border-bottom: none;
        }

        .member-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.35rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .time-info {
            color: #6b7280;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-bottom: 0.5rem;
        }

        .status-badge-small {
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 0.35rem;
        }

        .badge-checked-in {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-checked-out {
            background: #f3f4f6;
            color: #374151;
        }

        /* <CHANGE> Empty state styling */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.4;
        }

        /* <CHANGE> Improved responsive layout */
        @media (max-width: 992px) {
            .page-content {
                padding: 1.5rem 1rem;
            }

            .page-header {
                padding: 2rem;
            }

            .page-header h2 {
                font-size: 1.5rem;
            }

            .card-body {
                padding: 1.25rem;
            }
        }

        @media (max-width: 576px) {
            .page-content {
                padding: 1rem;
            }

            .page-header {
                padding: 1.5rem;
                margin-bottom: 1rem;
            }

            .page-header h2 {
                font-size: 1.25rem;
            }

            .page-header p {
                font-size: 0.85rem;
            }

            .scanner-controls {
                flex-direction: column;
            }

            .btn-scanner {
                width: 100%;
                justify-content: center;
            }

            .video-wrapper {
                max-height: 300px;
            }

            .row {
                --bs-gutter-x: 0.5rem;
            }

            .card {
                margin-bottom: 1rem;
            }
        }

        /* <CHANGE> Sample QR box styling */
        .sample-qr-box {
            background: #fef3c7;
            padding: 1rem;
            margin-bottom: 1.25rem;
            border-radius: 8px;
            border-left: 4px solid #f59e0b;
            font-size: 0.85rem;
            color: #78350f;
        }

        .sample-qr-box strong {
            display: block;
            margin-bottom: 0.5rem;
        }

        /* <CHANGE> Autocomplete dropdown styling */
        .suggestions-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s ease;
        }

        .suggestion-item:hover {
            background: #f8fafc;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .suggestion-id {
            font-size: 0.8rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include '../includes/header.php'; ?>

        <div class="page-content">
            <div class="page-header">
                <h2><i class="fas fa-qrcode"></i>Attendance Scanner</h2>
                <p>Manage check-ins and check-outs with QR code scanning</p>
            </div>

            <?php if ($message): ?>
                <div class="alert-modern alert-success-modern">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert-modern alert-danger-modern">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="container-fluid">
                <div class="row g-4">
                    <!-- Main Scanner -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-camera"></i>QR Code Scanner</h5>
                            </div>
                            <div class="card-body">
                                <div class="video-wrapper">
                                    <video id="qr-video"></video>
                                    <div class="scan-overlay" id="scan-overlay" style="display: none;"></div>
                                </div>
                                <div class="scanner-controls">
                                    <button id="start-scan" class="btn btn-scanner btn-scanner-primary">
                                        <i class="fas fa-play"></i>Start Scanning
                                    </button>
                                    <button id="stop-scan" class="btn btn-scanner btn-scanner-secondary" disabled>
                                        <i class="fas fa-stop"></i>Stop Scanning
                                    </button>
                                </div>
                                <div id="scan-status" class="status-badge status-info">
                                    <i class="fas fa-info-circle me-2"></i>Click "Start Scanning" to begin
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <!-- Manual Entry -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-keyboard"></i>Manual Entry</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="manual-entry-form">
                                    <input type="hidden" name="manual_entry" value="1">
                                    <input type="hidden" name="scan_qr" value="Process Attendance">
                                    <div class="mb-3">
                                        <label for="qr_code" class="form-label">QR Code Data</label>
                                        <input type="text" class="form-control form-control-modern" id="qr_code" name="qr_code" placeholder="Scan or enter QR code" required>
                                        <small class="text-muted">Paste or type QR code data</small>
                                    </div>
                                    <button type="submit" class="btn btn-process">
                                        <i class="fas fa-check-circle me-2"></i>Process
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Today's Attendance -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-clock"></i>Today's Check-ins</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="attendance-list">
                                    <?php if ($recent_qr_attendance->num_rows > 0): ?>
                                        <?php while ($record = $recent_qr_attendance->fetch_assoc()): ?>
                                            <div class="attendance-item">
                                                <div class="member-name">
                                                    <i class="fas fa-user-circle"></i><?php echo htmlspecialchars($record['person_name']); ?>
                                                    <small style="font-weight: 400; color: #6b7280;">(<?php echo ucfirst($record['person_role']); ?>)</small>
                                                </div>
                                                <div class="time-info">
                                                    <i class="fas fa-arrow-right"></i><?php echo date('g:i A', strtotime($record['check_in'])); ?>
                                                    <?php if ($record['check_out']): ?>
                                                        <i class="fas fa-arrow-left"></i><?php echo date('g:i A', strtotime($record['check_out'])); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="status-badge-small <?php echo is_null($record['check_out']) ? 'badge-checked-in' : 'badge-checked-out'; ?>">
                                                    <?php echo is_null($record['check_out']) ? '● Checked In' : '● Checked Out'; ?>
                                                </span>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p class="mb-0">No check-ins yet today</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- QR Generation Tools -->
                <div class="row g-4 mt-2">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-qrcode"></i>Send QR Code</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="generate_qr_form">
                                    <div class="mb-3">
                                        <label for="member_id_send" class="form-label">Search Member</label>
                                        <div class="position-relative">
                                            <input type="text" class="form-control form-control-modern" id="member_search_send" name="member_search" placeholder="Type member name or ID..." autocomplete="off" required>
                                            <input type="hidden" id="member_id_send" name="member_id">
                                            <div id="member_suggestions_send" class="suggestions-dropdown"></div>
                                        </div>
                                        <small class="text-muted">Search by name or enter member ID</small>
                                    </div>
                                    <button type="submit" name="generate_qr" class="btn btn-process" onclick="return handleGenerateClick()">
                                        <i class="fas fa-paper-plane me-2"></i>Send via WhatsApp
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-download"></i>Download QR Code</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="download_qr_form">
                                    <div class="mb-3">
                                        <label for="member_id_download" class="form-label">Search Member</label>
                                        <div class="position-relative">
                                            <input type="text" class="form-control form-control-modern" id="member_search_download" name="member_search" placeholder="Type member name or ID..." autocomplete="off" required>
                                            <input type="hidden" id="member_id_download" name="member_id">
                                            <div id="member_suggestions_download" class="suggestions-dropdown"></div>
                                        </div>
                                        <small class="text-muted">Search by name or enter member ID</small>
                                    </div>
                                    <button type="submit" name="generate_qr_download" value="1" class="btn btn-process">
                                        <i class="fas fa-download me-2"></i>Download QR Code
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global functions for button handlers
        function handleDownloadClick() {
            console.log('📤 DOWNLOAD BUTTON CLICKED VIA ONCLICK!');
            console.log('Form validation starting...');

            // Check hidden input value specifically
            const hiddenInput = document.getElementById('member_id_download');
            console.log('🔍 Hidden member_id_download value:', hiddenInput ? `"${hiddenInput.value}"` : 'NOT FOUND');

            const searchInput = document.getElementById('member_search_download');
            console.log('🔍 Search member_search_download value:', searchInput ? `"${searchInput.value}"` : 'NOT FOUND');

            // Check if member_id is empty
            if (!hiddenInput || !hiddenInput.value.trim()) {
                console.error('❌ ERROR: member_id is empty! User must select a member from dropdown first.');
                alert('Please select a member from the dropdown first before downloading the QR code!');
                if (searchInput) searchInput.focus(); // Focus the search input
                return false; // Prevent form submission
            }

            console.log('✅ Form validation passed, proceeding with download...');
            console.log('📤 Form will submit now');
            // Allow form submission
            return true;
        }

        function handleGenerateClick() {
            console.log('🎯 GENERATE BUTTON CLICKED VIA ONCLICK!');
            console.log('Generate form validation starting...');

            // Check hidden input value specifically
            const hiddenInput = document.getElementById('member_id_send');
            console.log('🔍 Hidden member_id_send value:', hiddenInput ? `"${hiddenInput.value}"` : 'NOT FOUND');

            const searchInput = document.getElementById('member_search_send');
            console.log('🔍 Search member_search_send value:', searchInput ? `"${searchInput.value}"` : 'NOT FOUND');

            // Check if member_id is empty
            if (!hiddenInput || !hiddenInput.value.trim()) {
                console.error('❌ ERROR: member_id is empty! User must select a member from dropdown first.');
                alert('Please select a member from the dropdown first before generating the QR code!');
                searchInput.focus(); // Focus the search input
                return false; // Prevent form submission
            }

            console.log('✅ Generate form validation passed, proceeding with generation...');
            // Allow form submission
            return true;
        }

        // Member search autocomplete functionality
        document.addEventListener('DOMContentLoaded', function() {
            function initializeAutocomplete(searchInputId, hiddenInputId, suggestionsId) {
                const searchInput = document.getElementById(searchInputId);
                const hiddenInput = document.getElementById(hiddenInputId);
                const suggestionsDiv = document.getElementById(suggestionsId);

                if (!searchInput || !hiddenInput || !suggestionsDiv) {
                    console.error('Missing elements for autocomplete:', { searchInputId, hiddenInputId, suggestionsId });
                    return;
                }

                let searchTimeout;

                searchInput.addEventListener('input', function() {
                    const query = this.value.trim();
                    clearTimeout(searchTimeout);

                    if (query.length < 2) {
                        suggestionsDiv.style.display = 'none';
                        hiddenInput.value = '';
                        return;
                    }

                    searchTimeout = setTimeout(() => {
                        searchMembers(query, suggestionsDiv, hiddenInput, searchInput);
                    }, 300);
                });

                searchInput.addEventListener('focus', function() {
                    if (this.value.trim().length >= 2) {
                        searchMembers(this.value.trim(), suggestionsDiv, hiddenInput, searchInput);
                    }
                });

                // Hide suggestions when clicking outside
                document.addEventListener('click', function(e) {
                    if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                        suggestionsDiv.style.display = 'none';
                    }
                });

                // Handle keyboard navigation
                searchInput.addEventListener('keydown', function(e) {
                    const items = suggestionsDiv.querySelectorAll('.suggestion-item');
                    let activeItem = suggestionsDiv.querySelector('.suggestion-item.active');

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        if (!activeItem && items.length > 0) {
                            items[0].classList.add('active');
                        } else if (activeItem && activeItem.nextElementSibling) {
                            activeItem.classList.remove('active');
                            activeItem.nextElementSibling.classList.add('active');
                        }
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        if (activeItem && activeItem.previousElementSibling) {
                            activeItem.classList.remove('active');
                            activeItem.previousElementSibling.classList.add('active');
                        }
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (activeItem) {
                            activeItem.click();
                        }
                    } else if (e.key === 'Escape') {
                        suggestionsDiv.style.display = 'none';
                        searchInput.blur();
                    }
                });
            }

            function searchMembers(query, suggestionsDiv, hiddenInput, searchInput) {
                const apiUrl = `../api/search.php?query=${encodeURIComponent(query)}`;

                fetch(apiUrl)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        suggestionsDiv.innerHTML = '';
                        const members = data.members || [];

                        if (members.length === 0) {
                            const noResults = document.createElement('div');
                            noResults.className = 'suggestion-item';
                            noResults.innerHTML = '<div class="suggestion-name">No members found</div>';
                            suggestionsDiv.appendChild(noResults);
                        } else {
                            members.forEach((member) => {
                                const item = document.createElement('div');
                                item.className = 'suggestion-item';
                                item.innerHTML = `
                                    <div class="suggestion-name">${member.name}</div>
                                    <div class="suggestion-id">ID: ${member.id}</div>
                                `;
                                item.addEventListener('click', function() {
                                    searchInput.value = `${member.name} (ID: ${member.id})`;
                                    hiddenInput.value = member.id;
                                    suggestionsDiv.style.display = 'none';
                                });
                                suggestionsDiv.appendChild(item);
                            });
                        }

                        suggestionsDiv.style.display = (members.length > 0 || query.length >= 2) ? 'block' : 'none';
                    })
                    .catch(error => {
                        console.error('Error searching members:', error);
                        suggestionsDiv.style.display = 'none';
                    });
            }

            // Initialize autocomplete for both search fields
            initializeAutocomplete('member_search_send', 'member_id_send', 'member_suggestions_send');
            initializeAutocomplete('member_search_download', 'member_id_download', 'member_suggestions_download');

            // Add form submit debugging
            const downloadForm = document.getElementById('download_qr_form');
            if (downloadForm) {
                downloadForm.addEventListener('submit', function(e) {
                    console.log('🚀 DOWNLOAD FORM SUBMIT EVENT FIRED');
                    
                    // Validate member selection
                    const hiddenInput = document.getElementById('member_id_download');
                    const searchInput = document.getElementById('member_search_download');
                    
                    console.log('🔍 Hidden member_id_download value:', hiddenInput ? `"${hiddenInput.value}"` : 'NOT FOUND');
                    console.log('🔍 Search member_search_download value:', searchInput ? `"${searchInput.value}"` : 'NOT FOUND');
                    
                    if (!hiddenInput || !hiddenInput.value.trim()) {
                        console.error('❌ ERROR: member_id is empty! Preventing form submission.');
                        e.preventDefault();
                        alert('Please select a member from the dropdown first before downloading the QR code!');
                        if (searchInput) searchInput.focus();
                        return false;
                    }
                    
                    console.log('✅ Form validation passed');
                    const formData = new FormData(this);
                    console.log('📋 Form data being submitted:');
                    for (let [key, value] of formData.entries()) {
                        console.log(`  ${key}: "${value}"`);
                    }
                });
            }
        });
    </script>
</html>