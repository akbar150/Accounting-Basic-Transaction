<?php
// api/dashboard.php - CRITICAL FIX
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';

try {
    $user = authenticate();
    $action = $_GET['action'] ?? 'stats';
    
    $db = Database::getInstance()->getConnection();
    
    if ($action === 'stats') {
        // Total customers
        $customers = $db->query("SELECT COUNT(*) FROM customers WHERE status = 1")->fetchColumn();
        
        // Totals
        $totals = $db->query("
            SELECT 
                COALESCE(SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END), 0) as total_credit
            FROM transactions
        ")->fetch();
        
        $net = ($totals['total_debit'] ?? 0) - ($totals['total_credit'] ?? 0);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_customers' => (int)$customers,
                'total_debit' => (float)$totals['total_debit'],
                'total_credit' => (float)$totals['total_credit'],
                'net_balance' => (float)$net
            ]
        ]);
    } 
    elseif ($action === 'recent') {
        $stmt = $db->query("
            SELECT t.*, c.name as customer_name 
            FROM transactions t 
            JOIN customers c ON t.customer_id = c.id 
            ORDER BY t.created_at DESC 
            LIMIT 10
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>