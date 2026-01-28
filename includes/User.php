<?php
require_once __DIR__ . '/../config.php';

class User {
    private $db;
    private $table = 'users';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ? AND status = 1 LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Update last login
                $update = $this->db->prepare("UPDATE {$this->table} SET last_login = NOW() WHERE id = ?");
                $update->execute([$user['id']]);
                
                unset($user['password']);
                return $user;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT id, name, email, role, status, created_at FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO {$this->table} (name, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'], 
            $data['email'], 
            $hash, 
            $data['role'] ?? 'admin'
        ]);
        return $this->db->lastInsertId();
    }
}
?>