<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';
require_once '../includes/Transaction.php';

try {
    $user = authenticate();
    $method = $_SERVER['REQUEST_METHOD'];
    $transaction = new Transaction();
    
    switch($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $data = $transaction->getById($_GET['id']);
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                $filters = [
                    'customer_id' => $_GET['customer_id'] ?? null,
                    'transaction_type' => $_GET['type'] ?? null,
                    'date_from' => $_GET['date_from'] ?? null,
                    'date_to' => $_GET['date_to'] ?? null
                ];
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 50;
                $data = $transaction->getFiltered($filters, $page, $limit);
                echo json_encode(['success' => true, 'data' => $data]);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (empty($data['customer_id'])) {
                echo json_encode(['success' => false, 'message' => 'Customer required']);
                exit;
            }
            if (empty($data['amount']) || $data['amount'] <= 0) {
                echo json_encode(['success' => false, 'message' => 'Valid amount required']);
                exit;
            }
            
            $id = $transaction->create($data, $user['user_id']);
            echo json_encode(['success' => true, 'data' => ['id' => $id], 'message' => 'Transaction saved']);
            break;
            
        case 'PUT':
            $id = $_GET['id'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID required']);
                exit;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (empty($data['customer_id'])) {
                echo json_encode(['success' => false, 'message' => 'Customer required']);
                exit;
            }
            
            $transaction->update($id, $data, $user['user_id']);
            echo json_encode(['success' => true, 'message' => 'Transaction updated']);
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID required']);
                exit;
            }
            
            $transaction->delete($id);
            echo json_encode(['success' => true, 'message' => 'Transaction deleted']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>