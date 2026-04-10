# CV Builder Pro — Unit Testing Guide

**Author:** Ahmed Mohamed Abubakr  
**Site:** https://abubakr.rf.gd/  

---

## Overview

This project uses **PHPUnit 10** for both unit and integration testing.
Tests live in `/tests/` and are split into two suites:

| Suite | Path | What it tests |
|---|---|---|
| Unit | `tests/unit/` | Pure logic — no DB, no HTTP |
| Integration | `tests/integration/` | Real DB queries, full flows |

---

## 1. First-time Setup

### Step 1 — Install dependencies

```bash
# From the project root (cv-builder-pro/)
composer install
```

### Step 2 — Create the test database

Open phpMyAdmin or your MySQL client and run:

```sql
CREATE DATABASE cv_builder_pro_test
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Then import the full schema into this test database:

```bash
mysql -u root -p cv_builder_pro_test < schema.sql
```

> The test database is **separate** from your live `cv_builder_pro` database.  
> Tests will insert and delete rows — never run tests against your live DB.

### Step 3 — Configure test credentials

Open `tests/bootstrap.php` and set your local MySQL credentials:

```php
define('DB_USER', 'root');   // your MySQL username
define('DB_PASS', '');       // your MySQL password
```

---

## 2. Running Tests

### Run all tests
```bash
./vendor/bin/phpunit
```

### Run only unit tests (no DB required)
```bash
./vendor/bin/phpunit --testsuite=Unit
```

### Run only integration tests
```bash
./vendor/bin/phpunit --testsuite=Integration
```

### Run a single test file
```bash
./vendor/bin/phpunit tests/unit/HelpersTest.php
```

### Run a single test method
```bash
./vendor/bin/phpunit --filter test_csrf_token_is_generated_when_missing
```

### Run with readable output (testdox)
```bash
./vendor/bin/phpunit --testdox
```

### Run with composer shortcut
```bash
composer test           # all tests
composer test-unit      # unit only
composer test-integration  # integration only
```

---

## 3. Test Files Explained

### `tests/unit/HelpersTest.php` — 22 tests
Tests every function in `includes/helpers.php`:

| Group | What is tested |
|---|---|
| CSRF | Token generation, reuse, hidden field HTML, verify pass/fail |
| Auth | `auth_check`, `auth_user`, `auth_admin`, `auth_login` |
| Flash | Store, retrieve, auto-clear, HTML render |
| Sanitize | XSS stripping, whitespace trim, Arabic text preservation |

**No database needed.** Uses PHP sessions only.

---

### `tests/unit/DatabaseTest.php` — 11 tests
Tests every method in `includes/Database.php`:

| Group | What is tested |
|---|---|
| Singleton | Same PDO instance returned every call |
| Insert | Returns last insert ID, data persisted |
| fetchOne | Returns correct row, returns false on no match |
| fetchAll | Returns all rows, returns empty array |
| execute | Update/delete returns row count |
| Security | SQL injection via prepared statements is blocked |
| Encoding | Arabic UTF-8 text stored and retrieved correctly |

**Requires test database.** Creates and drops a `_test_users` table automatically.

---

### `tests/unit/CompletenessTest.php` — 16 tests
Tests the CV completeness scoring algorithm:

| Group | What is tested |
|---|---|
| Empty | Zero score on empty CV |
| Personal | Each field adds 5pts, max 30pts |
| Experience | 1 entry = 15pts, 2 = 25pts, 3+ = 30pts |
| Education | 1 = 15pts, 2 = 20pts |
| Skills | 1 = 5pts, 3 = 10pts, 5 = 15pts |
| Languages | 1 = 5pts, 2 = 10pts |
| Certificates | 1+ = 5pts |
| Cap | Score never exceeds 100 |

**No database needed.** Pure PHP logic.

---

### `tests/integration/AuthIntegrationTest.php` — 12 tests
Full register + login flow against the test database:

| Group | What is tested |
|---|---|
| Register | Success, DB persistence, password hashing, duplicate email, invalid email, short password |
| Login | Success, wrong password, nonexistent user, empty fields, case-insensitive email, disabled account |
| Security | Password hash not stored in session |

**Requires test database.**

---

## 4. Test Fixtures

`tests/fixtures/TestFixtures.php` provides seed helpers for integration tests:

```php
// Create a test user and get back their data + ID
$user = TestFixtures::seedUser(['name' => 'Ahmed', 'email' => 'a@test.com']);

