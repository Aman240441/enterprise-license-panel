<?php

namespace App\Database;

use App\Config\Env;
use PDO;
use PDOException;
use Exception;

class DatabaseConnection
{
    private static ?PDO $instance = null;
    private static string $driver = 'mysql';

    /**
     * Get Singleton PDO Instance
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../Config/database.php';
            self::$driver = $config['driver'] ?? 'mysql';

            if (self::$driver === 'pgsql') {
                $dsn = sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
                    $config['host'],
                    $config['port'],
                    $config['database'],
                    $config['sslmode'] ?? 'prefer'
                );
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $config['host'],
                    $config['port'],
                    $config['database'],
                    $config['charset']
                );
            }

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                // MySQL database auto-creation fallback
                if (self::$driver === 'mysql' && $e->getCode() == 1049) {
                    self::createDatabaseIfNotExist($config);
                    self::$instance = new PDO(
                        $dsn,
                        $config['username'],
                        $config['password'],
                        $config['options']
                    );
                } else {
                    throw new Exception("Database Connection Failure [" . self::$driver . "]: " . $e->getMessage(), (int)$e->getCode());
                }
            }
        }

        return self::$instance;
    }

    /**
     * Check if active connection driver is PostgreSQL
     */
    public static function isPgsql(): bool
    {
        self::getInstance();
        return self::$driver === 'pgsql';
    }

    /**
     * Get current database driver name ('pgsql' or 'mysql')
     */
    public static function getDriver(): string
    {
        self::getInstance();
        return self::$driver;
    }

    /**
     * Translate MySQL-specific query dialects into PostgreSQL compatible ANSI SQL when using pgsql
     */
    public static function translateQuery(string $sql): string
    {
        if (!self::isPgsql()) {
            return $sql;
        }

        // Replace MySQL backticks with standard quotes / unquoted
        $sql = str_replace('`', '"', $sql);

        // Replace MySQL functions with PostgreSQL equivalents
        $sql = preg_replace('/DATE_FORMAT\s*\(\s*([^,]+)\s*,\s*\'%Y-%m\'\s*\)/i', "TO_CHAR($1, 'YYYY-MM')", $sql);
        $sql = preg_replace('/DATE_FORMAT\s*\(\s*([^,]+)\s*,\s*\'%Y-%m-%d\'\s*\)/i', "TO_CHAR($1, 'YYYY-MM-DD')", $sql);
        $sql = preg_replace('/DATE_SUB\s*\(\s*CURDATE\s*\(\s*\)\s*,\s*INTERVAL\s+(\d+)\s+MONTH\s*\)/i', "CURRENT_DATE - INTERVAL '$1 month'", $sql);
        $sql = preg_replace('/DATE\s*\(\s*([^)]+)\s*\)\s*=\s*CURDATE\s*\(\s*\)/i', "DATE($1) = CURRENT_DATE", $sql);
        $sql = str_replace(['CURDATE()', 'curdate()'], 'CURRENT_DATE', $sql);
        $sql = str_replace(['NOW()', 'now()'], 'CURRENT_TIMESTAMP', $sql);

        // Replace ON DUPLICATE KEY UPDATE with ON CONFLICT DO NOTHING / UPDATE fallback
        if (stripos($sql, 'ON DUPLICATE KEY UPDATE') !== false) {
            $sql = preg_replace('/ON DUPLICATE KEY UPDATE.*/i', 'ON CONFLICT DO NOTHING', $sql);
        }

        return $sql;
    }

    /**
     * Automatically create MySQL database if it doesn't exist yet
     */
    private static function createDatabaseIfNotExist(array $config): void
    {
        try {
            $rawDsn = sprintf(
                'mysql:host=%s;port=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['charset']
            );
            $pdo = new PDO($rawDsn, $config['username'], $config['password']);
            $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $config['database']);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        } catch (PDOException $e) {
            throw new Exception("Failed to auto-create database: " . $e->getMessage());
        }
    }

    /**
     * Execute a SQL query with prepared statement parameters
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $translatedSql = self::translateQuery($sql);
        $stmt = self::getInstance()->prepare($translatedSql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all records
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Fetch a single row
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params)->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Execute INSERT / UPDATE / DELETE query and return affected rows
     */
    public static function execute(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Get last insert ID (supports PostgreSQL sequence fallback)
     */
    public static function lastInsertId(?string $name = null): string
    {
        return self::getInstance()->lastInsertId($name);
    }

    /**
     * Begin Database Transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Commit Database Transaction
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    /**
     * Rollback Database Transaction
     */
    public static function rollBack(): bool
    {
        if (self::getInstance()->inTransaction()) {
            return self::getInstance()->rollBack();
        }
        return false;
    }
}
