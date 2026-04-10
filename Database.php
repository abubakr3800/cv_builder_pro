<?php
// ============================================================
//  CV Builder Pro — Database Connection (PDO Singleton)
// ============================================================

require_once __DIR__ . '/config.php';

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST, DB_NAME, DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                if (APP_ENV === 'development') {
                    die('<pre style="color:red">DB Connection Error: ' . $e->getMessage() . '</pre>');
                } else {
                    die('A database error occurred. Please try again later.');
                }
            }
        }

        return self::$instance;
    }

    /** Convenience: run a prepared query and return the statement */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch a single row */
    public static function fetchOne(string $sql, array $params = []): array|false
    {
        return self::query($sql, $params)->fetch();
    }

    /** Fetch all rows */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /** Insert and return last insert ID */
    public static function insert(string $sql, array $params = []): string|false
    {
        self::query($sql, $params);
        return self::getInstance()->lastInsertId();
    }

    /** Run update/delete and return affected row count */
    public static function execute(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }
}
