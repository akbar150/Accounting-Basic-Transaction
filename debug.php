<?php
// Run this to check your setup
header('Content-Type: text/plain');

echo "=== ACCOUNTING SYSTEM DEBUG ===\n\n";

// 1. Check PHP version
echo "PHP Version: " . phpversion() . "\n";
if (version_compare(phpversion(), '7.4.0', '<')) {
    echo "❌ ERROR: PHP 7.4+ required\n";
} else {
    echo "✓ PHP version OK\n";
}

// 2. Check extensions
$required = ['pdo', 'pdo_mysql', 'json'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "✓ Extension $ext loaded\n";
    } else {
        echo "❌ ERROR: Extension $ext missing\n";
    }
}

// 3. Check database connection
echo "\n=== DATABASE CHECK ===\n";
try {
    require_once 'config.php';
    $db = Database::getInstance()->getConnection();
    echo "✓ Database connection successful\n";
    
    // Check tables
    $tables = ['users', 'customers', 'transactions', 'transaction_categories'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table $table exists\n";
        } else {
            echo "❌ ERROR: Table $table missing\n";
        }
    }
    
    // Check if admin exists
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE email = 'admin@system.com'");
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        echo "✓ Admin user exists (admin@system.com)\n";
    } else {
        echo "❌ WARNING: Admin user not found. Run setup.php\n";
    }
    
    // Check customers count
    $stmt = $db->query("SELECT COUNT(*) FROM customers WHERE status = 1");
    $customers = $stmt->fetchColumn();
    echo "\n📊 Total active customers: $customers\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

// 4. Check file permissions
echo "\n=== FILE PERMISSIONS ===\n";
$dirs = ['exports', 'includes', 'api'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "✓ Directory $dir exists\n";
        if (is_writable($dir)) {
            echo "  ✓ $dir is writable\n";
        } else {
            echo "  ❌ $dir is not writable\n";
        }
    } else {
        echo "❌ Directory $dir missing\n";
    }
}

echo "\n=== END DEBUG ===\n";
?>