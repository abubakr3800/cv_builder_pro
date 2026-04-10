<?php
// ============================================================
//  CV Builder Pro — Forgot Password Page
//  Author  : Ahmed Mohamed Abubakr
//  Site    : https://abubakr.rf.gd/
//  Phase   : 2 — Auth + UI Shell
// ============================================================

require_once __DIR__ . '/../includes/bootstrap.php';

if (auth_check()) {
    redirect('pages/dashboard.php');
}

$error   = '';
$success = '';

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_abort();

    $email = strtolower(trim(post('email')));

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $user = Database::fetchOne(
            'SELECT id, name, email FROM users WHERE email = ? AND is_active = 1 LIMIT 1',
            [$email]
        );

        // Always show success to prevent user enumeration
        if ($user) {
            // Invalidate old tokens for this email
            Database::execute(
                'DELETE FROM password_resets WHERE email = ?',
                [$email]
            );

            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            Database::insert(
                'INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)',
                [$email, $token, $expiresAt]
            );

            // In production: send email with reset link
            // mail($email, 'Reset your CV Builder Pro password',
            //   APP_URL . '/auth/reset.php?token=' . $token . '&email=' . urlencode($email)
            // );

            // Dev: store token in flash so you can follow the link
            if (APP_ENV === 'development') {
                flash('dev_reset_token',
                    'DEV — Reset link: ' . APP_URL . '/auth/reset.php?token=' . $token . '&email=' . urlencode($email),
                    'info'
                );
            }
        }

        // Always redirect to the same success message
        flash('forgot_sent', 'If that email is registered, we\'ve sent a reset link. Check your inbox.', 'success');
        redirect('auth/forgot.php');
    }
}

$flash_sent = flash_get('forgot_sent');
if ($flash_sent) $success = $flash_sent['message'];

