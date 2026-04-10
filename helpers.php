<?php
// ============================================================
//  CV Builder Pro — Session, CSRF & Auth Helpers
// ============================================================

require_once __DIR__ . '/config.php';

// ── Session bootstrap ────────────────────────────────────────
function session_boot(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// ── CSRF ─────────────────────────────────────────────────────
function csrf_token(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrf_field(): string
{
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrf_token() . '">';
}

function csrf_verify(): bool
{
    $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}

function csrf_abort(): void
{
    if (!csrf_verify()) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Invalid CSRF token.']));
    }
}

// ── Auth ─────────────────────────────────────────────────────
function auth_user(): array|false
{
    return $_SESSION['user'] ?? false;
}

function auth_check(): bool
{
    return !empty($_SESSION['user']['id']);
}

function auth_admin(): bool
{
    return auth_check() && ($_SESSION['user']['role'] ?? '') === 'admin';
}

function auth_require(): void
{
    if (!auth_check()) {
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }
}

function auth_require_admin(): void
{
    if (!auth_admin()) {
        header('Location: ' . APP_URL . '/pages/dashboard.php');
        exit;
    }
}

function auth_login(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'    => $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
        'avatar'=> $user['avatar'],
    ];
}

function auth_logout(): void
{
    session_unset();
    session_destroy();
    // Clear remember-me cookie
    setcookie('cvb_remember', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

// ── Flash messages ───────────────────────────────────────────
function flash(string $key, string $message, string $type = 'info'): void
{
    $_SESSION['flash'][$key] = ['message' => $message, 'type' => $type];
}

function flash_get(string $key): array|null
{
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function flash_render(): string
{
    if (empty($_SESSION['flash'])) return '';
    $html = '';
    foreach ($_SESSION['flash'] as $key => $f) {
        $type = htmlspecialchars($f['type']);
        $msg  = htmlspecialchars($f['message']);
        $html .= "<div class=\"cvb-toast cvb-toast--{$type}\" data-flash=\"{$key}\">{$msg}</div>";
        unset($_SESSION['flash'][$key]);
    }
    return $html;
}

// ── JSON response helper ─────────────────────────────────────
function json_response(bool $success, string $message, array $data = []): never
{
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// ── Sanitize input ───────────────────────────────────────────
function clean(mixed $val): string
{
    return htmlspecialchars(trim((string)$val), ENT_QUOTES, 'UTF-8');
}

function post(string $key, string $default = ''): string
{
    return clean($_POST[$key] ?? $default);
}

// ── Redirect ─────────────────────────────────────────────────
function redirect(string $path): never
{
    header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
    exit;
}

// Bootstrap session on every include
session_boot();
