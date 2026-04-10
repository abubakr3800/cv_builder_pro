<?php
// ============================================================
//  CV Builder Pro — Login Page
//  Author  : Ahmed Mohamed Abubakr
//  Site    : https://abubakr.rf.gd/
//  Phase   : 2 — Auth + UI Shell
// ============================================================

require_once __DIR__ . '/../includes/bootstrap.php';

// Already logged in → redirect to dashboard
if (auth_check()) {
    redirect('pages/dashboard.php');
}

$error   = '';
$success = '';

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_abort(); // 403 if token mismatch

    $email    = post('email');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Basic validation
    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $user = Database::fetchOne(
            'SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1',
            [$email]
        );

        if ($user && password_verify($password, $user['password'])) {
            auth_login($user);

            // Remember me cookie
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                Database::execute(
                    'UPDATE users SET remember_token = ? WHERE id = ?',
                    [$token, $user['id']]
                );
                setcookie(
                    'cvb_remember',
                    $token,
                    time() + (86400 * REMEMBER_ME_DAYS),
                    '/',
                    '',
                    isset($_SERVER['HTTPS']),
                    true
                );
            }

            flash('login_success', 'Welcome back, ' . $user['name'] . '!', 'success');
            redirect('pages/dashboard.php');
        } else {
            $error = 'Incorrect email or password. Please try again.';
        }
    }
}

