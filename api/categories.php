<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';

try {
    $user = authenticate();
    $db = Database::getInstance()->getConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $stmt = $db->prepare("SELECT * FROM transaction_categories WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $data = $stmt->fetch();
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                $stmt = $db->query("SELECT * FROM transaction_categories WHERE status = 1 ORDER BY category_name");
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            }
            break;
            
        case 'POST':
            if (!in_array($user['role'], ['admin', 'super_admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin required']);
                exit;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            $stmt = $db->prepare("INSERT INTO transaction_categories (category_name, description, status) VALUES (?, ?, 1)");
            $stmt->execute([
                $data['category_name'],
                $data['description'] ?? null
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Category added', 'id' => $db->lastInsertId()]);
            break;
            
        case 'PUT':
            if (!in_array($user['role'], ['admin', 'super_admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin required']);
                exit;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $_GET['id'] ?? 0;
            
            $stmt = $db->prepare("UPDATE transaction_categories SET category_name = ?, description = ? WHERE id = ?");
            $stmt->execute([
                $data['category_name'],
                $data['description'] ?? null,
                $id
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Category updated']);
            break;
            
        case 'DELETE':
            if (!in_array($user['role'], ['admin', 'super_admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin required']);
                exit;
            }
            
            $id = $_GET['id'] ?? 0;
            
            // Soft delete
            $stmt = $db->prepare("UPDATE transaction_categories SET status = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Category deleted']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>