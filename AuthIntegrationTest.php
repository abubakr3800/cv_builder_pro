<?php
// ============================================================
//  Integration Tests — Auth Flow
//  Tests: register, login, duplicate email, wrong password
//  Requires: cv_builder_pro_test database
//  Run: ./vendor/bin/phpunit tests/integration/AuthIntegrationTest.php --testdox
// ============================================================

use PHPUnit\Framework\TestCase;

// ── Minimal auth functions (mirrors auth/register.php logic) ─
// These will be extracted to includes/auth_service.php in Phase 2.
// Defined here so integration tests are self-contained.

function auth_register_user(string $name, string $email, string $password): array
{
    $name     = trim($name);
    $email    = strtolower(trim($email));
    $password = trim($password);

    if (empty($name) || strlen($name) < 2) {
        return ['success' => false, 'message' => 'Name must be at least 2 characters.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address.'];
    }
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
    }

    $existing = Database::fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existing) {
        return ['success' => false, 'message' => 'Email already registered.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $id   = Database::insert(
        "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')",
        [$name, $email, $hash]
    );

    return ['success' => true, 'message' => 'Registered.', 'user_id' => (int)$id];
}

function auth_login_user(string $email, string $password): array
{
    $email = strtolower(trim($email));

    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email and password are required.'];
    }

    $user = Database::fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid credentials.'];
    }
    if (!(bool)$user['is_active']) {
        return ['success' => false, 'message' => 'Account is disabled.'];
    }
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid credentials.'];
    }

    return ['success' => true, 'message' => 'Login successful.', 'user' => $user];
}

class AuthIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        Database::execute("DELETE FROM users WHERE email LIKE '%@integrationtest.com'");
    }

    protected function tearDown(): void
    {
        Database::execute("DELETE FROM users WHERE email LIKE '%@integrationtest.com'");
    }

    // ── Registration Tests ───────────────────────────────────

    public function test_register_new_user_succeeds(): void
    {
        $result = auth_register_user('Ahmed Abubakr', 'ahmed@integrationtest.com', 'SecurePass123');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertGreaterThan(0, $result['user_id']);
    }

    public function test_register_persists_user_to_database(): void
    {
        auth_register_user('New User', 'newuser@integrationtest.com', 'Pass1234!');

        $user = Database::fetchOne("SELECT * FROM users WHERE email = ?", ['newuser@integrationtest.com']);

        $this->assertIsArray($user);
        $this->assertEquals('New User', $user['name']);
        $this->assertEquals('user', $user['role']);
        $this->assertEquals(1, $user['is_active']);
    }

    public function test_register_hashes_password(): void
    {
        auth_register_user('Hash Test', 'hash@integrationtest.com', 'PlainPassword1');

        $user = Database::fetchOne("SELECT password FROM users WHERE email = ?", ['hash@integrationtest.com']);

        $this->assertNotEquals('PlainPassword1', $user['password']);
        $this->assertTrue(password_verify('PlainPassword1', $user['password']));
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        auth_register_user('First User', 'dup@integrationtest.com', 'Password1!');
        $result = auth_register_user('Second User', 'dup@integrationtest.com', 'Password2!');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already registered', $result['message']);
    }

    public function test_register_fails_with_invalid_email(): void
    {
        $result = auth_register_user('Test', 'not-an-email', 'Password1!');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('email', strtolower($result['message']));
    }

    public function test_register_fails_with_short_password(): void
    {
        $result = auth_register_user('Test', 'short@integrationtest.com', '1234');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('8 characters', $result['message']);
    }

    public function test_register_fails_with_short_name(): void
    {
        $result = auth_register_user('A', 'short@integrationtest.com', 'Password1!');
        $this->assertFalse($result['success']);
    }

    // ── Login Tests ──────────────────────────────────────────

    public function test_login_succeeds_with_correct_credentials(): void
    {
        auth_register_user('Login Test', 'login@integrationtest.com', 'MyPassword99');
        $result = auth_login_user('login@integrationtest.com', 'MyPassword99');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('Login Test', $result['user']['name']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        auth_register_user('Wrong Pass', 'wrongpass@integrationtest.com', 'CorrectPass1');
        $result = auth_login_user('wrongpass@integrationtest.com', 'WrongPass1');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid', $result['message']);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $result = auth_login_user('ghost@integrationtest.com', 'AnyPassword1');
        $this->assertFalse($result['success']);
    }

    public function test_login_fails_with_empty_credentials(): void
    {
        $result = auth_login_user('', '');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('required', $result['message']);
    }

    public function test_login_is_case_insensitive_for_email(): void
    {
        auth_register_user('Case Test', 'case@integrationtest.com', 'Password12!');
        $result = auth_login_user('CASE@INTEGRATIONTEST.COM', 'Password12!');

        $this->assertTrue($result['success']);
    }

    public function test_login_fails_for_disabled_account(): void
    {
        auth_register_user('Disabled', 'disabled@integrationtest.com', 'Password12!');
        Database::execute("UPDATE users SET is_active = 0 WHERE email = ?", ['disabled@integrationtest.com']);

        $result = auth_login_user('disabled@integrationtest.com', 'Password12!');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('disabled', strtolower($result['message']));
    }

    public function test_login_does_not_expose_password_hash_in_result(): void
    {
        auth_register_user('Sec Test', 'sec@integrationtest.com', 'Password12!');
        $result = auth_login_user('sec@integrationtest.com', 'Password12!');

        // Password hash should not leak into application layer directly,
        // but it IS on the $user array here; auth_login() in helpers.php strips it.
        // Test that helpers.php auth_login() does not store it in session.
        auth_login($result['user']);
        $this->assertArrayNotHasKey('password', $_SESSION['user']);
    }
}