// Flash from other pages (e.g. after register or password reset)
$flash_reg = flash_get('register_success');
if ($flash_reg) $success = $flash_reg['message'];
$flash_reset = flash_get('password_reset');
if ($flash_reset) $success = $flash_reset['message'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="auto">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — CV Builder Pro</title>

  <!-- SC Brand Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Poppins:wght@300;400;500;600&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600&display=swap" rel="stylesheet">

  <style>
    /* ── SC Brand Tokens ──────────────────────────────────── */
    :root {
      --sc-red:       #eb1b26;
      --sc-dark-red:  #a40e16;
      --sc-black:     #000000;
      --sc-grey:      #cccccc;
      --sc-white:     #ffffff;

      /* Golden ratio scale (base 14px) */
      --base:     14px;
      --scale-sm: 11.33px;   /* base / 1.236 */
      --scale-md: 22.65px;   /* base × 1.618 */
      --scale-lg: 42px;      /* base × 3     */

      /* Light mode surfaces */
      --bg:           #f5f5f5;
      --bg-card:      #ffffff;
      --bg-input:     #ffffff;
      --border:       #e0e0e0;
      --text-primary: #0a0a0a;
      --text-muted:   #6b6b6b;
      --shadow:       0 2px 24px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.04);
      --input-shadow: 0 0 0 3px rgba(235,27,38,.15);
    }

    /* Dark mode surfaces */
    @media (prefers-color-scheme: dark) {
      :root:not([data-theme="light"]) {
        --bg:           #0e0e0e;
        --bg-card:      #1a1a1a;
        --bg-input:     #242424;
        --border:       #2e2e2e;
        --text-primary: #f0f0f0;
        --text-muted:   #888888;
        --shadow:       0 2px 24px rgba(0,0,0,.4), 0 1px 4px rgba(0,0,0,.2);
      }
    }
    [data-theme="dark"] {
      --bg:           #0e0e0e;
      --bg-card:      #1a1a1a;
      --bg-input:     #242424;
      --border:       #2e2e2e;
      --text-primary: #f0f0f0;
      --text-muted:   #888888;
      --shadow:       0 2px 24px rgba(0,0,0,.4), 0 1px 4px rgba(0,0,0,.2);
    }
    [data-theme="light"] {
      --bg:           #f5f5f5;
      --bg-card:      #ffffff;
      --bg-input:     #ffffff;
      --border:       #e0e0e0;
      --text-primary: #0a0a0a;
      --text-muted:   #6b6b6b;
      --shadow:       0 2px 24px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.04);
    }

    /* ── Reset ───────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Poppins', sans-serif;
      font-size: var(--base);
      background: var(--bg);
      color: var(--text-primary);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background .3s, color .3s;
      position: relative;
      overflow: hidden;
    }

    /* ── Background accent geometry ─────────────────────── */
    body::before {
      content: '';
      position: fixed;
      top: -120px;
      right: -120px;
      width: 420px;
      height: 420px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(235,27,38,.12) 0%, transparent 70%);
      pointer-events: none;
    }
    body::after {
      content: '';
      position: fixed;
      bottom: -80px;
      left: -80px;
      width: 300px;
      height: 300px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(164,14,22,.08) 0%, transparent 70%);
      pointer-events: none;
    }

    /* ── Theme toggle ────────────────────────────────────── */
    .theme-toggle {
      position: fixed;
      top: 20px;
      right: 20px;
      width: 38px;
      height: 38px;
      border-radius: 50%;
      border: 1px solid var(--border);
      background: var(--bg-card);
      color: var(--text-muted);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      transition: background .2s, border-color .2s, transform .2s;
      z-index: 100;
    }
    .theme-toggle:hover {
      border-color: var(--sc-red);
      color: var(--sc-red);
      transform: rotate(20deg);
    }

    /* ── Card ────────────────────────────────────────────── */
    .card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 16px;
      box-shadow: var(--shadow);
      width: 100%;
      max-width: 420px;
      padding: 40px 36px 36px;
      position: relative;
      animation: slideUp .45s cubic-bezier(.22,.68,0,1.2) both;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(28px) scale(.97); }
      to   { opacity: 1; transform: translateY(0)    scale(1);   }
    }

    /* ── Logo ────────────────────────────────────────────── */
    .logo-wrap {
      display: flex;
      justify-content: center;
      margin-bottom: 28px;
      animation: fadeIn .5s .1s both;
    }
    .logo-wrap img {
      height: 28px;
      width: auto;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-8px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Heading ─────────────────────────────────────────── */
    .card-title {
      font-family: 'Anton', sans-serif;
      font-size: var(--scale-lg);
      text-transform: uppercase;
      letter-spacing: .03em;
      color: var(--text-primary);
      line-height: 1;
      text-align: center;
      animation: fadeIn .5s .15s both;
    }
    .card-sub {
      font-size: var(--scale-sm);
      color: var(--text-muted);
      text-align: center;
      margin-top: 6px;
      text-transform: uppercase;
      letter-spacing: .07em;
      font-weight: 300;
      animation: fadeIn .5s .2s both;
    }

    /* ── Divider ─────────────────────────────────────────── */
    .divider {
      height: 1px;
      background: var(--border);
      margin: 24px 0;
      position: relative;
      animation: fadeIn .5s .25s both;
    }

    /* ── Alert ───────────────────────────────────────────── */
    .alert {
      border-radius: 8px;
      padding: 10px 14px;
      font-size: var(--scale-sm);
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
      animation: fadeIn .3s both;
    }
    .alert-error   { background: rgba(235,27,38,.08); color: #c0111b; border: 1px solid rgba(235,27,38,.2); }
    .alert-success { background: rgba(5,150,105,.08); color: #065f46; border: 1px solid rgba(5,150,105,.2); }
    [data-theme="dark"] .alert-error   { color: #f87171; }
    [data-theme="dark"] .alert-success { color: #6ee7b7; }

    /* ── Form ────────────────────────────────────────────── */
    .form-group {
      margin-bottom: 16px;
      animation: fadeIn .5s both;
    }
    .form-group:nth-child(1) { animation-delay: .3s; }
    .form-group:nth-child(2) { animation-delay: .35s; }
    .form-group:nth-child(3) { animation-delay: .4s; }

    label {
      display: block;
      font-size: var(--scale-sm);
      font-weight: 500;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: .06em;
      margin-bottom: 6px;
    }

    .input-wrap {
      position: relative;
    }
    .input-wrap .icon {
      position: absolute;
      left: 13px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      font-size: 15px;
      pointer-events: none;
    }

    input[type="email"],
    input[type="password"],
    input[type="text"] {
      width: 100%;
      padding: 11px 14px 11px 38px;
      background: var(--bg-input);
      border: 1px solid var(--border);
      border-radius: 8px;
      font-family: 'Poppins', sans-serif;
      font-size: var(--base);
      color: var(--text-primary);
      transition: border-color .2s, box-shadow .2s;
      outline: none;
    }
    input:focus {
      border-color: var(--sc-red);
      box-shadow: var(--input-shadow);
    }
    input::placeholder { color: var(--text-muted); opacity: .6; }

    /* Eye toggle for password */
    .eye-btn {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: var(--text-muted);
      font-size: 15px;
      padding: 2px 4px;
      transition: color .2s;
    }
    .eye-btn:hover { color: var(--sc-red); }

    /* ── Checkbox row ────────────────────────────────────── */
    .check-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
      animation: fadeIn .5s .42s both;
    }
    .check-label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: var(--scale-sm);
      color: var(--text-muted);
      cursor: pointer;
      user-select: none;
    }
    .check-label input[type="checkbox"] {
      width: 15px;
      height: 15px;
      accent-color: var(--sc-red);
      cursor: pointer;
      padding: 0;
    }
    .forgot-link {
      font-size: var(--scale-sm);
      color: var(--sc-red);
      text-decoration: none;
      transition: opacity .2s;
    }
    .forgot-link:hover { opacity: .7; }

    /* ── Submit button ───────────────────────────────────── */
    .btn-primary {
      width: 100%;
      padding: 13px;
      background: var(--sc-red);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-family: 'Anton', sans-serif;
      font-size: 18px;
      letter-spacing: .05em;
      text-transform: uppercase;
      cursor: pointer;
      transition: background .2s, transform .15s, box-shadow .2s;
      animation: fadeIn .5s .45s both;
      position: relative;
      overflow: hidden;
    }
    .btn-primary::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,.12) 0%, transparent 60%);
      pointer-events: none;
    }
    .btn-primary:hover {
      background: var(--sc-dark-red);
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(235,27,38,.35);
    }
    .btn-primary:active { transform: translateY(0); }
    .btn-primary:disabled { opacity: .6; cursor: not-allowed; transform: none; }

    /* Loading spinner inside button */
    .btn-primary .spinner {
      display: none;
      width: 16px;
      height: 16px;
      border: 2px solid rgba(255,255,255,.4);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin .6s linear infinite;
      margin: 0 auto;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Register link ───────────────────────────────────── */
    .register-link {
      text-align: center;
      margin-top: 20px;
      font-size: var(--scale-sm);
      color: var(--text-muted);
      animation: fadeIn .5s .5s both;
    }
    .register-link a {
      color: var(--sc-red);
      text-decoration: none;
      font-weight: 500;
      transition: opacity .2s;
    }
    .register-link a:hover { opacity: .7; }

    /* ── Red accent bar (top of card) ────────────────────── */
    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 36px;
      right: 36px;
      height: 3px;
      background: linear-gradient(90deg, var(--sc-dark-red), var(--sc-red));
      border-radius: 0 0 3px 3px;
    }

    /* ── Responsive ──────────────────────────────────────── */
    @media (max-width: 460px) {
      .card { margin: 16px; padding: 32px 24px 28px; }
      .card-title { font-size: 34px; }
    }
  </style>
