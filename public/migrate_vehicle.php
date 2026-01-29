<?php
// Quick migration to add disposal_remarks column
require_once __DIR__ . '/../src/Config/Database.php';

use Routina\Config\Database;

echo "<pre>";
try {
    $db = Database::getConnection();
    
    // Add disposal_remarks column if not exists (MySQL)
    echo "Adding disposal_remarks column to vehicles table...\n";
    
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM vehicles LIKE 'disposal_remarks'");
    if ($stmt->rowCount() === 0) {
        $db->exec("ALTER TABLE vehicles ADD COLUMN disposal_remarks TEXT");
        echo "Column added successfully!\n";
    } else {
        echo "Column already exists.\n";
    }
    
    echo "\nMigration complete! You can delete this file now.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "</pre>";
