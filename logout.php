<?php
// ============================================================
//  CV Builder Pro — Logout
//  Author  : Ahmed Mohamed Abubakr
//  Site    : https://abubakr.rf.gd/
//  Phase   : 2 — Auth + UI Shell
// ============================================================

require_once __DIR__ . '/../includes/bootstrap.php';

// Must be authenticated to log out (prevents CSRF abuse via anonymous GET)
if (auth_check()) {
    // Clear remember-me token from DB
    $userId = auth_user()['id'] ?? null;
    if ($userId) {
        Database::execute(
            'UPDATE users SET remember_token = NULL WHERE id = ?',
            [$userId]
        );
    }

    auth_logout(); // Destroys session, clears cookie, redirects to login
}

// If not logged in, just redirect to login
redirect('auth/login.php');
