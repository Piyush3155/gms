<?php
require_once 'fpdf/fpdf.php';

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(40, 10, 'Hello World!');
$content = $pdf->Output('', 'S');
echo 'Buffer length: ' . strlen($content) . "\n";
echo 'First 100 chars: ' . substr($content, 0, 100) . "\n";
?>