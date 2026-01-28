<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';

try {
    $db = Database::getInstance()->getConnection();
    $db->exec("SET NAMES utf8mb4");
    
    $user = authenticate();
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        $defaults = [
            'company_name' => 'My Company',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
            'date_format' => 'Y-m-d',
            'timezone' => 'Asia/Dhaka',
            'invoice_prefix' => 'INV-'
        ];
        
        foreach ($defaults as $key => $value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $value;
            }
        }
        
        echo json_encode(['success' => true, 'data' => $settings], JSON_UNESCAPED_UNICODE);
    }
    
    elseif ($method === 'POST' || $method === 'PUT') {
        if (!in_array($user['role'], ['admin', 'super_admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Admin required']);
            exit;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        $allowed = ['company_name', 'currency', 'currency_symbol', 'date_format', 'timezone', 'invoice_prefix'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $check = $db->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
                $check->execute([$key]);
                
                if ($check->fetchColumn() > 0) {
                    $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                } else {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                    $stmt->execute([$key, $value]);
                }
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Settings saved'], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>