<?php
require_once '../includes/config.php';
require_role('admin');
require_once '../fpdf/fpdf.php';

// --- INPUT VALIDATION AND SANITIZATION ---
if (!isset($_GET['member_id']) || !is_numeric($_GET['member_id'])) {
    die('Invalid member ID');
}

$member_id = (int)$_GET['member_id'];

// Robustness check: Ensure ID is a positive integer
if ($member_id <= 0) {
    die('Invalid member ID');
}


// Fetch member, plan, and trainer details
$stmt = $conn->prepare("
    SELECT
        m.*,
        p.name AS plan_name,
        p.duration_months,
        p.amount,
        t.name AS trainer_name,
        DATE_ADD(m.join_date, INTERVAL p.duration_months MONTH) AS expiry_date
    FROM members m
    LEFT JOIN plans p ON m.plan_id = p.id
    LEFT JOIN trainers t ON m.trainer_id = t.id
    WHERE m.id = ?
");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();

if (!$member) {
    die('Member not found');
}

// Gym settings (Assuming get_gym_settings() returns an array)
$settings = get_gym_settings();

// --- CUSTOM FPDF CLASS WITH UI IMPROVEMENTS ---
class PDF extends FPDF {
    private $settings;
    
    // Define custom colors for professional look (e.g., a dark blue or gray)
    const COLOR_HEADER_BG = [220, 220, 220]; // Light Gray for Section Headers
    const COLOR_TABLE_HEAD_BG = [180, 180, 180]; // Medium Gray for Table Header
    const COLOR_TOTAL_BG = [240, 240, 240]; // Very Light Gray for Total Row
    const COLOR_LINE = [150, 150, 150]; // Medium Gray for Separator Lines

    function __construct($settings) {
        parent::__construct('P', 'mm', 'A4');
        $this->settings = $settings;
    }

    function Header() {
        // Gym Logo and Info Section
        
        // 1. Logo Path Sanitization (Security Improvement)
        $logo_path = '../assets/images/' . basename($this->settings['logo']);;
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 10, 5, 30);
        }   

        // 2. Gym Name and Info (Right aligned)
        $this->SetTextColor(0, 0, 0); // Black text
        $this->SetFont('Arial', 'B', 18);
        $this->Cell(0, 8, $this->settings['gym_name'], 0, 1, 'R');

        $this->SetFont('Arial', '', 10);
        if (!empty($this->settings['address'])) {
            $this->Cell(0, 5, $this->settings['address'], 0, 1, 'R');
        }
        if (!empty($this->settings['contact'])) {
            $this->Cell(0, 5, 'Phone: ' . $this->settings['contact'], 0, 1, 'R');
        }
        if (!empty($this->settings['email'])) {
            $this->Cell(0, 5, 'Email: ' . $this->settings['email'], 0, 1, 'R');
        }

        // 3. Separator line (UI Improvement: Use a colored line)
        $this->Ln(5);
        $this->SetDrawColor(self::COLOR_LINE[0], self::COLOR_LINE[1], self::COLOR_LINE[2]);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(8);
    }

    function Footer() {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100); // Gray text for footer

        $this->Cell(0, 5, 'This is a computer-generated receipt and does not require a signature.', 0, 1, 'C');
        if (!empty($this->settings['tagline'])) {
            $this->Cell(0, 5, '"' . $this->settings['tagline'] . '"', 0, 1, 'C');
        }
        $this->Cell(0, 5, 'Thank you for choosing ' . $this->settings['gym_name'], 0, 0, 'C');
    }

    function ReceiptTitle() {
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, 'MEMBERSHIP ADMISSION RECEIPT', 0, 1, 'C');
        $this->Ln(5);
    }

    function ReceiptDetails($member) {
        $this->SetFont('Arial', '', 11);
        $this->SetTextColor(0, 0, 0);

        // UI Improvement: Add current year to Receipt Number
        $receipt_number = date('Y') . '-' . str_pad($member['id'], 5, '0', STR_PAD_LEFT);

        $this->Cell(95, 7, 'Receipt No: #' . $receipt_number, 0, 0, 'L');
        $this->Cell(95, 7, 'Date: ' . date('d M, Y', strtotime($member['join_date'])), 0, 1, 'R');
        $this->Ln(5);
    }

    function MemberDetails($member) {
        // UI Improvement: Use filled background for section header
        $this->SetFillColor(self::COLOR_HEADER_BG[0], self::COLOR_HEADER_BG[1], self::COLOR_HEADER_BG[2]);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, 'Member Details', 1, 1, 'L', true);

        // Data rows
        $this->SetFont('Arial', '', 11);
        $this->Cell(50, 8, 'Name:', 1, 0);
        $this->Cell(140, 8, $member['name'], 1, 1);
        $this->Cell(50, 8, 'Contact:', 1, 0);
        $this->Cell(140, 8, $member['contact'], 1, 1);
        $this->Cell(50, 8, 'Email:', 1, 0);
        $this->Cell(140, 8, $member['email'], 1, 1);
        $this->Cell(50, 8, 'Address:', 1, 0);
        $this->Cell(140, 8, $member['address'], 1, 1);
        $this->Ln(10);
    }

    function MembershipTable($member) {
        // UI Improvement: Use filled background for table header
        $this->SetFillColor(self::COLOR_TABLE_HEAD_BG[0], self::COLOR_TABLE_HEAD_BG[1], self::COLOR_TABLE_HEAD_BG[2]);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(95, 8, 'Description', 1, 0, 'C', true);
        $this->Cell(95, 8, 'Amount (Rs.)', 1, 1, 'C', true);

        // Plan Row
        $this->SetFont('Arial', '', 11);
        $plan_desc = 'Membership Plan: ' . $member['plan_name'] . ' (' . $member['duration_months'] . ' months)';
        $this->Cell(95, 8, $plan_desc, 1, 0, 'L');
        $this->Cell(95, 8, number_format($member['amount'], 2), 1, 1, 'R');

        // Trainer Row (UI Improvement: Make it look like an included service)
        if ($member['trainer_name']) {
            $this->SetFont('Arial', 'I', 10); // Smaller, italic font for supplemental info
            $this->Cell(95, 6, 'Includes Personal Trainer: ' . $member['trainer_name'], 1, 0, 'L');
            $this->Cell(95, 6, 'Included (N/A)', 1, 1, 'R');
            $this->SetFont('Arial', '', 11); // Reset font
        }
        
        // Total Row (UI Improvement: Highlighted background and currency symbol)
        $this->SetFillColor(self::COLOR_TOTAL_BG[0], self::COLOR_TOTAL_BG[1], self::COLOR_TOTAL_BG[2]);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(95, 8, 'TOTAL AMOUNT PAID', 1, 0, 'R', true);
        $this->Cell(95, 8, 'Rs. ' . number_format($member['amount'], 2), 1, 1, 'R', true);
        $this->Ln(10);
    }

    function MembershipPeriod($member) {
        // UI Improvement: Use filled background for section header
        $this->SetFillColor(self::COLOR_HEADER_BG[0], self::COLOR_HEADER_BG[1], self::COLOR_HEADER_BG[2]);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, 'Membership Period', 1, 1, 'L', true);
        
        // Data rows
        $this->SetFont('Arial', '', 11);
        $this->Cell(50, 8, 'Start Date:', 1, 0);
        $this->Cell(140, 8, date('d M, Y', strtotime($member['join_date'])), 1, 1);
        $this->Cell(50, 8, 'End Date:', 1, 0);
        $this->Cell(140, 8, date('d M, Y', strtotime($member['expiry_date'])), 1, 1);
    }
}

// --- GENERATE PDF ---
$pdf = new PDF($settings);
$pdf->AddPage();
$pdf->ReceiptTitle();
$pdf->ReceiptDetails($member);
$pdf->MemberDetails($member);
$pdf->MembershipTable($member);
$pdf->MembershipPeriod($member);

$filename = 'Admission_Receipt_' . preg_replace('/[^A-Za-z0-9\-_]/', '_', $member['name']) . '_' . date('Y-m-d') . '.pdf';
$pdf->Output($filename, 'I');
?>