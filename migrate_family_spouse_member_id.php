<?php
// Minimal migration: add spouse_member_id column to family_members
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

echo "Starting migration: family_members.spouse_member_id\n";

require_once __DIR__ . '/src/Config/Database.php';

// Minimal autoloader for this script
spl_autoload_register(function ($class) {
    $prefix = 'Routina\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

try {
    $config = require __DIR__ . '/config/config.php';
    $driver = $config['db_connection'] ?? 'sqlite';
    echo "Driver: {$driver}\n";

    $db = \Routina\Config\Database::getConnection();

    if ($driver === 'mysql') {
        try {
            $db->exec("ALTER TABLE family_members ADD COLUMN spouse_member_id BIGINT NULL");
            echo "OK: column added (mysql)\n";
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'Duplicate column') !== false || stripos($msg, 'already exists') !== false) {
                echo "OK: column already exists (mysql)\n";
            } else {
                throw $e;
            }
        }
    } elseif ($driver === 'sqlite') {
        try {
            $db->exec("ALTER TABLE family_members ADD COLUMN spouse_member_id INTEGER");
            echo "OK: column added (sqlite)\n";
        } catch (PDOException $e) {
            $msg = strtolower($e->getMessage());
            if (strpos($msg, 'duplicate column') !== false || strpos($msg, 'already exists') !== false) {
                echo "OK: column already exists (sqlite)\n";
            } else {
                throw $e;
            }
        }
    } elseif ($driver === 'pgsql') {
        $db->exec("ALTER TABLE family_members ADD COLUMN IF NOT EXISTS spouse_member_id BIGINT");
        echo "OK: ensured column exists (pgsql)\n";
    } else {
        echo "Unsupported driver for this migration: {$driver}\n";
        exit(1);
    }

    echo "Done.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "ERROR: " . $e->getMessage() . "\n";
}
