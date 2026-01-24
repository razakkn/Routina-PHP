<?php

namespace Routina\Config;

class Database {
    private static $connection = null;

    public static function getConnection() {
        if (self::$connection === null) {
            $config = require __DIR__ . '/../../config/config.php';
            $driver = isset($config['db_connection']) ? $config['db_connection'] : 'sqlite';
            $host = $config['db_host'] ?? '127.0.0.1';
            $port = (int)($config['db_port'] ?? 0);
            $name = $config['db_name'] ?? 'routina_db';
            $user = $config['db_user'] ?? '';
            $pass = $config['db_pass'] ?? '';
            
            try {
                // Default to SQLite for portability during dev, can switch to MySQL/PostgreSQL via config
                if ($driver === 'sqlite') {
                    $dbPath = __DIR__ . '/../../database.sqlite';
                    self::$connection = new \PDO("sqlite:" . $dbPath);

                    // Parity with ASP.NET setup: enable WAL + improve concurrency.
                    // (Best-effort; some pragmas may no-op depending on environment.)
                    self::$connection->exec('PRAGMA journal_mode=WAL;');
                    self::$connection->exec('PRAGMA synchronous=NORMAL;');
                    self::$connection->exec('PRAGMA foreign_keys=ON;');
                    self::$connection->exec('PRAGMA busy_timeout=5000;');
                } elseif ($driver === 'mysql') {
                    $mysqlPort = $port ?: 3306;
                    $dsn = "mysql:host={$host};port={$mysqlPort};dbname={$name};charset=utf8mb4";
                    self::$connection = new \PDO($dsn, $user, $pass);
                    self::$connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

                } elseif ($driver === 'pgsql') {
                    $pgPort = $port ?: 5432;
                    $dsn = "pgsql:host={$host};port={$pgPort};dbname={$name}";
                    self::$connection = new \PDO($dsn, $user, $pass);
                    self::$connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

                } else {
                    throw new \RuntimeException("Unsupported db_connection '{$driver}'. Use sqlite, mysql, or pgsql.");
                }
                
                self::$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        
        return self::$connection;
    }
}
