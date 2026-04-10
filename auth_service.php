<?php
// ============================================================
//  CV Builder Pro — Auth Service
//  Author  : Ahmed Mohamed Abubakr
//  Site    : https://abubakr.rf.gd/
//  Phase   : 2 — Auth + UI Shell
//
//  Pure service functions — no HTTP, no session, no output.
//  Used by: auth/register.php, auth/login.php, and unit tests.
//
//  Return shape (all functions):
//    ['success' => bool, 'message' => string, ...extra]
// ============================================================

require_once __DIR__ . '/Database.php';

// ── Register ─────────────────────────────────────────────────
/**
 * Validate and create a new user account.
 *
 * @param  string $name
 * @param  string $email
 * @param  string $password      Plain-text, not yet hashed
 * @return array{success:bool, message:string, user_id?:int}
 */
function auth_register_user(string $name, string $email, string $password): array
{
    $name  = trim($name);
    $email = strtolower(trim($email));

    // ── Validation ───────────────────────────────────────────
    if (empty($name) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'All fields are required.'];
    }
    if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
        return ['success' => false, 'message' => 'Name must be between 2 and 150 characters.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address.'];
    }
    if (mb_strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return ['success' => false, 'message' => 'Password must contain at least one uppercase letter.'];
    }
    if (!preg_match('/[0-9]/', $password)) {
        return ['success' => false, 'message' => 'Password must contain at least one number.'];
    }

    // ── Uniqueness check ─────────────────────────────────────
    $existing = Database::fetchOne(
        'SELECT id FROM users WHERE email = ? LIMIT 1',
        [$email]
    );
    if ($existing) {
        return ['success' => false, 'message' => 'Email already registered.'];
    }

    // ── Create account ───────────────────────────────────────
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $id = Database::insert(
        'INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, ?)',
        [$name, $email, $hash, 'user', 1]
    );

    if (!$id) {
        return ['success' => false, 'message' => 'Could not create account. Please try again.'];
    }

    return ['success' => true, 'message' => 'Registered.', 'user_id' => (int)$id];
}


// ── Login ─────────────────────────────────────────────────────
/**
 * Validate credentials and return the user row (minus password).
 *
 * @param  string $email
 * @param  string $password  Plain-text
 * @return array{success:bool, message:string, user?:array}
 */
function auth_login_user(string $email, string $password): array
{
    $email = strtolower(trim($email));

    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email and password are required.'];
    }

    $user = Database::fetchOne(
        'SELECT * FROM users WHERE email = ? LIMIT 1',
        [$email]
    );

    // Constant-time response to prevent user enumeration
    if (!$user) {
        password_verify($password, '$2y$12$invalidHashToPreventTimingAttacks.......invalid');
        return ['success' => false, 'message' => 'Invalid credentials.'];
    }

    if (!(bool)$user['is_active']) {
        return ['success' => false, 'message' => 'Account is disabled. Contact support.'];
    }

    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid credentials.'];
    }

    // Strip the hash before returning — callers must never receive it
    unset($user['password']);

    return ['success' => true, 'message' => 'Login successful.', 'user' => $user];
}


// ── Change Password ───────────────────────────────────────────
/**
 * Verify current password then update to a new one.
 *
 * @param  int    $userId
 * @param  string $currentPassword   Plain-text current password
 * @param  string $newPassword       Plain-text new password (already confirmed by caller)
 * @return array{success:bool, message:string}
 */
function auth_change_password(int $userId, string $currentPassword, string $newPassword): array
{
    if (empty($currentPassword) || empty($newPassword)) {
        return ['success' => false, 'message' => 'All password fields are required.'];
    }
    if (mb_strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters.'];
    }
    if (!preg_match('/[A-Z]/', $newPassword)) {
        return ['success' => false, 'message' => 'New password must contain at least one uppercase letter.'];
    }
    if (!preg_match('/[0-9]/', $newPassword)) {
        return ['success' => false, 'message' => 'New password must contain at least one number.'];
    }

    $user = Database::fetchOne(
        'SELECT id, password FROM users WHERE id = ? AND is_active = 1 LIMIT 1',
        [$userId]
    );

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect.'];
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    Database::execute(
        'UPDATE users SET password = ?, remember_token = NULL, updated_at = NOW() WHERE id = ?',
        [$hash, $userId]
    );

    return ['success' => true, 'message' => 'Password updated successfully.'];
}


// ── Rehash on login (future-proof) ───────────────────────────
/**
 * If the stored hash is outdated (cost changed), rehash transparently after login.
 * Call this right after a successful password_verify.
 *
 * @param  int    $userId
 * @param  string $currentHash   Hash currently in DB
 * @param  string $plainPassword Verified plain-text password
 */
function auth_rehash_if_needed(int $userId, string $currentHash, string $plainPassword): void
{
    if (password_needs_rehash($currentHash, PASSWORD_BCRYPT, ['cost' => 12])) {
        $newHash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        Database::execute(
            'UPDATE users SET password = ? WHERE id = ?',
            [$newHash, $userId]
        );
    }
}
