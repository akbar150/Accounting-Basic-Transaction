<?php
require_once '../config.php';
require_once '../includes/Customer.php';

$method = $_SERVER['REQUEST_METHOD'];
$customer = new Customer();

switch($method) {
    case 'GET':
        $user = requireAdmin();
        
        if (isset($_GET['id'])) {
            $data = $customer->getById($_GET['id']);
            jsonResponse(true, $data);
        } else {
            $search = $_GET['search'] ?? '';
            $page = $_GET['page'] ?? 1;
            $data = $customer->getAll($search, $page);
            jsonResponse(true, $data);
        }
        break;
        
    case 'POST':
        $user = requireAdmin();
        $data = json_decode(file_get_contents("php://input"), true);
        
        try {
            $id = $customer->create($data);
            jsonResponse(true, ['id' => $id], 'Customer created successfully');
        } catch (Exception $e) {
            jsonResponse(false, null, $e->getMessage());
        }
        break;
        
    case 'PUT':
        $user = requireAdmin();
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $_GET['id'] ?? 0;
        
        try {
            $customer->update($id, $data);
            jsonResponse(true, null, 'Customer updated successfully');
        } catch (Exception $e) {
            jsonResponse(false, null, $e->getMessage());
        }
        break;
        
    case 'DELETE':
        $user = requireAdmin();
        $id = $_GET['id'] ?? 0;
        
        try {
            $customer->delete($id);
            jsonResponse(true, null, 'Customer deleted successfully');
        } catch (Exception $e) {
            jsonResponse(false, null, $e->getMessage());
        }
        break;
}
?>