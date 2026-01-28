<?php
// Run this file once to initialize the system
require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if admin exists
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Create default admin
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Super Administrator', 'admin@system.com', $hash, 'super_admin']);
        echo "✓ Default admin created: admin@system.com / admin123\n";
    } else {
        echo "✓ Admin user already exists\n";
    }
    
    // Check directories
    if (!file_exists('exports')) {
        mkdir('exports', 0755, true);
        echo "✓ Exports directory created\n";
    }
    
    if (!file_exists('exports/csv')) {
        mkdir('exports/csv', 0755, true);
    }
    
    echo "\nSystem ready! Delete this file after setup.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>