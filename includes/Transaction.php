<?php
if (!class_exists('Database')) {
    require_once __DIR__ . '/../config.php';
}
require_once __DIR__ . '/Customer.php';

class Transaction {
    private $db;
    private $table = 'transactions';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data, $userId) {
        try {
            $referenceId = 'TXN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            $sql = "INSERT INTO {$this->table} 
                    (customer_id, transaction_type, category_id, amount, comments, transaction_date, reference_id, created_by, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['customer_id'],
                $data['transaction_type'],
                $data['category_id'] ?? null,
                $data['amount'],
                $data['comments'] ?? null,
                $data['transaction_date'],
                $referenceId,
                $userId
            ]);
            
            $transactionId = $this->db->lastInsertId();
            
            $customer = new Customer();
            $customer->updateBalance($data['customer_id']);
            
            return $transactionId;
            
        } catch (PDOException $e) {
            throw new Exception("Failed to create transaction: " . $e->getMessage());
        }
    }
    
    public function update($id, $data, $userId) {
        try {
            // Get old transaction to adjust balance
            $old = $this->getById($id);
            if (!$old) {
                throw new Exception("Transaction not found");
            }
            
            $sql = "UPDATE {$this->table} SET 
                    customer_id = ?, 
                    transaction_type = ?, 
                    category_id = ?, 
                    amount = ?, 
                    comments = ?, 
                    transaction_date = ?,
                    updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['customer_id'],
                $data['transaction_type'],
                $data['category_id'] ?? null,
                $data['amount'],
                $data['comments'] ?? null,
                $data['transaction_date'],
                $id
            ]);
            
            // Update balances for both old and new customer if changed
            $customer = new Customer();
            if ($old['customer_id'] != $data['customer_id']) {
                $customer->updateBalance($old['customer_id']);
            }
            $customer->updateBalance($data['customer_id']);
            
            return true;
            
        } catch (PDOException $e) {
            throw new Exception("Failed to update transaction: " . $e->getMessage());
        }
    }
    
    public function getById($id) {
        $sql = "SELECT t.*, c.name as customer_name, c.customer_code, cat.category_name 
                FROM {$this->table} t
                JOIN customers c ON t.customer_id = c.id
                LEFT JOIN transaction_categories cat ON t.category_id = cat.id
                WHERE t.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getFiltered($filters = [], $page = 1, $limit = 50) {
        try {
            $where = ["1=1"];
            $params = [];
            
            if (!empty($filters['customer_id'])) {
                $where[] = "t.customer_id = ?";
                $params[] = $filters['customer_id'];
            }
            
            if (!empty($filters['transaction_type'])) {
                $where[] = "t.transaction_type = ?";
                $params[] = $filters['transaction_type'];
            }
            
            if (!empty($filters['date_from'])) {
                $where[] = "t.transaction_date >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where[] = "t.transaction_date <= ?";
                $params[] = $filters['date_to'];
            }
            
            $whereClause = implode(' AND ', $where);
            
            $countSql = "SELECT COUNT(*) FROM {$this->table} t WHERE $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            
            $offset = ($page - 1) * $limit;
            $sql = "SELECT t.*, c.name as customer_name, c.customer_code, 
                           cat.category_name, u.name as created_by_name
                    FROM {$this->table} t
                    JOIN customers c ON t.customer_id = c.id
                    LEFT JOIN transaction_categories cat ON t.category_id = cat.id
                    JOIN users u ON t.created_by = u.id
                    WHERE $whereClause
                    ORDER BY t.transaction_date DESC, t.id DESC
                    LIMIT ? OFFSET ?";
            
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $transactions = $stmt->fetchAll();
            
            return [
                'data' => $transactions,
                'total' => (int)$total,
                'pages' => (int)ceil($total / $limit),
                'current_page' => (int)$page
            ];
            
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch transactions: " . $e->getMessage());
        }
    }
    
    public function delete($id) {
        try {
            $trans = $this->getById($id);
            if (!$trans) {
                throw new Exception("Transaction not found");
            }
            
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $customer = new Customer();
                $customer->updateBalance($trans['customer_id']);
            }
            
            return $result;
        } catch (PDOException $e) {
            throw new Exception("Failed to delete transaction: " . $e->getMessage());
        }
    }
}
?>