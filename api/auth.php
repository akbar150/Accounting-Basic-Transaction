<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../includes/User.php';

$data = json_decode(file_get_contents("php://input"), true);
$action = $_GET['action'] ?? '';

if ($action === 'login') {
    if (empty($data['email']) || empty($data['password'])) {
        jsonResponse(false, null, 'Email and password required');
    }
    
    $user = new User();
    $result = $user->login($data['email'], $data['password']);
    
    if ($result) {
        $token = JWT::generate([
            'user_id' => $result['id'],
            'email' => $result['email'],
            'role' => $result['role'],
            'name' => $result['name']
        ]);
        jsonResponse(true, [
            'token' => $token, 
            'user' => $result
        ], 'Login successful');
    } else {
        jsonResponse(false, null, 'Invalid email or password');
    }
} 
elseif ($action === 'verify') {
    $headers = getallheaders();
    $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
    
    if (empty($token)) {
        jsonResponse(false, null, 'No token provided');
    }
    
    $payload = JWT::verify($token);
    if ($payload) {
        jsonResponse(true, $payload);
    } else {
        jsonResponse(false, null, 'Invalid token');
    }
}
else {
    jsonResponse(false, null, 'Invalid action');
}
?>