$flash_dev = flash_get('dev_reset_token');
$dev_link  = $flash_dev ? $flash_dev['message'] : '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="auto">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password — CV Builder Pro</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

  <style>
    :root {
      --sc-red:       #eb1b26;
      --sc-dark-red:  #a40e16;
      --base:         14px;
      --scale-sm:     11.33px;
      --scale-md:     22.65px;
      --scale-lg:     42px;
      --bg:           #f5f5f5;
      --bg-card:      #ffffff;
      --bg-input:     #ffffff;
      --border:       #e0e0e0;
      --text-primary: #0a0a0a;
      --text-muted:   #6b6b6b;
      --shadow:       0 2px 24px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.04);
      --input-shadow: 0 0 0 3px rgba(235,27,38,.15);
    }
    @media (prefers-color-scheme: dark) {
      :root:not([data-theme="light"]) {
        --bg: #0e0e0e; --bg-card: #1a1a1a; --bg-input: #242424;
        --border: #2e2e2e; --text-primary: #f0f0f0; --text-muted: #888888;
        --shadow: 0 2px 24px rgba(0,0,0,.4), 0 1px 4px rgba(0,0,0,.2);
      }
    }
    [data-theme="dark"] {
      --bg: #0e0e0e; --bg-card: #1a1a1a; --bg-input: #242424;
      --border: #2e2e2e; --text-primary: #f0f0f0; --text-muted: #888888;
      --shadow: 0 2px 24px rgba(0,0,0,.4), 0 1px 4px rgba(0,0,0,.2);
    }
    [data-theme="light"] {
      --bg: #f5f5f5; --bg-card: #ffffff; --bg-input: #ffffff;
      --border: #e0e0e0; --text-primary: #0a0a0a; --text-muted: #6b6b6b;
      --shadow: 0 2px 24px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.04);
    }

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
    body::before {
      content: '';
      position: fixed; top: -120px; right: -120px;
      width: 420px; height: 420px; border-radius: 50%;
      background: radial-gradient(circle, rgba(235,27,38,.12) 0%, transparent 70%);
      pointer-events: none;
    }
    body::after {
      content: '';
      position: fixed; bottom: -80px; left: -80px;
      width: 300px; height: 300px; border-radius: 50%;
      background: radial-gradient(circle, rgba(164,14,22,.08) 0%, transparent 70%);
      pointer-events: none;
    }

    .theme-toggle {
      position: fixed; top: 20px; right: 20px;
      width: 38px; height: 38px; border-radius: 50%;
      border: 1px solid var(--border); background: var(--bg-card);
      color: var(--text-muted); cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      font-size: 16px; transition: background .2s, border-color .2s, transform .2s; z-index: 100;
    }
    .theme-toggle:hover { border-color: var(--sc-red); color: var(--sc-red); transform: rotate(20deg); }

    .card {
      background: var(--bg-card); border: 1px solid var(--border);
      border-radius: 16px; box-shadow: var(--shadow);
      width: 100%; max-width: 420px; padding: 40px 36px 36px;
      position: relative; animation: slideUp .45s cubic-bezier(.22,.68,0,1.2) both;
    }
    .card::before {
      content: ''; position: absolute; top: 0; left: 36px; right: 36px;
      height: 3px; background: linear-gradient(90deg, var(--sc-dark-red), var(--sc-red));
      border-radius: 0 0 3px 3px;
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(28px) scale(.97); }
      to   { opacity: 1; transform: translateY(0)    scale(1);   }
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-8px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .logo-wrap { display: flex; justify-content: center; margin-bottom: 28px; animation: fadeIn .5s .1s both; }
    .logo-wrap img { height: 28px; width: auto; }

    /* Icon illustration */
    .icon-illustration {
      width: 64px; height: 64px; border-radius: 50%;
      background: rgba(235,27,38,.08); border: 1px solid rgba(235,27,38,.15);
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px; animation: fadeIn .5s .15s both;
    }
    .icon-illustration svg { color: var(--sc-red); }

    .card-title {
      font-family: 'Anton', sans-serif; font-size: var(--scale-lg);
      text-transform: uppercase; letter-spacing: .03em; color: var(--text-primary);
      line-height: 1; text-align: center; animation: fadeIn .5s .15s both;
    }
    .card-sub {
      font-size: var(--scale-sm); color: var(--text-muted); text-align: center;
      margin-top: 6px; text-transform: uppercase; letter-spacing: .07em;
      font-weight: 300; animation: fadeIn .5s .2s both;
    }
    .divider { height: 1px; background: var(--border); margin: 24px 0; animation: fadeIn .5s .25s both; }

    .alert {
      border-radius: 8px; padding: 10px 14px; font-size: var(--scale-sm);
      margin-bottom: 18px; display: flex; align-items: flex-start; gap: 8px; animation: fadeIn .3s both;
    }
    .alert svg { flex-shrink: 0; margin-top: 1px; }
    .alert-error   { background: rgba(235,27,38,.08); color: #c0111b; border: 1px solid rgba(235,27,38,.2); }
    .alert-success { background: rgba(5,150,105,.08); color: #065f46; border: 1px solid rgba(5,150,105,.2); }
    .alert-info    { background: rgba(59,130,246,.08); color: #1e40af; border: 1px solid rgba(59,130,246,.2); word-break: break-all; }
    [data-theme="dark"] .alert-error   { color: #f87171; }
    [data-theme="dark"] .alert-success { color: #6ee7b7; }
    [data-theme="dark"] .alert-info    { color: #93c5fd; }

    .form-group { margin-bottom: 16px; animation: fadeIn .5s .3s both; }
    label {
      display: block; font-size: var(--scale-sm); font-weight: 500;
      color: var(--text-muted); text-transform: uppercase;
      letter-spacing: .06em; margin-bottom: 6px;
    }
    .input-wrap { position: relative; }
    .input-wrap .icon {
      position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
      color: var(--text-muted); font-size: 15px; pointer-events: none;
    }
    input[type="email"] {
      width: 100%; padding: 11px 14px 11px 38px;
      background: var(--bg-input); border: 1px solid var(--border);
      border-radius: 8px; font-family: 'Poppins', sans-serif;
      font-size: var(--base); color: var(--text-primary);
      transition: border-color .2s, box-shadow .2s; outline: none;
    }
    input:focus { border-color: var(--sc-red); box-shadow: var(--input-shadow); }
    input::placeholder { color: var(--text-muted); opacity: .6; }

    .helper-text {
      font-size: var(--scale-sm); color: var(--text-muted);
      line-height: 1.6; margin-bottom: 20px;
      animation: fadeIn .5s .28s both;
    }

    .btn-primary {
      width: 100%; padding: 13px; background: var(--sc-red); color: #fff;
      border: none; border-radius: 8px; font-family: 'Anton', sans-serif;
      font-size: 18px; letter-spacing: .05em; text-transform: uppercase;
      cursor: pointer; transition: background .2s, transform .15s, box-shadow .2s;
      animation: fadeIn .5s .35s both; position: relative; overflow: hidden;
    }
    .btn-primary::after {
      content: ''; position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,.12) 0%, transparent 60%);
      pointer-events: none;
    }
    .btn-primary:hover { background: var(--sc-dark-red); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(235,27,38,.35); }
    .btn-primary:active { transform: translateY(0); }
    .btn-primary:disabled { opacity: .6; cursor: not-allowed; transform: none; }
    .btn-primary .spinner {
      display: none; width: 16px; height: 16px;
      border: 2px solid rgba(255,255,255,.4); border-top-color: #fff;
      border-radius: 50%; animation: spin .6s linear infinite; margin: 0 auto;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .back-link {
      display: flex; align-items: center; justify-content: center;
      gap: 6px; margin-top: 20px; font-size: var(--scale-sm);
      color: var(--text-muted); text-decoration: none;
      transition: color .2s; animation: fadeIn .5s .4s both;
    }
    .back-link:hover { color: var(--sc-red); }

    @media (max-width: 460px) {
      .card { margin: 16px; padding: 32px 24px 28px; }
      .card-title { font-size: 34px; }
    }
  </style>
</head>
<body>

  <button class="theme-toggle" id="themeBtn" title="Toggle theme" aria-label="Toggle dark/light mode">
    <span id="themeIcon">🌙</span>
  </button>

  <div class="card" role="main">

    <div class="logo-wrap">
      <img src="https://shortcircuit.company/assets/img/logo.svg" id="logoImg" alt="Short Circuit Company" height="28">
    </div>

    <div class="icon-illustration">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        <circle cx="12" cy="16" r="1" fill="currentColor"/>
      </svg>
    </div>

    <h1 class="card-title">Reset</h1>
    <p class="card-sub">Password Recovery</p>

    <div class="divider"></div>

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

    <?php if ($dev_link && APP_ENV === 'development'): ?>
      <div class="alert alert-info" role="alert">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="8"/><line x1="12" y1="12" x2="12" y2="16"/></svg>
        <?= htmlspecialchars($dev_link) ?>
      </div>
    <?php endif; ?>

    <?php if (!$success): ?>
      <p class="helper-text">
        Enter the email associated with your account and we'll send you a link to reset your password.
      </p>

      <form method="POST" action="" id="forgotForm" novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
          <label for="email">Email Address</label>
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

        <button type="submit" class="btn-primary" id="submitBtn">
          <span id="btnText">Send Reset Link</span>
          <div class="spinner" id="btnSpinner"></div>
        </button>
      </form>
    <?php endif; ?>

    <a href="<?= APP_URL ?>/auth/login.php" class="back-link">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      Back to Sign In
    </a>

  </div>

  <script>
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
    const saved = localStorage.getItem('cvb_theme');
    applyTheme(saved || getSystemTheme());
    themeBtn.addEventListener('click', () => {
      applyTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    });
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
      if (!localStorage.getItem('cvb_theme')) applyTheme(e.matches ? 'dark' : 'light');
    });

    const form = document.getElementById('forgotForm');
    if (form) {
      form.addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        document.getElementById('btnText').style.display = 'none';
        document.getElementById('btnSpinner').style.display = 'block';
        btn.disabled = true;
      });
    }
  </script>
</body>
</html>
