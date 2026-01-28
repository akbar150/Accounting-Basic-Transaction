<?php
// includes/Customer.php

// Ensure Database class is available
if (!class_exists('Database')) {
    require_once __DIR__ . '/../config.php';
}

class Customer {
    private $db;
    private $table = 'customers';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create new customer
     */
    public function create($data) {
        try {
            // Generate unique customer code
            $code = $this->generateCustomerCode();
            
            $sql = "INSERT INTO {$this->table} 
                    (customer_code, name, phone, email, address, opening_balance, current_balance, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $openingBalance = $data['opening_balance'] ?? 0;
            
            $stmt->execute([
                $code,
                $data['name'],
                $data['phone'] ?? null,
                $data['email'] ?? null,
                $data['address'] ?? null,
                $openingBalance,
                $openingBalance
            ]);
            
            return [
                'id' => $this->db->lastInsertId(),
                'customer_code' => $code
            ];
            
        } catch (PDOException $e) {
            throw new Exception("Failed to create customer: " . $e->getMessage());
        }
    }
    
    /**
     * Update customer details
     */
    public function update($id, $data) {
        try {
            $sql = "UPDATE {$this->table} SET 
                    name = ?, 
                    phone = ?, 
                    email = ?, 
                    address = ?,
                    updated_at = NOW()
                    WHERE id = ?";
                    
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['name'], 
                $data['phone'] ?? null, 
                $data['email'] ?? null, 
                $data['address'] ?? null, 
                $id
            ]);
        } catch (PDOException $e) {
            throw new Exception("Failed to update customer: " . $e->getMessage());
        }
    }
    
    /**
     * Delete customer (only if no transactions)
     */
    public function delete($id) {
        try {
            // Check if customer has transactions
            $check = $this->db->prepare("SELECT COUNT(*) FROM transactions WHERE customer_id = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                throw new Exception("Cannot delete customer with existing transactions. Please deactivate instead.");
            }
            
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            throw new Exception("Failed to delete customer: " . $e->getMessage());
        }
    }
    
    /**
     * Get single customer by ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT c.*, 
                    COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) as total_debit,
                    COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0) as total_credit
                    FROM {$this->table} c
                    LEFT JOIN transactions t ON c.id = t.customer_id
                    WHERE c.id = ? AND c.status = 1
                    GROUP BY c.id";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch customer: " . $e->getMessage());
        }
    }
    
    /**
     * Get customer by code
     */
    public function getByCode($code) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE customer_code = ? AND status = 1");
            $stmt->execute([$code]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch customer: " . $e->getMessage());
        }
    }
    
    /**
     * Get all customers with pagination and search
     */
    public function getAll($search = '', $page = 1, $limit = 20) {
        try {
            $offset = ($page - 1) * $limit;
            $params = [];
            
            $where = "WHERE status = 1";
            if (!empty($search)) {
                $where .= " AND (name LIKE ? OR customer_code LIKE ? OR phone LIKE ? OR email LIKE ?)";
                $searchTerm = "%$search%";
                $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
            }
            
            // Get total count
            $countSql = "SELECT COUNT(*) FROM {$this->table} $where";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            
            // Get data with calculated balance
            $sql = "SELECT c.*, 
                    COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) as total_debit,
                    COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0) as total_credit
                    FROM {$this->table} c
                    LEFT JOIN transactions t ON c.id = t.customer_id
                    $where
                    GROUP BY c.id
                    ORDER BY c.name ASC
                    LIMIT ? OFFSET ?";
            
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            return [
                'data' => $data,
                'total' => (int)$total,
                'pages' => (int)ceil($total / $limit),
                'current_page' => (int)$page
            ];
            
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch customers: " . $e->getMessage());
        }
    }
    
    /**
     * Get all customers for dropdown (minimal data)
     */
    public function getAllForDropdown() {
        try {
            $sql = "SELECT id, customer_code, name, current_balance 
                    FROM {$this->table} 
                    WHERE status = 1 
                    ORDER BY name ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch customers: " . $e->getMessage());
        }
    }
    
    /**
     * Calculate customer balance from opening + transactions
     */
    public function getBalance($customerId) {
        try {
            $sql = "SELECT 
                        c.opening_balance,
                        COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) as total_debit,
                        COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0) as total_credit
                    FROM {$this->table} c
                    LEFT JOIN transactions t ON c.id = t.customer_id
                    WHERE c.id = ?
                    GROUP BY c.id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$customerId]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return 0;
            }
            
            // Balance = Opening + Debit - Credit
            // Positive = Customer owes you ( Debit > Credit )
            // Negative = You owe customer ( Credit > Debit )
            return $result['opening_balance'] + $result['total_debit'] - $result['total_credit'];
            
        } catch (PDOException $e) {
            throw new Exception("Failed to calculate balance: " . $e->getMessage());
        }
    }
    
    /**
     * Update current_balance field in database
     */
    public function updateBalance($customerId) {
        try {
            $newBalance = $this->getBalance($customerId);
            $stmt = $this->db->prepare("UPDATE {$this->table} SET current_balance = ? WHERE id = ?");
            return $stmt->execute([$newBalance, $customerId]);
        } catch (PDOException $e) {
            throw new Exception("Failed to update balance: " . $e->getMessage());
        }
    }
    
    /**
     * Get customers with negative balance (overpayment)
     */
    public function getNegativeBalanceCustomers() {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE current_balance < 0 AND status = 1 
                    ORDER BY current_balance ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch customers: " . $e->getMessage());
        }
    }
    
    /**
     * Generate unique customer code
     * Format: CUST[YEAR][RANDOM]
     */
    private function generateCustomerCode() {
        $prefix = 'CUST';
        $year = date('Y');
        $random = strtoupper(substr(uniqid(), -4));
        $code = $prefix . $year . $random;
        
        // Check if code exists, if so regenerate
        $check = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE customer_code = ?");
        $check->execute([$code]);
        if ($check->fetchColumn() > 0) {
            return $this->generateCustomerCode();
        }
        
        return $code;
    }
}
?>