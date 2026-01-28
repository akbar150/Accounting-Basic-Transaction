<?php
require_once 'vendor/autoload.php'; // Install TCPDF via composer

use TCPDF as TCPDF;

class PDFGenerator {
    public function generateTransactionReport($transactions, $filters) {
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        
        $pdf->SetCreator('Accounting System');
        $pdf->SetTitle('Transaction Report');
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        
        // Header
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Transaction Report', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $pdf->Ln(10);
        
        // Table
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        
        $headers = ['Date', 'Customer', 'Type', 'Category', 'Amount', 'Balance', 'Comments'];
        $widths = [25, 40, 20, 30, 25, 25, 60];
        
        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 8, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Data
        $pdf->SetFont('helvetica', '', 9);
        foreach ($transactions as $t) {
            $pdf->Cell($widths[0], 7, $t['transaction_date'], 1);
            $pdf->Cell($widths[1], 7, $t['customer_name'], 1);
            $pdf->Cell($widths[2], 7, $t['transaction_type'], 1, 0, 'C');
            $pdf->Cell($widths[3], 7, $t['category_name'] ?? '-', 1);
            $pdf->Cell($widths[4], 7, number_format($t['amount'], 2), 1, 0, 'R');
            $pdf->Cell($widths[5], 7, number_format($t['balance'], 2), 1, 0, 'R');
            $pdf->Cell($widths[6], 7, substr($t['comments'], 0, 30), 1);
            $pdf->Ln();
        }
        
        return $pdf->Output('transaction_report.pdf', 'D');
    }
}
?>