</head>
<body>

  <!-- Theme toggle -->
  <button class="theme-toggle" id="themeBtn" title="Toggle theme" aria-label="Toggle dark/light mode">
    <span id="themeIcon">🌙</span>
  </button>

  <div class="card" role="main">

    <!-- Logo -->
    <div class="logo-wrap">
      <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://shortcircuit.company/assets/img/logo-dark.svg" id="logoSrcDark">
        <img src="https://shortcircuit.company/assets/img/logo.svg" id="logoImg" alt="Short Circuit Company" height="28">
      </picture>
    </div>

    <!-- Title -->
    <h1 class="card-title">Sign In</h1>
    <p class="card-sub">CV Builder Pro</p>

    <div class="divider"></div>

    <!-- Flash alerts -->
    <?php if ($error): ?>
      <div class="alert alert-error" role="alert">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success" role="alert">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <!-- Login form -->
    <form method="POST" action="" id="loginForm" novalidate>
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="email">Email</label>
        <div class="input-wrap">
          <span class="icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </span>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="you@example.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            autocomplete="email"
            required
            autofocus
          >
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrap">
          <span class="icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </span>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          >
          <button type="button" class="eye-btn" id="eyeBtn" aria-label="Show/hide password">
            <svg id="eyeOpen" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg id="eyeClosed" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
      </div>

      <div class="check-row">
        <label class="check-label">
          <input type="checkbox" name="remember" id="remember" <?= isset($_POST['remember']) ? 'checked' : '' ?>>
          Remember me
        </label>
        <a href="<?= APP_URL ?>/auth/forgot.php" class="forgot-link">Forgot password?</a>
      </div>

      <button type="submit" class="btn-primary" id="submitBtn">
        <span id="btnText">Sign In</span>
        <div class="spinner" id="btnSpinner"></div>
      </button>
    </form>

    <p class="register-link">
      Don't have an account? <a href="<?= APP_URL ?>/auth/register.php">Create one →</a>
    </p>

  </div><!-- /.card -->

  <script>
    // ── Dark/light theme ───────────────────────────────────────
    const root     = document.documentElement;
    const themeBtn = document.getElementById('themeBtn');
    const themeIcon= document.getElementById('themeIcon');
    const logoImg  = document.getElementById('logoImg');

    const LIGHT_LOGO = 'https://shortcircuit.company/assets/img/logo.svg';
    const DARK_LOGO  = 'https://shortcircuit.company/assets/img/logo-dark.svg';

    function getSystemTheme() {
      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function applyTheme(theme) {
      root.setAttribute('data-theme', theme);
      themeIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
      logoImg.src = theme === 'dark' ? DARK_LOGO : LIGHT_LOGO;
      localStorage.setItem('cvb_theme', theme);
    }

    // Init — saved pref > system
    const saved = localStorage.getItem('cvb_theme');
    applyTheme(saved || getSystemTheme());

    themeBtn.addEventListener('click', () => {
      const current = root.getAttribute('data-theme');
      applyTheme(current === 'dark' ? 'light' : 'dark');
    });

    // Sync if system changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
      if (!localStorage.getItem('cvb_theme')) {
        applyTheme(e.matches ? 'dark' : 'light');
      }
    });

    // ── Password eye toggle ────────────────────────────────────
    const pwInput    = document.getElementById('password');
    const eyeBtn     = document.getElementById('eyeBtn');
    const eyeOpen    = document.getElementById('eyeOpen');
    const eyeClosed  = document.getElementById('eyeClosed');

    eyeBtn.addEventListener('click', () => {
      const show = pwInput.type === 'password';
      pwInput.type = show ? 'text' : 'password';
      eyeOpen.style.display   = show ? 'none'  : '';
      eyeClosed.style.display = show ? ''      : 'none';
    });

    // ── Submit loading state ───────────────────────────────────
    document.getElementById('loginForm').addEventListener('submit', function() {
      const btn     = document.getElementById('submitBtn');
      const btnText = document.getElementById('btnText');
      const spinner = document.getElementById('btnSpinner');
      btn.disabled        = true;
      btnText.style.display  = 'none';
      spinner.style.display  = 'block';
    });
  </script>
</body>
</html>
