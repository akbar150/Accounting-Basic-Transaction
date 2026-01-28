<?php
// config.php
error_reporting(E_ALL);
ini_set('display_errors', 0); // CRITICAL: Don't show errors to browser
ini_set('log_errors', 1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'accounts');
define('DB_USER', 'root');
define('DB_PASS', 'rootpass');
define('DB_CHARSET', 'utf8mb4');

define('JWT_SECRET', 'b12ce8347ea4135656c64ed1127c006ee8ee7ce427a087d4c7164d17761e039af5db9ad9');
define('JWT_EXPIRE', 86400); // 24 hours

define('CURRENCY', '$');
define('DATE_FORMAT', 'Y-m-d');
define('TIMEZONE', 'UTC');

// Paths
define('BASE_URL', 'http://account.mapplate.com' . $_SERVER['HTTP_HOST'] . '/accounting-system');
define('API_URL', BASE_URL . '/api');
define('EXPORT_PATH', __DIR__ . '/exports/');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set(TIMEZONE);

// CORS Headers for API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database Connection Class
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// JWT Helper Class
class JWT {
    public static function generate($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_EXPIRE;
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, JWT_SECRET, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    public static function verify($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;
        
        $signature = hash_hmac('sha256', $parts[0] . "." . $parts[1], JWT_SECRET, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if (!hash_equals($base64Signature, $parts[2])) return false;
        
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
        
        if (!isset($payload['exp']) || $payload['exp'] < time()) return false;
        
        return $payload;
    }
}

// Authentication Middleware
function authenticate() {
    $headers = getallheaders();
    $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
    
    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Access denied. No token provided.']);
        exit();
    }
    
    $payload = JWT::verify($token);
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token.']);
        exit();
    }
    
    return $payload;
}

function requireAdmin() {
    $user = authenticate();
    if (!in_array($user['role'], ['admin', 'super_admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
        exit();
    }
    return $user;
}

// Response Helper
function jsonResponse($success, $data = null, $message = '') {
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit();
}
?>