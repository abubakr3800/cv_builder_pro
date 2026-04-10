<?php
// ============================================================
//  Unit Tests — helpers.php
//  Tests: CSRF, auth session, flash messages, sanitization
//  Run: ./vendor/bin/phpunit tests/unit/HelpersTest.php --testdox
// ============================================================

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    // ── Setup/Teardown ───────────────────────────────────────

    protected function setUp(): void
    {
        // Start a clean session before each test
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
    }

    protected function tearDown(): void
    {
        session_unset();
    }

    // ── CSRF Tests ───────────────────────────────────────────

    public function test_csrf_token_is_generated_when_missing(): void
    {
        unset($_SESSION[CSRF_TOKEN_NAME]);
        $token = csrf_token();

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    public function test_csrf_token_is_reused_on_second_call(): void
    {
        $token1 = csrf_token();
        $token2 = csrf_token();

        $this->assertEquals($token1, $token2);
    }

    public function test_csrf_field_contains_hidden_input(): void
    {
        $field = csrf_field();

        $this->assertStringContainsString('<input type="hidden"', $field);
        $this->assertStringContainsString(CSRF_TOKEN_NAME, $field);
        $this->assertStringContainsString(csrf_token(), $field);
    }

    public function test_csrf_verify_passes_with_valid_token(): void
    {
        $token = csrf_token();
        $_POST[CSRF_TOKEN_NAME] = $token;

        $this->assertTrue(csrf_verify());

        unset($_POST[CSRF_TOKEN_NAME]);
    }

    public function test_csrf_verify_fails_with_wrong_token(): void
    {
        csrf_token(); // ensure session token exists
        $_POST[CSRF_TOKEN_NAME] = 'wrong_token_here';

        $this->assertFalse(csrf_verify());

        unset($_POST[CSRF_TOKEN_NAME]);
    }

    public function test_csrf_verify_fails_with_empty_post(): void
    {
        csrf_token();
        unset($_POST[CSRF_TOKEN_NAME]);

        $this->assertFalse(csrf_verify());
    }

    // ── Auth Tests ───────────────────────────────────────────

    public function test_auth_check_returns_false_when_no_session(): void
    {
        unset($_SESSION['user']);
        $this->assertFalse(auth_check());
    }

    public function test_auth_check_returns_true_after_login(): void
    {
        $fakeUser = ['id' => 1, 'name' => 'Test', 'email' => 't@t.com', 'role' => 'user', 'avatar' => null];
        $_SESSION['user'] = $fakeUser;

        $this->assertTrue(auth_check());
    }

    public function test_auth_user_returns_false_when_not_logged_in(): void
    {
        unset($_SESSION['user']);
        $this->assertFalse(auth_user());
    }

    public function test_auth_user_returns_session_data_when_logged_in(): void
    {
        $fakeUser = ['id' => 5, 'name' => 'Ahmed', 'email' => 'a@b.com', 'role' => 'user', 'avatar' => null];
        $_SESSION['user'] = $fakeUser;

        $user = auth_user();
        $this->assertIsArray($user);
        $this->assertEquals(5, $user['id']);
        $this->assertEquals('Ahmed', $user['name']);
    }

    public function test_auth_admin_returns_false_for_regular_user(): void
    {
        $_SESSION['user'] = ['id' => 2, 'name' => 'User', 'email' => 'u@u.com', 'role' => 'user', 'avatar' => null];
        $this->assertFalse(auth_admin());
    }

    public function test_auth_admin_returns_true_for_admin_user(): void
    {
        $_SESSION['user'] = ['id' => 1, 'name' => 'Admin', 'email' => 'a@a.com', 'role' => 'admin', 'avatar' => null];
        $this->assertTrue(auth_admin());
    }

    public function test_auth_admin_returns_false_when_not_logged_in(): void
    {
        unset($_SESSION['user']);
        $this->assertFalse(auth_admin());
    }

    public function test_auth_login_sets_session_correctly(): void
    {
        $user = ['id' => 7, 'name' => 'Ahmed Abubakr', 'email' => 'ahmed@test.com', 'role' => 'user', 'avatar' => null];
        auth_login($user);

        $this->assertTrue(auth_check());
        $this->assertEquals(7, $_SESSION['user']['id']);
        $this->assertEquals('Ahmed Abubakr', $_SESSION['user']['name']);
        $this->assertEquals('user', $_SESSION['user']['role']);
    }

    public function test_auth_login_does_not_store_password_in_session(): void
    {
        $user = ['id' => 3, 'name' => 'Test', 'email' => 't@t.com', 'role' => 'user', 'avatar' => null, 'password' => 'secret'];
        auth_login($user);

        $this->assertArrayNotHasKey('password', $_SESSION['user']);
    }

    // ── Flash Message Tests ──────────────────────────────────

    public function test_flash_stores_message_in_session(): void
    {
        flash('success_msg', 'Saved successfully.', 'success');

        $this->assertArrayHasKey('success_msg', $_SESSION['flash']);
        $this->assertEquals('Saved successfully.', $_SESSION['flash']['success_msg']['message']);
        $this->assertEquals('success', $_SESSION['flash']['success_msg']['type']);
    }

    public function test_flash_get_returns_message_and_removes_it(): void
    {
        flash('info_msg', 'Profile updated.', 'info');
        $msg = flash_get('info_msg');

        $this->assertIsArray($msg);
        $this->assertEquals('Profile updated.', $msg['message']);
        $this->assertEquals('info', $msg['type']);
        $this->assertArrayNotHasKey('info_msg', $_SESSION['flash'] ?? []);
    }

    public function test_flash_get_returns_null_if_key_not_set(): void
    {
        $msg = flash_get('nonexistent_key');
        $this->assertNull($msg);
    }

    public function test_flash_render_returns_html_and_clears_all(): void
    {
        flash('k1', 'Message one', 'success');
        flash('k2', 'Message two', 'error');

        $html = flash_render();

        $this->assertStringContainsString('Message one', $html);
        $this->assertStringContainsString('Message two', $html);
        $this->assertStringContainsString('cvb-toast', $html);
        $this->assertEmpty($_SESSION['flash'] ?? []);
    }

    // ── Sanitization Tests ───────────────────────────────────

    public function test_clean_strips_html_tags_intent(): void
    {
        $result = clean('<script>alert("xss")</script>');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function test_clean_trims_whitespace(): void
    {
        $result = clean('  hello world  ');
        $this->assertEquals('hello world', $result);
    }

    public function test_clean_encodes_quotes(): void
    {
        $result = clean("it's a \"test\"");
        $this->assertStringContainsString('&quot;', $result);
        $this->assertStringContainsString('&#039;', $result);
    }

    public function test_clean_handles_empty_string(): void
    {
        $this->assertEquals('', clean(''));
    }

    public function test_clean_handles_arabic_text_intact(): void
    {
        $arabic = 'أحمد محمد أبوبكر';
        $result = clean($arabic);
        $this->assertEquals($arabic, $result);
    }

    public function test_post_helper_returns_default_when_key_missing(): void
    {
        unset($_POST['nonexistent']);
        $result = post('nonexistent', 'fallback');
        $this->assertEquals('fallback', $result);
    }

    public function test_post_helper_sanitizes_existing_value(): void
    {
        $_POST['field'] = '  <b>test</b>  ';
        $result = post('field');
        $this->assertStringContainsString('&lt;b&gt;', $result);
        unset($_POST['field']);
    }
}
