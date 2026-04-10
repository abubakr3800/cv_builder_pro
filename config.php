<?php
// ============================================================
//  CV Builder Pro — Application Configuration
//  Author  : Ahmed Mohamed Abubakr
//  Site    : https://abubakr.rf.gd/
//  Phone   : 01113284597
// ============================================================

// ── Environment ─────────────────────────────────────────────
define('APP_ENV',     'development');   // 'development' | 'production'
define('APP_NAME',    'CV Builder Pro');
define('APP_URL',     'https://abubakr.rf.gd/cv-builder-pro');
define('APP_VERSION', '1.0.0');

// ── Database ─────────────────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_NAME',     'cv_builder_pro');
define('DB_USER',     'root');           // ← change on live server
define('DB_PASS',     '');               // ← change on live server
define('DB_CHARSET',  'utf8mb4');

// ── Paths ────────────────────────────────────────────────────
define('ROOT_PATH',     dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH',  ROOT_PATH . '/uploads');
define('EXPORTS_PATH',  ROOT_PATH . '/exports');
define('TEMPLATES_PATH',ROOT_PATH . '/templates');
define('ASSETS_PATH',   ROOT_PATH . '/assets');

// ── Security ─────────────────────────────────────────────────
define('SESSION_NAME',        'cvb_session');
define('SESSION_LIFETIME',    3600 * 24 * 7);   // 7 days
define('REMEMBER_ME_DAYS',    30);
define('CSRF_TOKEN_NAME',     '_cvb_csrf');
define('MAX_UPLOAD_SIZE',     5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMG_TYPES',   ['image/jpeg', 'image/png', 'image/webp']);

// ── Branding ─────────────────────────────────────────────────
define('OWNER_NAME',    'Ahmed Mohamed Abubakr');
define('OWNER_PHONE',   '01113284597');
define('OWNER_SITE',    'https://abubakr.rf.gd/');
define('BRAND_RED',     '#eb1b26');
define('BRAND_DARK_RED','#a40e16');

// ── Error reporting ──────────────────────────────────────────
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ── Timezone ─────────────────────────────────────────────────
date_default_timezone_set('Africa/Cairo');
