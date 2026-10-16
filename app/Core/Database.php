<?php
/**
 * Database Connection & Query Wrapper
 * 
 * Implements PDO Singleton pattern with prepared statements,
 * transaction management, and connection fallback.
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use Exception;

class Database {
    private static ?PDO $instance = null;
    private static string $connectionType = 'mysql';

    private function __construct() {}
    private function __clone() {}

    /**
     * Get or initialize the PDO connection instance
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    /**
     * Establish the database connection based on config
     */
    public static function connect(?string $customDriver = null): PDO {
        $config = require CONFIG_PATH . '/database.php';
        $driver = $customDriver ?? $config['default'] ?? 'mysql';
        self::$connectionType = $driver;

        $conn = $config['connections'][$driver] ?? null;
        if (!$conn) {
            throw new Exception("Database configuration for driver [{$driver}] not found.");
        }

        try {
            if ($driver === 'sqlite') {
                $dir = dirname($conn['database']);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                $dsn = "sqlite:" . $conn['database'];
                self::$instance = new PDO($dsn, null, null, $conn['options'] ?? []);
                // Enable foreign keys for SQLite
                self::$instance->exec('PRAGMA foreign_keys = ON;');
            } else {
                $dsn = sprintf(
                    "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                    $conn['host'],
                    $conn['port'],
                    $conn['database'],
                    $conn['charset'] ?? 'utf8mb4'
                );
                self::$instance = new PDO($dsn, $conn['username'], $conn['password'], $conn['options'] ?? []);
            }
        } catch (PDOException $e) {
            // If MySQL is not reachable and in local testing, gracefully fallback to SQLite if configured
            if ($driver === 'mysql' && isset($config['connections']['sqlite'])) {
                return self::connect('sqlite');
            }
            throw new Exception("Database connection error: " . $e->getMessage());
        }

        return self::$instance;
    }

    /**
     * Reset the active connection
     */
    public static function resetConnection(): void {
        self::$instance = null;
    }

    /**
     * Execute a prepared SELECT statement and fetch all rows
     */
    public static function query(string $sql, array $params = []): array {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute a prepared SELECT statement and fetch a single row
     */
    public static function queryOne(string $sql, array $params = []): ?array {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Execute an INSERT, UPDATE, or DELETE query and return affected rows
     */
    public static function execute(string $sql, array $params = []): int {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Get the last inserted row ID
     */
    public static function lastInsertId(): string {
        return self::getConnection()->lastInsertId();
    }

    /**
     * Run a callback within an ACID database transaction (supports nesting)
     */
    public static function transaction(callable $callback): mixed {
        $pdo = self::getConnection();
        $isRootTransaction = false;

        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $isRootTransaction = true;
        }

        try {
            $result = $callback($pdo);
            if ($isRootTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }
            return $result;
        } catch (Exception $e) {
            if ($isRootTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get current driver type
     */
    public static function getDriver(): string {
        return self::$connectionType;
    }
}
