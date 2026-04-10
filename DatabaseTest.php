<?php
// ============================================================
//  Unit Tests — Database.php
//  Tests: singleton, fetchOne, fetchAll, insert, execute
//  Requires: cv_builder_pro_test database to exist
//  Run: ./vendor/bin/phpunit tests/unit/DatabaseTest.php --testdox
// ============================================================

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private static PDO $pdo;

    // ── Class-level setup (runs once before all tests) ───────

    public static function setUpBeforeClass(): void
    {
        // Create test table used across all DB tests
        self::$pdo = Database::getInstance();
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS `_test_users` (
                `id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name`  VARCHAR(100) NOT NULL,
                `email` VARCHAR(100) NOT NULL UNIQUE,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public static function tearDownAfterClass(): void
    {
        Database::getInstance()->exec("DROP TABLE IF EXISTS `_test_users`");
    }

    protected function setUp(): void
    {
        // Clean slate before every test
        Database::getInstance()->exec("TRUNCATE TABLE `_test_users`");
    }

    // ── Singleton Tests ──────────────────────────────────────

    public function test_getInstance_returns_pdo_instance(): void
    {
        $db = Database::getInstance();
        $this->assertInstanceOf(PDO::class, $db);
    }

    public function test_getInstance_returns_same_instance_on_multiple_calls(): void
    {
        $db1 = Database::getInstance();
        $db2 = Database::getInstance();
        $this->assertSame($db1, $db2);
    }

    // ── Insert Tests ─────────────────────────────────────────

    public function test_insert_returns_last_insert_id(): void
    {
        $id = Database::insert(
            "INSERT INTO `_test_users` (`name`, `email`) VALUES (?, ?)",
            ['Ahmed Abubakr', 'ahmed@test.com']
        );

        $this->assertIsString($id);
        $this->assertGreaterThan(0, (int)$id);
    }

    public function test_insert_persists_data(): void
    {
        Database::insert(
            "INSERT INTO `_test_users` (`name`, `email`) VALUES (?, ?)",
            ['Test User', 'test@example.com']
        );

        $count = Database::getInstance()
            ->query("SELECT COUNT(*) FROM `_test_users`")
            ->fetchColumn();

        $this->assertEquals(1, $count);
    }

    // ── fetchOne Tests ───────────────────────────────────────

    public function test_fetchOne_returns_correct_row(): void
    {
        Database::insert(
            "INSERT INTO `_test_users` (`name`, `email`) VALUES (?, ?)",
            ['Ahmed', 'a@b.com']
        );

        $row = Database::fetchOne(
            "SELECT * FROM `_test_users` WHERE `email` = ?",
            ['a@b.com']
        );

        $this->assertIsArray($row);
        $this->assertEquals('Ahmed', $row['name']);
        $this->assertEquals('a@b.com', $row['email']);
    }

    public function test_fetchOne_returns_false_when_no_match(): void
    {
        $row = Database::fetchOne(
            "SELECT * FROM `_test_users` WHERE `email` = ?",
            ['nonexistent@test.com']
        );

        $this->assertFalse($row);
    }

    // ── fetchAll Tests ───────────────────────────────────────

    public function test_fetchAll_returns_all_rows(): void
    {
        Database::insert("INSERT INTO `_test_users` (`name`, `email`) VALUES (?, ?)", ['User 1', 'u1@test.com']);
        Database::insert("INSERT INTO `_test_users` (`name`, `email`) VALUES (?, ?)", ['User 2', 'u2@test.com']);
        Database::insert("INSERT INTO `_test_users` (`name`, `email`) VALUES (?, ?)", ['User 3', 'u3@test.com']);

        $rows = Database::fetchAll("SELECT * FROM `_test_users` ORDER BY `id`");

        $this->assertIsArray($rows);
        $this->assertCount(3, $rows);
        $this->assertEquals('User 1', $rows[0]['name']);
        $this->assertEquals('User 3', $rows[2]['name']);
    }

    public function test_fetchAll_returns_empty_array_when_no_rows(): void
    {
        $rows = Database::fetchAll("SELECT * FROM `_test_users`");
        $this->assertIsArray($rows);
        $this->assertEmpty($rows);
    }

    // ── execute Tests ────────────────────────────────────────

    public function test_execute_updates_row_and_returns_affected_count(): void
    {
        Database::insert("INSERT INTO `_test_users` (`name`, `email`) VALUES (?, ?)", ['Old Name', 'old@test.com']);

        $affected = Database::execute(
            "UPDATE `_test_users` SET `name` = ? WHERE `email` = ?",
            ['New Name', 'old@test.com']
        );

        $this->assertEquals(1, $affected);

        $row = Database::fetchOne("SELECT * FROM `_test_users` WHERE `email` = ?", ['old@test.com']);
        $this->assertEquals('New Name', $row['name']);
    }

    public function test_execute_deletes_row_and_returns_affected_count(): void
    {
        Database::insert("INSERT INTO `_test_users` (`name`, `email`) VALUES (?, ?)", ['Delete Me', 'd@test.com']);

        $affected = Database::execute(
            "DELETE FROM `_test_users` WHERE `email` = ?",
            ['d@test.com']
        );

        $this->assertEquals(1, $affected);

        $row = Database::fetchOne("SELECT * FROM `_test_users` WHERE `email` = ?", ['d@test.com']);
        $this->assertFalse($row);
    }

    public function test_execute_returns_zero_when_nothing_matched(): void
    {
        $affected = Database::execute(
            "DELETE FROM `_test_users` WHERE `email` = ?",
            ['nobody@nowhere.com']
        );

        $this->assertEquals(0, $affected);
    }

    // ── Prepared statement / injection safety ────────────────

    public function test_prepared_statements_prevent_sql_injection(): void
    {
        Database::insert(
            "INSERT INTO `_test_users` (`name`, `email`) VALUES (?, ?)",
            ['Legit User', 'legit@test.com']
        );

        // Attempt injection via parameter — should return false, not all rows
        $row = Database::fetchOne(
            "SELECT * FROM `_test_users` WHERE `email` = ?",
            ["' OR '1'='1"]
        );

        $this->assertFalse($row);
    }

    // ── UTF-8 / Arabic text handling ─────────────────────────

    public function test_database_stores_and_retrieves_arabic_text(): void
    {
        Database::insert(
            "INSERT INTO `_test_users` (`name`, `email`) VALUES (?, ?)",
            ['أحمد محمد أبوبكر', 'arabic@test.com']
        );

        $row = Database::fetchOne(
            "SELECT * FROM `_test_users` WHERE `email` = ?",
            ['arabic@test.com']
        );

        $this->assertIsArray($row);
        $this->assertEquals('أحمد محمد أبوبكر', $row['name']);
    }
}
