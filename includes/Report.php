<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Transaction.php';

class Report {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getDashboardStats() {
        $stats = [];
        
        $stmt = $this->db->query("SELECT COUNT(*) FROM customers WHERE status = 1");
        $stats['total_customers'] = $stmt->fetchColumn();
        
        $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END), 0) as total_debit,
                    COALESCE(SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END), 0) as total_credit
                FROM transactions";
        $stmt = $this->db->query($sql);
        $totals = $stmt->fetch();
        $stats['total_debit'] = $totals['total_debit'];
        $stats['total_credit'] = $totals['total_credit'];
        $stats['net_balance'] = $totals['total_debit'] - $totals['total_credit'];
        
        return $stats;
    }
    
    public function exportCSV($filters) {
        $transaction = new Transaction();
        $result = $transaction->getFiltered($filters, 1, 10000);
        
        $filename = 'report_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = EXPORT_PATH . $filename;
        
        if (!file_exists(EXPORT_PATH)) {
            mkdir(EXPORT_PATH, 0755, true);
        }
        
        $fp = fopen($filepath, 'w');
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for UTF-8
        
        fputcsv($fp, ['Date', 'Customer', 'Type', 'Category', 'Amount', 'Comments', 'Reference']);
        
        foreach ($result['data'] as $row) {
            $type = $row['transaction_type'] == 'debit' ? 'Deposit' : 'Withdraw';
            fputcsv($fp, [
                $row['transaction_date'],
                $row['customer_name'],
                $type,
                $row['category_name'],
                $row['amount'],
                $row['comments'],
                $row['reference_id']
            ]);
        }
        
        fclose($fp);
        return $filename;
    }
    
    public function generatePDF($filters) {
        if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
            throw new Exception("PDF library not installed. Run: composer require tecnickcom/tcpdf");
        }
        
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $transaction = new Transaction();
        $result = $transaction->getFiltered($filters, 1, 1000);
        
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Accounting System');
        $pdf->SetTitle('Transaction Report');
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Transaction Report', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $pdf->Ln(10);
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        
        $headers = ['Date', 'Customer', 'Type', 'Category', 'Amount', 'Comments'];
        $widths = [25, 50, 25, 35, 25, 80];
        
        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 8, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        $pdf->SetFont('helvetica', '', 9);
        foreach ($result['data'] as $t) {
            $type = $t['transaction_type'] == 'debit' ? 'Deposit' : 'Withdraw';
            
            $pdf->Cell($widths[0], 7, $t['transaction_date'], 1);
            $pdf->Cell($widths[1], 7, $t['customer_name'], 1);
            $pdf->Cell($widths[2], 7, $type, 1, 0, 'C');
            $pdf->Cell($widths[3], 7, $t['category_name'] ?: '-', 1);
            $pdf->Cell($widths[4], 7, number_format($t['amount'], 2), 1, 0, 'R');
            $pdf->Cell($widths[5], 7, substr($t['comments'], 0, 40), 1);
            $pdf->Ln();
        }
        
        $pdf->Output('transaction_report.pdf', 'D');
        exit;
    }
}
?>