// Create an admin
$admin = TestFixtures::seedAdmin();

// Create a CV for the user
$cv = TestFixtures::seedCV($user['id'], ['title' => 'My Dev CV']);

// Seed personal info
TestFixtures::seedPersonalInfo($cv['id']);

// Seed an experience entry
TestFixtures::seedExperience($cv['id'], ['company' => 'Google']);

// Clean up after tests
TestFixtures::cleanUsers('%@test.com');
TestFixtures::cleanCVs($user['id']);
```

---

## 5. Writing New Tests

### Unit test template
```php
<?php
use PHPUnit\Framework\TestCase;

class MyNewTest extends TestCase
{
    protected function setUp(): void
    {
        // runs before every test method
    }

    protected function tearDown(): void
    {
        // runs after every test method — always clean up
    }

    public function test_something_does_what_i_expect(): void
    {
        // Arrange
        $input = 'some value';

        // Act
        $result = my_function($input);

        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### Naming rules
- File: `MyThingTest.php`
- Class: `MyThingTest extends TestCase`
- Method: `test_snake_case_description_of_what_is_being_tested`
- Each test should test **one thing only**

### Assertions cheat sheet
```php
$this->assertTrue($val);
$this->assertFalse($val);
$this->assertEquals($expected, $actual);
$this->assertNotEquals($a, $b);
$this->assertNull($val);
$this->assertNotNull($val);
$this->assertIsArray($val);
$this->assertIsString($val);
$this->assertIsInt($val);
$this->assertEmpty($val);
$this->assertNotEmpty($val);
$this->assertCount(3, $array);
$this->assertArrayHasKey('key', $array);
$this->assertArrayNotHasKey('key', $array);
$this->assertStringContainsString('needle', $haystack);
$this->assertStringNotContainsString('needle', $haystack);
$this->assertGreaterThan(0, $val);
$this->assertLessThanOrEqual(100, $val);
$this->assertInstanceOf(PDO::class, $val);
```

---

## 6. What to Test Per Phase

| Phase | Files to add tests for |
|---|---|
| Phase 2 | `tests/unit/ValidationTest.php` — form validation rules |
| Phase 3 | `tests/integration/CVBuilderTest.php` — CRUD for all CV sections |
| Phase 4 | `tests/unit/ExportTest.php` — template rendering, filename generation |
| Phase 5 | `tests/integration/AdminTest.php` — user management, access control |

---

## 7. Interpreting Results

```
PHPUnit 10.x

HelpersTest
 ✔ Csrf token is generated when missing
 ✔ Csrf token is reused on second call
 ✔ Auth check returns false when no session
 ...

DatabaseTest
 ✔ Get instance returns pdo instance
 ✔ Insert returns last insert id
 ...

Time: 0.312 seconds, Memory: 8.00 MB

OK (49 tests, 61 assertions)
```

- Green `✔` = passed
- Red `✘` = failed — read the diff output to find the bug
- `E` = PHP error thrown inside the test
- `W` = warning or deprecation

---

## 8. Code Coverage Report

```bash
# Requires Xdebug or PCOV installed
composer test-coverage

# Then open in browser:
open coverage/html/index.html
```

Coverage shows which lines of `includes/` and `api/` are exercised by tests.  
Aim for **>80% coverage** on `helpers.php` and `Database.php` before going to production.

---

## 9. CI Tip (optional)

If you add GitHub Actions later, add this `.github/workflows/tests.yml`:

```yaml
name: Tests
on: [push, pull_request]
jobs:
  phpunit:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: ''
          MYSQL_ALLOW_EMPTY_PASSWORD: yes
          MYSQL_DATABASE: cv_builder_pro_test
        options: --health-cmd="mysqladmin ping" --health-interval=10s
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.2', extensions: pdo_mysql }
      - run: composer install --no-progress
      - run: mysql -u root cv_builder_pro_test < schema.sql
      - run: composer test
```
