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
    private $settings;

    function __construct($settings) {
        parent::__construct();
        $this->settings = $settings;
    }

    function Header() {
        // Gym Logo
        $logo_path = '../' . $this->settings['logo']; // Adjust path to use the database value directly
        $file_extension = strtolower(pathinfo($logo_path, PATHINFO_EXTENSION)); // Get file extension

        if (!empty($this->settings['logo']) && file_exists($logo_path)) {
            if (in_array($file_extension, ['png', 'jpg', 'jpeg'])) { // Check for supported formats
                try {
                    $this->Image($logo_path, 10, 12, 30, 0, strtoupper($file_extension)); // Specify file type
                } catch (Exception $e) {
                    error_log('Error displaying logo: ' . $e->getMessage());
                    $this->SetFont('Arial', 'B', 20);
                    $this->SetXY(15, 15);
                    $this->Cell(30, 10, 'GYM', 0, 0, 'C');
                }
            } else {
                error_log('Unsupported image format: ' . $logo_path);
                $this->SetFont('Arial', 'B', 20);
                $this->SetXY(15, 15);
                $this->Cell(30, 10, 'GYM', 0, 0, 'C');
            }
        } else {
            error_log('Logo not found or invalid: ' . $logo_path);
            $this->SetFont('Arial', 'B', 20);
            $this->SetXY(15, 15);
            $this->Cell(30, 10, 'GYM', 0, 0, 'C');
        }

        // Gym Info (Right-aligned)
        $this->SetTextColor(50, 50, 50); // Dark gray for gym name
        $this->SetFont('Arial', 'B', 18);
        $this->Cell(0, 8, $this->settings['gym_name'], 0, 1, 'R');
        $this->SetTextColor(0); // Reset to black
        
        if (!empty($this->settings['address'])) {
            $this->SetFont('Arial', '', 10);
            $this->MultiCell(0, 5, $this->settings['address'], 0, 'R');
        }

        if (!empty($this->settings['contact'])) {
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 5, 'Phone: ' . $this->settings['contact'], 0, 1, 'R');
        }
        if (!empty($this->settings['email'])) {
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 5, 'Email: ' . $this->settings['email'], 0, 1, 'R');
        }

        // Line separator
        $this->Ln(5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'This is a computer-generated receipt and does not require a signature.', 0, 0, 'C');
    }

    function ReceiptTitle() {
        $this->SetTextColor(80, 80, 80); // Dark gray
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'MEMBERSHIP ADMISSION RECEIPT', 0, 1, 'C');
        $this->SetTextColor(0); // Reset to black
        $this->Ln(5);
    }

    function ReceiptDetails($member) {
        $this->SetFont('Arial', '', 12);
        $this->Cell(95, 7, 'Receipt No: #' . str_pad($member['id'], 6, '0', STR_PAD_LEFT), 0, 0, 'L');
        $this->Cell(95, 7, 'Date: ' . date('d M, Y', strtotime($member['join_date'])), 0, 1, 'R');
        $this->Ln(5);
    }

    function MemberDetails($member) {
        $this->SetFillColor(80, 80, 80); // Dark Gray
        $this->SetTextColor(255); // White
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, 'BILLED TO', 0, 1, 'L', true);
        $this->SetTextColor(0); // Reset to black

        $this->Ln(2);
        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 6, $member['name'], 0, 1, 'L');
        if($member['address']) $this->Cell(0, 6, $member['address'], 0, 1, 'L');
        if($member['contact']) $this->Cell(0, 6, 'Contact: ' . $member['contact'], 0, 1, 'L');
        if($member['email']) $this->Cell(0, 6, 'Email: ' . $member['email'], 0, 1, 'L');
        $this->Ln(10);
    }

    function SetFillColor($r, $g=null, $b=null) {
        if(($r==0 && $g==0 && $b==0) || $g===null)
            $this->FillColor = sprintf('%.3F g', $r/255);
        else
            $this->FillColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
        $this->ColorFlag = ($this->FillColor!=$this->TextColor);
    }

    function SetTextColor($r, $g=null, $b=null) {
        if(($r==0 && $g==0 && $b==0) || $g===null)
            $this->TextColor = sprintf('%.3F g', $r/255);
        else
            $this->TextColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
        $this->ColorFlag = ($this->FillColor!=$this->TextColor);
    }

    function Image($file, $x=null, $y=null, $w=0, $h=0, $type='', $link='')
    {
        if(!isset($this->images[$file]))
        {
            // First use of this image, get info
            if($type=='')
            {
                $pos = strrpos($file,'.');
                if(!$pos)
                    $this->Error('Image file has no extension and no type was specified: '.$file);
                $type = substr($file,$pos+1);
            }
            $type = strtolower($type);
            if($type=='jpeg')
                $type = 'jpg';
            $mtd = '_parse'.$type;
            if(!method_exists($this,$mtd))
                $this->Error('Unsupported image type: '.$type);
            $info = $this->$mtd($file);
            $info['i'] = count($this->images)+1;
            $this->images[$file] = $info;
        }
        else
            $info = $this->images[$file];

        // Automatic width and height calculation if needed
        if($w==0 && $h==0)
        {
            // Put image at 72 dpi
            $w = -96;
            $h = -96;
        }
        if($w<0)
            $w = -$info['w']*72/$w/$this->k;
        if($h<0)
            $h = -$info['h']*72/$h/$this->k;
        if($w==0)
            $w = $h*$info['w']/$info['h'];
        if($h==0)
            $h = $w*$info['h']/$info['w'];

        // Flowing mode
        if($y===null)
        {
            if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AutoPageBreak)
            {
                // Automatic page break
                $x2 = $this->x;
                $this->AddPage($this->CurOrientation,$this->CurPageSize);
                $this->x = $x2;
            }
            $y = $this->y;
            $this->y += $h;
        }

        if($x===null)
            $x = $this->x;
        $this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q',$w*$this->k,$h*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k,$info['i']));
        if($link)
            $this->Link($x,$y,$w,$h,$link);
    }

    function SetXY($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    function GetY()
    {
        return $this->y;
    }

    function Line($x1, $y1, $x2, $y2)
    {
        $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S',$x1*$this->k,($this->h-$y1)*$this->k,$x2*$this->k,($this->h-$y2)*$this->k));
    }

    function MembershipTable($member) {
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(50, 50, 50); // Darker Gray
        $this->SetTextColor(255); // White
        $this->Cell(95, 8, 'Description', 1, 0, 'L', true);
        $this->Cell(95, 8, 'Amount', 1, 1, 'R', true);
        $this->SetTextColor(0); // Reset to black

        $this->SetFont('Arial', '', 11);
        $plan_description = 'Membership Plan: ' . $member['plan_name'] . ' (' . $member['duration_months'] . ' months)';
        $this->Cell(95, 8, $plan_description, 1, 0, 'L');
        $this->Cell(95, 8, 'Rs. ' . number_format($member['amount'], 2), 1, 1, 'R');

        if ($member['trainer_name']) {
            $this->Cell(95, 8, 'Personal Trainer: ' . $member['trainer_name'], 1, 0, 'L');
            $this->Cell(95, 8, '', 1, 1, 'R'); // Assuming trainer cost is included or not shown
        }

        // Total
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(230, 230, 230); // Light Gray for total
        $this->Cell(95, 10, 'Total Amount', 1, 0, 'R', true);
        $this->Cell(95, 10, 'Rs. ' . number_format($member['amount'], 2), 1, 1, 'R', true);
        $this->Ln(10);
    }

    function OtherDetails($member) {
        $this->SetFillColor(80, 80, 80); // Dark Gray
        $this->SetTextColor(255); // White
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, 'MEMBERSHIP PERIOD', 0, 1, 'L', true);
        $this->SetTextColor(0); // Reset to black
        
        $this->Ln(2);
        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 7, 'Join Date: ' . date('d M, Y', strtotime($member['join_date'])), 0, 1, 'L');
        $this->Cell(0, 7, 'Expiry Date: ' . date('d M, Y', strtotime($member['expiry_date'])), 0, 1, 'L');
        $this->Ln(5);
    }
}

// Create new PDF document
$pdf = new PDF($settings);
$pdf->AddPage();

// Build the receipt
$pdf->ReceiptTitle();
$pdf->ReceiptDetails($member);
$pdf->MemberDetails($member);
$pdf->MembershipTable($member);
$pdf->OtherDetails($member);

// Output the PDF
$filename = 'Admission_Receipt_' . preg_replace('/[^A-Za-z0-9\-_]/', '_', $member['name']) . '_' . date('Y-m-d') . '.pdf';
$pdf->Output($filename, 'I'); // 'I' for inline, 'D' for download
?>