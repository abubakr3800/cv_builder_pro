<?php
// ============================================================
//  CV Builder Pro — Base Layout Shell
//  Author  : Ahmed Mohamed Abubakr
//  Site    : https://abubakr.rf.gd/
//  Phase   : 2 — Auth + UI Shell
//
//  Usage:
//    $page_title   = 'Dashboard';        // <title> suffix
//    $active_page  = 'dashboard';        // sidebar highlight
//    require_once ROOT_PATH . '/includes/layout.php';
//    // … your page content …
//    layout_end();
// ============================================================

require_once __DIR__ . '/bootstrap.php';
auth_require();  // redirect to login if not authenticated

$__user       = auth_user();
$__theme      = $_COOKIE['cvb_theme'] ?? 'auto';   // 'light' | 'dark' | 'auto'
$__page_title = $page_title ?? 'CV Builder Pro';
$__active     = $active_page ?? '';

// ── Helper: render a sidebar nav item ────────────────────────
function nav_item(string $href, string $icon, string $label, string $active): string
{
    $cls = $active === basename($href, '.php') ? ' is-active' : '';
    return <<<HTML
    <a href="{$href}" class="nav-item{$cls}">
      <span class="nav-icon">{$icon}</span>
      <span class="nav-label">{$label}</span>
    </a>
    HTML;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($__theme) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($__page_title) ?> — CV Builder Pro</title>

  <!-- SC Brand Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;1,400&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600&display=swap" rel="stylesheet">

  <!-- SC Logo -->
  <link rel="icon" type="image/png" href="http://shortcircuit.company/SCbrand/favicon.png">

  <style>
    /* ══════════════════════════════════════════════════════════
       SC BRAND TOKENS
       ══════════════════════════════════════════════════════════ */
    :root {
      /* Brand palette */
      --sc-red:       #eb1b26;
      --sc-dark-red:  #a40e16;
      --sc-black:     #000000;
      --sc-grey:      #cccccc;
      --sc-white:     #ffffff;

      /* Type scale — golden ratio base 14 */
      --t-xs:   11px;
      --t-sm:   13px;
      --t-base: 14px;
      --t-md:   16px;
      --t-lg:   20px;
      --t-xl:   26px;
      --t-2xl:  34px;

      /* Spacing */
      --sp-1: 4px;
      --sp-2: 8px;
      --sp-3: 12px;
      --sp-4: 16px;
      --sp-5: 20px;
      --sp-6: 24px;
      --sp-8: 32px;
      --sp-10: 40px;

      /* Layout */
      --sidebar-w: 224px;
      --sidebar-w-collapsed: 64px;
      --topbar-h: 56px;
      --radius-sm: 6px;
      --radius-md: 10px;
      --radius-lg: 16px;

      /* Transition */
      --ease: cubic-bezier(.4,0,.2,1);
      --dur: .22s;
    }

    /* ── Light theme (default) ── */
    [data-theme="light"],
    [data-theme="auto"] {
      --bg-page:    #f4f4f5;
      --bg-card:    #ffffff;
      --bg-sidebar: #111111;
      --bg-topbar:  #ffffff;
      --bg-hover:   rgba(235,27,38,.07);
      --bg-active:  rgba(235,27,38,.12);
      --bg-input:   #fafafa;

      --txt-primary:   #111111;
      --txt-secondary: #555555;
      --txt-muted:     #999999;
      --txt-sidebar:   #cccccc;
      --txt-sidebar-active: #ffffff;

      --border:    rgba(0,0,0,.09);
      --border-strong: rgba(0,0,0,.18);

      --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
      --shadow-md: 0 4px 16px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.04);
      --shadow-lg: 0 8px 32px rgba(0,0,0,.12), 0 2px 8px rgba(0,0,0,.06);

      color-scheme: light;
    }

    /* ── Dark theme ── */
    [data-theme="dark"] {
      --bg-page:    #0d0d0e;
      --bg-card:    #18181b;
      --bg-sidebar: #0a0a0b;
      --bg-topbar:  #18181b;
      --bg-hover:   rgba(235,27,38,.10);
      --bg-active:  rgba(235,27,38,.18);
      --bg-input:   #222226;

      --txt-primary:   #f0f0f0;
      --txt-secondary: #aaaaaa;
      --txt-muted:     #666666;
      --txt-sidebar:   #888888;
      --txt-sidebar-active: #ffffff;

      --border:    rgba(255,255,255,.06);
      --border-strong: rgba(255,255,255,.12);

      --shadow-sm: 0 1px 3px rgba(0,0,0,.3);
      --shadow-md: 0 4px 16px rgba(0,0,0,.4);
      --shadow-lg: 0 8px 32px rgba(0,0,0,.5);

      color-scheme: dark;
    }

    /* ══════════════════════════════════════════════════════════
       RESET + BASE
       ══════════════════════════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html { font-size: var(--t-base); }

    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg-page);
      color: var(--txt-primary);
      min-height: 100vh;
      display: flex;
      overflow-x: hidden;
      transition: background var(--dur) var(--ease), color var(--dur) var(--ease);
    }

    a { color: inherit; text-decoration: none; }
    button { cursor: pointer; border: none; background: none; font-family: inherit; }
    img { display: block; max-width: 100%; }

    /* ══════════════════════════════════════════════════════════
       SIDEBAR
       ══════════════════════════════════════════════════════════ */
    .sidebar {
      position: fixed;
      top: 0; left: 0; bottom: 0;
      width: var(--sidebar-w);
      background: var(--bg-sidebar);
      display: flex;
      flex-direction: column;
      z-index: 200;
      transition: width var(--dur) var(--ease), transform var(--dur) var(--ease);
      overflow: hidden;
    }
    .sidebar.collapsed { width: var(--sidebar-w-collapsed); }

    /* Logo area */
    .sidebar-logo {
      display: flex;
      align-items: center;
      gap: var(--sp-3);
      padding: 0 var(--sp-4);
      height: var(--topbar-h);
      border-bottom: 1px solid rgba(255,255,255,.05);
      flex-shrink: 0;
      overflow: hidden;
    }
    .sidebar-logo img {
      height: 28px;
      width: auto;
      flex-shrink: 0;
      filter: brightness(0) invert(1);
    }
    .sidebar-logo-text {
      font-family: 'Anton', sans-serif;
      font-size: var(--t-md);
      color: var(--sc-white);
      white-space: nowrap;
      letter-spacing: .04em;
      text-transform: uppercase;
      opacity: 1;
      transition: opacity var(--dur) var(--ease);
    }
    .sidebar.collapsed .sidebar-logo-text { opacity: 0; pointer-events: none; }

    /* Nav */
    .sidebar-nav {
      flex: 1;
      padding: var(--sp-4) var(--sp-2);
      display: flex;
      flex-direction: column;
      gap: var(--sp-1);
      overflow-y: auto;
      overflow-x: hidden;
    }

    .nav-section-label {
      font-size: var(--t-xs);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .10em;
      color: rgba(255,255,255,.25);
      padding: var(--sp-3) var(--sp-3) var(--sp-2);
      white-space: nowrap;
      overflow: hidden;
      transition: opacity var(--dur);
    }
    .sidebar.collapsed .nav-section-label { opacity: 0; }

    .nav-item {
      display: flex;
      align-items: center;
      gap: var(--sp-3);
      padding: 10px var(--sp-3);
      border-radius: var(--radius-sm);
      color: var(--txt-sidebar);
      font-size: var(--t-sm);
      font-weight: 500;
      white-space: nowrap;
      transition: background var(--dur), color var(--dur);
      position: relative;
      overflow: hidden;
    }
    .nav-item:hover {
      background: rgba(255,255,255,.06);
      color: var(--sc-white);
    }
    .nav-item.is-active {
      background: var(--sc-red);
      color: var(--sc-white);
    }
    .nav-item.is-active::before {
      content: '';
      position: absolute;
      right: 0; top: 20%; bottom: 20%;
      width: 3px;
      background: rgba(255,255,255,.6);
      border-radius: 2px;
    }

    .nav-icon {
      font-size: 17px;
      flex-shrink: 0;
      width: 22px;
      text-align: center;
      line-height: 1;
    }
    .nav-label {
      opacity: 1;
      transition: opacity var(--dur);
    }
    .sidebar.collapsed .nav-label { opacity: 0; }

    /* Sidebar footer */
    .sidebar-footer {
      padding: var(--sp-4) var(--sp-2);
      border-top: 1px solid rgba(255,255,255,.05);
      display: flex;
      flex-direction: column;
      gap: var(--sp-1);
      overflow: hidden;
    }

    /* Collapse toggle */
    .sidebar-toggle {
      display: flex;
      align-items: center;
      gap: var(--sp-3);
      padding: 8px var(--sp-3);
      border-radius: var(--radius-sm);
      color: rgba(255,255,255,.35);
      font-size: var(--t-xs);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .08em;
      white-space: nowrap;
      transition: color var(--dur), background var(--dur);
    }
    .sidebar-toggle:hover { color: rgba(255,255,255,.7); background: rgba(255,255,255,.05); }
    .sidebar-toggle .toggle-icon {
      font-size: 16px;
      flex-shrink: 0;
      width: 22px;
      text-align: center;
      transition: transform var(--dur) var(--ease);
    }
    .sidebar.collapsed .sidebar-toggle .toggle-icon { transform: scaleX(-1); }

    /* ══════════════════════════════════════════════════════════
       TOP BAR
       ══════════════════════════════════════════════════════════ */
    .topbar {
      position: fixed;
      top: 0; right: 0;
      left: var(--sidebar-w);
      height: var(--topbar-h);
      background: var(--bg-topbar);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      padding: 0 var(--sp-6);
      gap: var(--sp-4);
      z-index: 100;
      transition: left var(--dur) var(--ease), background var(--dur) var(--ease);
      box-shadow: var(--shadow-sm);
    }
    .sidebar.collapsed ~ .topbar,
    body.sidebar-collapsed .topbar {
      left: var(--sidebar-w-collapsed);
    }

    .topbar-title {
      font-family: 'Anton', sans-serif;
      font-size: var(--t-xl);
      text-transform: uppercase;
      letter-spacing: .04em;
      color: var(--txt-primary);
      flex: 1;
    }

    .topbar-actions {
      display: flex;
      align-items: center;
      gap: var(--sp-3);
    }

    /* Theme toggle */
    .theme-toggle {
      width: 36px; height: 36px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 16px;
      color: var(--txt-secondary);
      transition: background var(--dur), color var(--dur), transform .3s var(--ease);
      border: 1px solid var(--border);
    }
    .theme-toggle:hover {
      background: var(--bg-hover);
      color: var(--sc-red);
      transform: rotate(20deg);
    }

    /* Notification bell */
    .topbar-bell {
      position: relative;
      width: 36px; height: 36px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 17px;
      color: var(--txt-secondary);
      border: 1px solid var(--border);
      transition: background var(--dur), color var(--dur);
    }
    .topbar-bell:hover { background: var(--bg-hover); color: var(--sc-red); }
    .topbar-bell .badge {
      position: absolute;
      top: 6px; right: 6px;
      width: 8px; height: 8px;
      background: var(--sc-red);
      border-radius: 50%;
      border: 1.5px solid var(--bg-topbar);
    }

    /* Avatar + dropdown */
    .topbar-avatar {
      position: relative;
    }
    .avatar-btn {
      display: flex;
      align-items: center;
      gap: var(--sp-2);
      padding: 4px var(--sp-3) 4px 4px;
      border-radius: 24px;
      border: 1px solid var(--border);
      transition: background var(--dur), border-color var(--dur);
    }
    .avatar-btn:hover { background: var(--bg-hover); border-color: var(--sc-red); }
    .avatar-img {
      width: 28px; height: 28px;
      border-radius: 50%;
      background: var(--sc-red);
      color: var(--sc-white);
      font-family: 'Anton', sans-serif;
      font-size: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      overflow: hidden;
    }
    .avatar-img img { width: 100%; height: 100%; object-fit: cover; }
    .avatar-name {
      font-size: var(--t-sm);
      font-weight: 500;
      color: var(--txt-primary);
      max-width: 110px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .avatar-chevron { font-size: 10px; color: var(--txt-muted); }

    /* Dropdown menu */
    .avatar-dropdown {
      position: absolute;
      top: calc(100% + 8px);
      right: 0;
      width: 200px;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-lg);
      padding: var(--sp-2);
      opacity: 0;
      pointer-events: none;
      transform: translateY(-6px);
      transition: opacity var(--dur), transform var(--dur);
      z-index: 300;
    }
    .topbar-avatar.open .avatar-dropdown {
      opacity: 1;
      pointer-events: all;
      transform: translateY(0);
    }
    .avatar-dropdown-header {
      padding: var(--sp-2) var(--sp-3) var(--sp-3);
      border-bottom: 1px solid var(--border);
      margin-bottom: var(--sp-1);
    }
    .avatar-dropdown-header .dd-name {
      font-weight: 600;
      font-size: var(--t-sm);
      color: var(--txt-primary);
      margin-bottom: 2px;
    }
    .avatar-dropdown-header .dd-email {
      font-size: var(--t-xs);
      color: var(--txt-muted);
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .dd-item {
      display: flex;
      align-items: center;
      gap: var(--sp-2);
      padding: 8px var(--sp-3);
      border-radius: var(--radius-sm);
      font-size: var(--t-sm);
      color: var(--txt-secondary);
      transition: background var(--dur), color var(--dur);
    }
    .dd-item:hover { background: var(--bg-hover); color: var(--txt-primary); }
    .dd-item.danger:hover { background: rgba(235,27,38,.08); color: var(--sc-red); }
    .dd-divider { height: 1px; background: var(--border); margin: var(--sp-1) 0; }

    /* Mobile hamburger */
    .hamburger {
      display: none;
      width: 36px; height: 36px;
      border-radius: var(--radius-sm);
      align-items: center;
      justify-content: center;
      font-size: 20px;
      color: var(--txt-secondary);
      border: 1px solid var(--border);
    }

    /* ══════════════════════════════════════════════════════════
       MAIN CONTENT
       ══════════════════════════════════════════════════════════ */
    .main-wrap {
      margin-left: var(--sidebar-w);
      margin-top: var(--topbar-h);
      min-height: calc(100vh - var(--topbar-h));
      width: calc(100% - var(--sidebar-w));
      transition: margin-left var(--dur) var(--ease), width var(--dur) var(--ease);
    }
    body.sidebar-collapsed .main-wrap {
      margin-left: var(--sidebar-w-collapsed);
      width: calc(100% - var(--sidebar-w-collapsed));
    }

    .page-content {
      padding: var(--sp-6);
      max-width: 1280px;
      margin: 0 auto;
    }

    /* ── Toast system ──────────────────────────────────────── */
    .toast-container {
      position: fixed;
      bottom: var(--sp-6);
      right: var(--sp-6);
      display: flex;
      flex-direction: column;
      gap: var(--sp-2);
      z-index: 9999;
    }
    .cvb-toast {
      padding: 12px var(--sp-4);
      border-radius: var(--radius-md);
      font-size: var(--t-sm);
      font-weight: 500;
      box-shadow: var(--shadow-md);
      animation: slideInToast .3s var(--ease) both;
      max-width: 340px;
    }
    @keyframes slideInToast {
      from { opacity: 0; transform: translateX(20px); }
      to   { opacity: 1; transform: translateX(0); }
    }
    .cvb-toast--success { background: #16a34a; color: #fff; }
    .cvb-toast--error   { background: var(--sc-red); color: #fff; }
    .cvb-toast--info    { background: #2563eb; color: #fff; }
    .cvb-toast--warning { background: #d97706; color: #fff; }

    /* ── Overlay (mobile) ──────────────────────────────────── */
    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.5);
      z-index: 150;
    }

    /* ══════════════════════════════════════════════════════════
       RESPONSIVE
       ══════════════════════════════════════════════════════════ */
    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
        width: var(--sidebar-w);
      }
      .sidebar.mobile-open {
        transform: translateX(0);
      }
      .sidebar-overlay { display: block; opacity: 0; pointer-events: none; transition: opacity var(--dur); }
      .sidebar.mobile-open ~ .sidebar-overlay { opacity: 1; pointer-events: all; }

      .topbar { left: 0 !important; padding: 0 var(--sp-4); }
      .hamburger { display: flex; }
      .topbar-title { font-size: var(--t-lg); }
      .avatar-name { display: none; }

      .main-wrap {
        margin-left: 0 !important;
        width: 100% !important;
      }
      .page-content { padding: var(--sp-4); }
    }

    /* ══════════════════════════════════════════════════════════
       SHARED COMPONENTS (used by child pages)
       ══════════════════════════════════════════════════════════ */

    /* Cards */
    .card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-sm);
      transition: background var(--dur), border-color var(--dur);
    }

    /* Section headers */
    .section-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: var(--sp-5);
    }
    .section-title {
      font-family: 'Anton', sans-serif;
      font-size: var(--t-xl);
      text-transform: uppercase;
      letter-spacing: .04em;
      color: var(--txt-primary);
    }
    .section-sub {
      font-size: var(--t-sm);
      color: var(--txt-muted);
      margin-top: 2px;
    }

    /* Primary button */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: var(--sp-2);
      padding: 9px var(--sp-5);
      border-radius: var(--radius-sm);
      font-family: 'Anton', sans-serif;
      font-size: var(--t-sm);
      letter-spacing: .06em;
      text-transform: uppercase;
      transition: background var(--dur), transform .1s, box-shadow var(--dur);
      border: none;
      cursor: pointer;
    }
    .btn-primary {
      background: var(--sc-red);
      color: #fff;
      box-shadow: 0 2px 8px rgba(235,27,38,.30);
    }
    .btn-primary:hover {
      background: var(--sc-dark-red);
      transform: translateY(-1px);
      box-shadow: 0 4px 14px rgba(235,27,38,.40);
    }
    .btn-primary:active { transform: translateY(0); }
    .btn-ghost {
      background: transparent;
      color: var(--txt-secondary);
      border: 1px solid var(--border);
    }
    .btn-ghost:hover { border-color: var(--sc-red); color: var(--sc-red); background: var(--bg-hover); }

    /* Badge */
    .badge {
      display: inline-flex;
      align-items: center;
      padding: 2px 8px;
      border-radius: 20px;
      font-size: var(--t-xs);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .06em;
    }
    .badge-red   { background: rgba(235,27,38,.12); color: var(--sc-red); }
    .badge-green { background: rgba(22,163,74,.12);  color: #16a34a; }
    .badge-blue  { background: rgba(37,99,235,.12);  color: #2563eb; }
    .badge-grey  { background: rgba(100,100,100,.12); color: var(--txt-muted); }

    /* Empty state */
    .empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: var(--sp-10) var(--sp-6);
      text-align: center;
      gap: var(--sp-4);
    }
    .empty-icon {
      font-size: 52px;
      line-height: 1;
      filter: grayscale(1);
      opacity: .35;
    }
    .empty-title {
      font-family: 'Anton', sans-serif;
      font-size: var(--t-xl);
      text-transform: uppercase;
      color: var(--txt-secondary);
    }
    .empty-desc {
      font-size: var(--t-sm);
      color: var(--txt-muted);
      max-width: 320px;
    }

    /* Skeleton loader */
    .skeleton {
      background: linear-gradient(90deg, var(--border) 25%, var(--bg-hover) 50%, var(--border) 75%);
      background-size: 200% 100%;
      animation: shimmer 1.4s infinite;
      border-radius: var(--radius-sm);
    }
    @keyframes shimmer {
      from { background-position: 200% 0; }
      to   { background-position: -200% 0; }
    }

    /* Page enter animation */
    .page-content { animation: pageFadeIn .35s var(--ease) both; }
    @keyframes pageFadeIn {
      from { opacity: 0; transform: translateY(8px); }
      to   { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body class="" id="app-body">

<!-- ── Sidebar ──────────────────────────────────────────── -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <img src="http://shortcircuit.company/SCbrand/logo.png"
         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
         alt="SC">
    <span class="sidebar-logo-text">CV Builder</span>
  </div>

  <nav class="sidebar-nav">
    <span class="nav-section-label">Main</span>
    <?= nav_item(APP_URL . '/pages/dashboard.php',  '📊', 'Dashboard',  $__active) ?>
    <?= nav_item(APP_URL . '/pages/my-cvs.php',     '📄', 'My CVs',     $__active) ?>

    <span class="nav-section-label">Account</span>
    <?= nav_item(APP_URL . '/pages/account.php',    '👤', 'Account',    $__active) ?>

    <?php if (auth_admin()): ?>
    <span class="nav-section-label">Admin</span>
    <?= nav_item(APP_URL . '/admin/users.php',       '🛡️', 'Users',      $__active) ?>
    <?= nav_item(APP_URL . '/admin/export-log.php',  '📦', 'Export Log', $__active) ?>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <?= nav_item(APP_URL . '/auth/logout.php', '🚪', 'Log Out', '') ?>
    <button class="sidebar-toggle" id="collapseBtn" onclick="toggleSidebar()">
      <span class="toggle-icon">◀</span>
      <span class="nav-label">Collapse</span>
    </button>
  </div>
</aside>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeMobileSidebar()"></div>

<!-- ── Top Bar ───────────────────────────────────────────── -->
<header class="topbar" id="topbar">
  <button class="hamburger" onclick="openMobileSidebar()">☰</button>

  <div class="topbar-title"><?= htmlspecialchars($__page_title) ?></div>

  <div class="topbar-actions">

    <!-- Theme toggle -->
    <button class="theme-toggle" id="themeBtn" onclick="cycleTheme()" title="Toggle theme">
      <span id="themeIcon">🌙</span>
    </button>

    <!-- Notification bell -->
    <div class="topbar-bell">
      🔔
    </div>

    <!-- Avatar + dropdown -->
    <div class="topbar-avatar" id="avatarMenu">
      <button class="avatar-btn" onclick="toggleAvatarMenu()">
        <div class="avatar-img" id="avatarEl">
          <?php if (!empty($__user['avatar'])): ?>
            <img src="<?= htmlspecialchars(APP_URL . '/uploads/' . $__user['avatar']) ?>" alt="avatar">
          <?php else: ?>
            <?= strtoupper(mb_substr($__user['name'], 0, 2)) ?>
          <?php endif; ?>
        </div>
        <span class="avatar-name"><?= htmlspecialchars($__user['name']) ?></span>
        <span class="avatar-chevron">▾</span>
      </button>

      <div class="avatar-dropdown" id="avatarDropdown">
        <div class="avatar-dropdown-header">
          <div class="dd-name"><?= htmlspecialchars($__user['name']) ?></div>
          <div class="dd-email"><?= htmlspecialchars($__user['email']) ?></div>
        </div>
        <a class="dd-item" href="<?= APP_URL ?>/pages/account.php">
          <span>👤</span> Profile & Settings
        </a>
        <a class="dd-item" href="<?= APP_URL ?>/pages/my-cvs.php">
          <span>📄</span> My CVs
        </a>
        <div class="dd-divider"></div>
        <a class="dd-item danger" href="<?= APP_URL ?>/auth/logout.php">
          <span>🚪</span> Log Out
        </a>
      </div>
    </div>

  </div>
</header>

<!-- ── Flash toast container ─────────────────────────────── -->
<div class="toast-container" id="toastContainer">
  <?= flash_render() ?>
</div>

<!-- ── Main content wrapper ──────────────────────────────── -->
<main class="main-wrap" id="mainWrap">
  <div class="page-content">

<?php
// ── layout_end() — close the layout shell ──────────────────
function layout_end(): void { ?>

  </div><!-- /.page-content -->
</main><!-- /.main-wrap -->

<script>
/* ── Sidebar collapse ──────────────────────────────────── */
const sidebar    = document.getElementById('sidebar');
const body       = document.getElementById('app-body');
const COLLAPSED  = 'sidebar-collapsed';

function toggleSidebar() {
  sidebar.classList.toggle('collapsed');
  body.classList.toggle(COLLAPSED);
  localStorage.setItem('cvb_sidebar', sidebar.classList.contains('collapsed') ? '1' : '0');
}

// Restore on load
if (localStorage.getItem('cvb_sidebar') === '1') {
  sidebar.classList.add('collapsed');
  body.classList.add(COLLAPSED);
}

/* ── Mobile sidebar ────────────────────────────────────── */
function openMobileSidebar() {
  sidebar.classList.add('mobile-open');
}
function closeMobileSidebar() {
  sidebar.classList.remove('mobile-open');
}

/* ── Theme toggle ──────────────────────────────────────── */
const html      = document.documentElement;
const themeIcon = document.getElementById('themeIcon');
const themes    = ['light', 'dark'];

function applyTheme(t) {
  html.setAttribute('data-theme', t);
  themeIcon.textContent = t === 'dark' ? '☀️' : '🌙';
  document.cookie = `cvb_theme=${t};path=/;max-age=${60*60*24*365}`;
}

function cycleTheme() {
  const cur  = html.getAttribute('data-theme') || 'light';
  const next = themes[(themes.indexOf(cur) + 1) % themes.length];
  applyTheme(next);
}

// Init icon from current theme
applyTheme(html.getAttribute('data-theme') || 'light');

/* ── Avatar dropdown ───────────────────────────────────── */
const avatarMenu = document.getElementById('avatarMenu');

function toggleAvatarMenu() {
  avatarMenu.classList.toggle('open');
}

document.addEventListener('click', (e) => {
  if (!avatarMenu.contains(e.target)) {
    avatarMenu.classList.remove('open');
  }
});

/* ── Auto-dismiss flash toasts ─────────────────────────── */
document.querySelectorAll('.cvb-toast').forEach(el => {
  setTimeout(() => {
    el.style.opacity = '0';
    el.style.transition = 'opacity .4s';
    setTimeout(() => el.remove(), 400);
  }, 4000);
});
</script>

<?php } // end layout_end() ?>
