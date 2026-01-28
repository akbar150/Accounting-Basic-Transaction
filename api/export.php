<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';
require_once '../includes/Report.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$payload = JWT::verify($token);
if (!$payload) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$type = $_GET['type'] ?? 'csv';
$report = new Report();

$filters = [
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null,
    'customer_id' => $_GET['customer_id'] ?? null,
    'transaction_type' => $_GET['transaction_type'] ?? null
];

try {
    if ($type === 'csv') {
        $filename = $report->exportCSV($filters);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        
        readfile(EXPORT_PATH . $filename);
        exit;
        
    } elseif ($type === 'pdf') {
        $report->generatePDF($filters);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid type']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>