<?php
require_once '../includes/config.php';
require_role('admin');

require_once '../fpdf/fpdf.php';

if (!isset($_GET['member_id']) || !is_numeric($_GET['member_id'])) {
    die('Invalid member ID');
}

$member_id = (int)$_GET['member_id'];

// Get member details with plan and trainer info
$member_query = $conn->prepare("
    SELECT
        m.*,
        p.name as plan_name,
        p.duration_months,
        p.amount,
        t.name as trainer_name,
        DATE_ADD(m.join_date, INTERVAL p.duration_months MONTH) as expiry_date
    FROM members m
    LEFT JOIN plans p ON m.plan_id = p.id
    LEFT JOIN trainers t ON m.trainer_id = t.id
    WHERE m.id = ?
");

$member_query->bind_param("i", $member_id);
$member_query->execute();
$member = $member_query->get_result()->fetch_assoc();

if (!$member) {
    die('Member not found');
}

// Get gym settings
$settings = get_gym_settings();

class PDF extends FPDF {
    function Header() {
        // Header is handled in main content
    }

    function Footer() {
        $this->SetY(-30);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'This is a computer generated receipt. No signature required.', 0, 0, 'C');
    }
}

// Create new PDF document
$pdf = new PDF();
$pdf->AddPage();

// Set font
$pdf->SetFont('Arial', '', 12);

// Header with gym logo and info
$y = 15;

// Gym Logo (if exists) - simplified for basic PDF
if (!empty($settings['logo']) && file_exists('../assets/images/' . $settings['logo'])) {
    // Logo not supported in basic PDF
    $x_start = 50;
} else {
    // Default gym icon (text representation)
    $pdf->SetFont('Arial', 'B', 24);
    $pdf->SetX(15);
    $pdf->SetY($y);
    $pdf->Cell(30, 10, 'GYM', 0, 0, 'C');
    $x_start = 50;
}

// Gym Name and Tagline
$pdf->SetFont('Arial', 'B', 20);
$pdf->SetX($x_start);
$pdf->SetY($y);
$pdf->Cell(0, 8, $settings['gym_name'], 0, 0); // Don't move to next line yet

if (!empty($settings['tagline'])) {
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->SetX($x_start);
    $pdf->SetY($y + 8);
    $pdf->Cell(0, 6, $settings['tagline'], 0, 1); // Move to next line after tagline
} else {
    $pdf->Ln(8); // If no tagline, just move down
}

// Contact Information
$contact_info = [];
if (!empty($settings['contact'])) $contact_info[] = 'Phone: ' . $settings['contact'];
if (!empty($settings['email'])) $contact_info[] = 'Email: ' . $settings['email'];
if (!empty($settings['address'])) $contact_info[] = 'Address: ' . $settings['address'];

if (!empty($contact_info)) {
    $pdf->SetFont('Arial', '', 10);
    $y += 16; // Move down from the gym name/tagline
    foreach ($contact_info as $info) {
        $pdf->SetX($x_start);
        $pdf->SetY($y);
        $pdf->Cell(0, 5, $info, 0, 1);
        $y += 5;
    }
}

// Title
$y += 10;
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetX(15);
$pdf->SetY($y);
$pdf->Cell(0, 10, 'MEMBERSHIP ADMISSION RECEIPT', 0, 1, 'C');
$y += 15;

// Receipt details box - simplified
// $pdf->SetFillColor(240, 240, 240);
// $pdf->Rect(15, $y, 180, 80, 'F');

// Receipt Number and Date
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetX(20);
$pdf->SetY($y + 5);
$pdf->Cell(50, 8, 'Receipt No: #' . str_pad($member['id'], 6, '0', STR_PAD_LEFT), 0, 0);

$pdf->SetX(120);
$pdf->SetY($y + 5);
$pdf->Cell(50, 8, 'Date: ' . date('d/m/Y', strtotime($member['join_date'])), 0, 1);

// Member Details
$y += 10;
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetX(20);
$pdf->SetY($y);
$pdf->Cell(0, 7, 'MEMBER DETAILS', 0, 1);

$y += 8;
$pdf->SetFont('Arial', '', 10);

$member_details = [
    'Name' => $member['name'],
    'Email' => $member['email'],
    'Contact' => $member['contact'],
    'Gender' => ucfirst($member['gender'] ?? 'N/A'),
    'Date of Birth' => $member['dob'] ? date('d/m/Y', strtotime($member['dob'])) : 'N/A',
    'Address' => $member['address'] ?: 'N/A'
];

foreach ($member_details as $label => $value) {
    $pdf->SetX(25);
    $pdf->SetY($y);
    $pdf->Cell(40, 6, $label . ':', 0, 0);
    $pdf->Cell(0, 6, $value, 0, 1);
    $y += 6;
}

// Membership Details
$y += 5;
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetX(20);
$pdf->SetY($y);
$pdf->Cell(0, 7, 'MEMBERSHIP DETAILS', 0, 1);

$y += 8;
$pdf->SetFont('Arial', '', 10);

$membership_details = [
    'Plan' => $member['plan_name'] . ' (' . $member['duration_months'] . ' months)',
    'Amount' => '₹' . number_format($member['amount'], 2),
    'Join Date' => date('d/m/Y', strtotime($member['join_date'])),
    'Expiry Date' => date('d/m/Y', strtotime($member['expiry_date'])),
    'Trainer' => $member['trainer_name'] ?: 'Not Assigned',
    'Status' => ucfirst($member['status'])
];

foreach ($membership_details as $label => $value) {
    $pdf->SetX(25);
    $pdf->SetY($y);
    $pdf->Cell(40, 6, $label . ':', 0, 0);
    $pdf->Cell(0, 6, $value, 0, 1);
    $y += 6;
}

// Terms and Conditions
$y += 10;
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetX(15);
$pdf->SetY($y);
$pdf->Cell(0, 6, 'TERMS & CONDITIONS:', 0, 1, 'C');

$y += 8;
$pdf->SetFont('Arial', '', 9);
$terms = [
    '1. Membership is valid from join date to expiry date.',
    '2. Late payment may result in membership suspension.',
    '3. Membership is non-transferable.',
    '4. Please bring this receipt for verification.',
    '5. For any queries, contact gym management.'
];

foreach ($terms as $term) {
    $pdf->SetX(20);
    $pdf->SetY($y);
    $pdf->Cell(0, 5, $term, 0, 1);
    $y += 6;
}

// Signature section
$y += 10;
$pdf->SetFont('Arial', '', 10);
$pdf->SetX(15);
$pdf->SetY($y);
$pdf->Cell(80, 6, 'Member Signature: ___________________________', 0, 0);
$pdf->Cell(0, 6, 'Authorized Signature: ___________________________', 0, 1);

// Output the PDF
$filename = 'Admission_Receipt_' . preg_replace('/[^A-Za-z0-9\-_]/', '_', $member['name']) . '_' . date('Y-m-d') . '.pdf';
$pdf->Output($filename, 'D');
?>