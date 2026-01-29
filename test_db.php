<?php
// Minimal database test script
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

echo "=== Database Connection Test ===\n\n";

echo "Step 1: Loading config...\n";
try {
    $config = require __DIR__ . '/config/config.php';
    echo "Config loaded successfully.\n";
    echo "Driver: " . ($config['db_connection'] ?? 'not set') . "\n";
    echo "Host: " . ($config['db_host'] ?? 'not set') . "\n";
    echo "Database: " . ($config['db_database'] ?? 'not set') . "\n\n";
} catch (Throwable $e) {
    echo "ERROR loading config: " . $e->getMessage() . "\n";
    exit;
}

echo "Step 2: Connecting to database...\n";
try {
    require_once __DIR__ . '/src/Config/Database.php';
    $db = \Routina\Config\Database::getConnection();
    echo "Connection successful!\n\n";
} catch (Throwable $e) {
    echo "ERROR connecting: " . $e->getMessage() . "\n";
    exit;
}

echo "Step 3: Testing query...\n";
try {
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "Query test: " . ($result['test'] ?? 'failed') . "\n\n";
} catch (Throwable $e) {
    echo "ERROR querying: " . $e->getMessage() . "\n";
    exit;
}

echo "Step 4: Checking family_members table...\n";
try {
    $stmt = $db->query("DESCRIBE family_members");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in family_members:\n";
    foreach ($columns as $col) {
        echo "  - $col\n";
    }
    
    // Check if spouse_member_id exists
    $stmt = $db->query("SHOW COLUMNS FROM family_members LIKE 'spouse_member_id'");
    $exists = $stmt->fetch();
    echo "\nspouse_member_id column: " . ($exists ? "EXISTS" : "MISSING") . "\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
