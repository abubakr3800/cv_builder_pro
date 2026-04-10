<?php
// ============================================================
//  CV Builder Pro — Reset Password Page
//  Author  : Ahmed Mohamed Abubakr
//  Site    : https://abubakr.rf.gd/
//  Phase   : 2 — Auth + UI Shell
// ============================================================

require_once __DIR__ . '/../includes/bootstrap.php';

if (auth_check()) {
    redirect('pages/dashboard.php');
}

$error      = '';
$success    = '';
$tokenValid = false;

// ── Validate token from GET params ───────────────────────────
$token = trim($_GET['token'] ?? '');
$email = strtolower(trim($_GET['email'] ?? ''));

if (empty($token) || empty($email)) {
    $error = 'Invalid or missing reset link. Please request a new one.';
} else {
    $row = Database::fetchOne(
        'SELECT * FROM password_resets
         WHERE email = ? AND token = ? AND used = 0 AND expires_at > NOW()
         LIMIT 1',
        [$email, $token]
    );

    if (!$row) {
        $error = 'This reset link is invalid or has expired. Please request a new one.';
    } else {
        $tokenValid = true;
    }
}

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {

    csrf_abort();

    $newPassword = $_POST['password']         ?? '';
    $confirm     = $_POST['password_confirm'] ?? '';

    if (empty($newPassword) || empty($confirm)) {
        $error = 'Both password fields are required.';

    } elseif (mb_strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters.';

    } elseif (!preg_match('/[A-Z]/', $newPassword)) {
        $error = 'Password must contain at least one uppercase letter.';

    } elseif (!preg_match('/[0-9]/', $newPassword)) {
        $error = 'Password must contain at least one number.';

    } elseif ($newPassword !== $confirm) {
        $error = 'Passwords do not match.';

    } else {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        // Update password
        $affected = Database::execute(
            'UPDATE users SET password = ?, remember_token = NULL, updated_at = NOW() WHERE email = ?',
            [$hashed, $email]
        );

        if ($affected) {
            // Mark token as used
            Database::execute(
                'UPDATE password_resets SET used = 1 WHERE email = ? AND token = ?',
                [$email, $token]
            );

            flash('password_reset', 'Password updated successfully. Please sign in with your new password.', 'success');
            redirect('auth/login.php');
        } else {
            $error = 'Could not update password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="auto">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password — CV Builder Pro</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

  <style>
    :root {
      --sc-red:       #eb1b26;
      --sc-dark-red:  #a40e16;
      --base:         14px;
      --scale-sm:     11.33px;
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
        --bg:#0e0e0e; --bg-card:#1a1a1a; --bg-input:#242424;
        --border:#2e2e2e; --text-primary:#f0f0f0; --text-muted:#888888;
        --shadow:0 2px 24px rgba(0,0,0,.4),0 1px 4px rgba(0,0,0,.2);
      }
    }
    [data-theme="dark"] {
      --bg:#0e0e0e; --bg-card:#1a1a1a; --bg-input:#242424;
      --border:#2e2e2e; --text-primary:#f0f0f0; --text-muted:#888888;
      --shadow:0 2px 24px rgba(0,0,0,.4),0 1px 4px rgba(0,0,0,.2);
    }
    [data-theme="light"] {
      --bg:#f5f5f5; --bg-card:#ffffff; --bg-input:#ffffff;
      --border:#e0e0e0; --text-primary:#0a0a0a; --text-muted:#6b6b6b;
      --shadow:0 2px 24px rgba(0,0,0,.08),0 1px 4px rgba(0,0,0,.04);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Poppins', sans-serif; font-size: var(--base);
      background: var(--bg); color: var(--text-primary);
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
      transition: background .3s, color .3s; position: relative; overflow: hidden;
    }
    body::before {
      content: ''; position: fixed; top: -120px; right: -120px;
      width: 420px; height: 420px; border-radius: 50%;
      background: radial-gradient(circle, rgba(235,27,38,.12) 0%, transparent 70%);
      pointer-events: none;
    }
    body::after {
      content: ''; position: fixed; bottom: -80px; left: -80px;
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
      from { opacity:0; transform:translateY(28px) scale(.97); }
      to   { opacity:1; transform:translateY(0)    scale(1);   }
    }
    @keyframes fadeIn {
      from { opacity:0; transform:translateY(-8px); }
      to   { opacity:1; transform:translateY(0); }
    }
    .logo-wrap { display:flex; justify-content:center; margin-bottom:28px; animation:fadeIn .5s .1s both; }
    .logo-wrap img { height:28px; width:auto; }

    .icon-illustration {
      width:64px; height:64px; border-radius:50%;
      background:rgba(235,27,38,.08); border:1px solid rgba(235,27,38,.15);
      display:flex; align-items:center; justify-content:center;
      margin:0 auto 20px; animation:fadeIn .5s .15s both;
    }
    .icon-illustration svg { color:var(--sc-red); }

    .card-title {
      font-family:'Anton',sans-serif; font-size:var(--scale-lg);
      text-transform:uppercase; letter-spacing:.03em; color:var(--text-primary);
      line-height:1; text-align:center; animation:fadeIn .5s .15s both;
    }
    .card-sub {
      font-size:var(--scale-sm); color:var(--text-muted); text-align:center;
      margin-top:6px; text-transform:uppercase; letter-spacing:.07em;
      font-weight:300; animation:fadeIn .5s .2s both;
    }
    .divider { height:1px; background:var(--border); margin:24px 0; animation:fadeIn .5s .25s both; }

    .alert {
      border-radius:8px; padding:10px 14px; font-size:var(--scale-sm);
      margin-bottom:18px; display:flex; align-items:flex-start; gap:8px; animation:fadeIn .3s both;
    }
    .alert svg { flex-shrink:0; margin-top:1px; }
    .alert-error   { background:rgba(235,27,38,.08); color:#c0111b; border:1px solid rgba(235,27,38,.2); }
    .alert-success { background:rgba(5,150,105,.08); color:#065f46; border:1px solid rgba(5,150,105,.2); }
    [data-theme="dark"] .alert-error   { color:#f87171; }
    [data-theme="dark"] .alert-success { color:#6ee7b7; }

    .form-group { margin-bottom:16px; animation:fadeIn .5s both; }
    .form-group:nth-child(1) { animation-delay:.3s; }
    .form-group:nth-child(2) { animation-delay:.35s; }

    label {
      display:block; font-size:var(--scale-sm); font-weight:500;
      color:var(--text-muted); text-transform:uppercase;
      letter-spacing:.06em; margin-bottom:6px;
    }
    .input-wrap { position:relative; }
    .input-wrap .icon {
      position:absolute; left:13px; top:50%; transform:translateY(-50%);
      color:var(--text-muted); font-size:15px; pointer-events:none;
    }
    input[type="password"] {
      width:100%; padding:11px 40px 11px 38px;
      background:var(--bg-input); border:1px solid var(--border);
      border-radius:8px; font-family:'Poppins',sans-serif;
      font-size:var(--base); color:var(--text-primary);
      transition:border-color .2s,box-shadow .2s; outline:none;
    }
    input:focus { border-color:var(--sc-red); box-shadow:var(--input-shadow); }
    input::placeholder { color:var(--text-muted); opacity:.6; }
    .eye-btn {
      position:absolute; right:12px; top:50%; transform:translateY(-50%);
      background:none; border:none; cursor:pointer; color:var(--text-muted);
      font-size:15px; padding:2px 4px; transition:color .2s;
    }
    .eye-btn:hover { color:var(--sc-red); }

    /* Password strength meter */
    .strength-wrap { margin-top:8px; animation:fadeIn .3s both; }
    .strength-bar {
      height:4px; border-radius:2px; background:var(--border);
      overflow:hidden; margin-bottom:4px;
    }
    .strength-fill { height:100%; width:0%; border-radius:2px; transition:width .3s, background .3s; }
    .strength-label { font-size:10px; color:var(--text-muted); }

    /* Match hint */
    .match-hint { font-size:10px; margin-top:5px; display:flex; align-items:center; gap:5px; }
    .match-ok  { color:#059669; }
    .match-err { color:#dc2626; }

    .btn-primary {
      width:100%; padding:13px; background:var(--sc-red); color:#fff;
      border:none; border-radius:8px; font-family:'Anton',sans-serif;
      font-size:18px; letter-spacing:.05em; text-transform:uppercase;
      cursor:pointer; transition:background .2s,transform .15s,box-shadow .2s;
      animation:fadeIn .5s .42s both; position:relative; overflow:hidden;
    }
    .btn-primary::after {
      content:''; position:absolute; inset:0;
      background:linear-gradient(135deg,rgba(255,255,255,.12) 0%,transparent 60%);
      pointer-events:none;
    }
    .btn-primary:hover { background:var(--sc-dark-red); transform:translateY(-1px); box-shadow:0 6px 20px rgba(235,27,38,.35); }
    .btn-primary:active { transform:translateY(0); }
    .btn-primary:disabled { opacity:.6; cursor:not-allowed; transform:none; }
    .btn-primary .spinner {
      display:none; width:16px; height:16px;
      border:2px solid rgba(255,255,255,.4); border-top-color:#fff;
      border-radius:50%; animation:spin .6s linear infinite; margin:0 auto;
    }
    @keyframes spin { to { transform:rotate(360deg); } }

    .back-link {
      display:flex; align-items:center; justify-content:center; gap:6px;
      margin-top:20px; font-size:var(--scale-sm); color:var(--text-muted);
      text-decoration:none; transition:color .2s; animation:fadeIn .5s .45s both;
    }
    .back-link:hover { color:var(--sc-red); }

    /* Invalid token state */
    .error-state { text-align:center; padding:20px 0; }
    .error-state p { font-size:var(--scale-sm); color:var(--text-muted); line-height:1.7; margin-bottom:20px; }

    @media (max-width:460px) {
      .card { margin:16px; padding:32px 24px 28px; }
      .card-title { font-size:34px; }
    }
  </style>
</head>
<body>

  <button class="theme-toggle" id="themeBtn" aria-label="Toggle dark/light mode">
    <span id="themeIcon">🌙</span>
  </button>

  <div class="card" role="main">

    <div class="logo-wrap">
      <img src="https://shortcircuit.company/assets/img/logo.svg" id="logoImg" alt="Short Circuit Company" height="28">
    </div>

    <div class="icon-illustration">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        <polyline points="9 12 11 14 15 10"/>
      </svg>
    </div>

    <h1 class="card-title">New Pass</h1>
    <p class="card-sub">Choose a strong password</p>

    <div class="divider"></div>

    <?php if ($error): ?>
      <div class="alert alert-error" role="alert">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($tokenValid): ?>
    <form method="POST" action="?token=<?= urlencode($token) ?>&email=<?= urlencode($email) ?>" id="resetForm" novalidate>
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="password">New Password</label>
        <div class="input-wrap">
          <span class="icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </span>
          <input type="password" id="password" name="password" placeholder="Min 8 chars" autocomplete="new-password" required>
          <button type="button" class="eye-btn" id="eyeBtn1" aria-label="Show/hide password">
            <svg id="eye1Open" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg id="eye1Closed" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
        <div class="strength-wrap" id="strengthWrap" style="display:none">
          <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
          <span class="strength-label" id="strengthLabel"></span>
        </div>
      </div>

      <div class="form-group">
        <label for="password_confirm">Confirm Password</label>
        <div class="input-wrap">
          <span class="icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </span>
          <input type="password" id="password_confirm" name="password_confirm" placeholder="Repeat password" autocomplete="new-password" required>
          <button type="button" class="eye-btn" id="eyeBtn2" aria-label="Show/hide confirm password">
            <svg id="eye2Open" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg id="eye2Closed" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
        <div class="match-hint" id="matchHint" style="display:none"></div>
      </div>

      <button type="submit" class="btn-primary" id="submitBtn">
        <span id="btnText">Set New Password</span>
        <div class="spinner" id="btnSpinner"></div>
      </button>
    </form>

    <?php else: ?>
    <div class="error-state">
      <p>This reset link is invalid or has expired.<br>Reset links are valid for 1 hour.</p>
      <a href="<?= APP_URL ?>/auth/forgot.php" class="btn-primary" style="display:block;text-decoration:none;text-align:center">Request New Link</a>
    </div>
    <?php endif; ?>

    <a href="<?= APP_URL ?>/auth/login.php" class="back-link">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      Back to Sign In
    </a>
  </div>

  <script>
    // ── Theme ───────────────────────────────────────────────
    const root = document.documentElement;
    const themeBtn = document.getElementById('themeBtn');
    const themeIcon = document.getElementById('themeIcon');
    const logoImg = document.getElementById('logoImg');
    const LIGHT_LOGO = 'https://shortcircuit.company/assets/img/logo.svg';
    const DARK_LOGO  = 'https://shortcircuit.company/assets/img/logo-dark.svg';
    function applyTheme(t) {
      root.setAttribute('data-theme', t);
      themeIcon.textContent = t === 'dark' ? '☀️' : '🌙';
      logoImg.src = t === 'dark' ? DARK_LOGO : LIGHT_LOGO;
      localStorage.setItem('cvb_theme', t);
    }
    applyTheme(localStorage.getItem('cvb_theme') ||
      (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
    themeBtn.addEventListener('click', () => applyTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'));

    // ── Password strength ────────────────────────────────────
    const pwInput     = document.getElementById('password');
    const strengthWrap= document.getElementById('strengthWrap');
    const strengthFill= document.getElementById('strengthFill');
    const strengthLabel=document.getElementById('strengthLabel');

    const levels = [
      { label:'Too short', color:'#dc2626', w:'10%' },
      { label:'Weak',      color:'#f97316', w:'30%' },
      { label:'Fair',      color:'#eab308', w:'55%' },
      { label:'Good',      color:'#22c55e', w:'75%' },
      { label:'Strong',    color:'#16a34a', w:'100%' },
    ];

    function scorePassword(p) {
      if (p.length < 8) return 0;
      let s = 1;
      if (p.length >= 12) s++;
      if (/[A-Z]/.test(p)) s++;
      if (/[0-9]/.test(p)) s++;
      if (/[^A-Za-z0-9]/.test(p)) s++;
      return Math.min(4, s);
    }

    if (pwInput) {
      pwInput.addEventListener('input', function() {
        const val = this.value;
        if (!val) { strengthWrap.style.display = 'none'; return; }
        strengthWrap.style.display = 'block';
        const lvl = levels[scorePassword(val)];
        strengthFill.style.width      = lvl.w;
        strengthFill.style.background = lvl.color;
        strengthLabel.textContent     = lvl.label;
        strengthLabel.style.color     = lvl.color;
        checkMatch();
      });
    }

    // ── Match hint ───────────────────────────────────────────
    const confirmInput = document.getElementById('password_confirm');
    const matchHint    = document.getElementById('matchHint');

    function checkMatch() {
      if (!confirmInput || !confirmInput.value) { matchHint.style.display = 'none'; return; }
      matchHint.style.display = 'flex';
      if (pwInput.value === confirmInput.value) {
        matchHint.className = 'match-hint match-ok';
        matchHint.innerHTML = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Passwords match';
      } else {
        matchHint.className = 'match-hint match-err';
        matchHint.innerHTML = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Passwords do not match';
      }
    }
    if (confirmInput) confirmInput.addEventListener('input', checkMatch);

    // ── Eye toggles ──────────────────────────────────────────
    function makeEyeToggle(inputId, btnId, openId, closedId) {
      const inp = document.getElementById(inputId);
      const btn = document.getElementById(btnId);
      if (!btn) return;
      btn.addEventListener('click', () => {
        const show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        document.getElementById(openId).style.display   = show ? 'none' : '';
        document.getElementById(closedId).style.display = show ? ''     : 'none';
      });
    }
    makeEyeToggle('password', 'eyeBtn1', 'eye1Open', 'eye1Closed');
    makeEyeToggle('password_confirm', 'eyeBtn2', 'eye2Open', 'eye2Closed');

    // ── Submit guard + spinner ───────────────────────────────
    const resetForm = document.getElementById('resetForm');
    if (resetForm) {
      resetForm.addEventListener('submit', function(e) {
        if (pwInput.value !== confirmInput.value) {
          e.preventDefault();
          checkMatch();
          confirmInput.focus();
          return;
        }
        const btn = document.getElementById('submitBtn');
        document.getElementById('btnText').style.display   = 'none';
        document.getElementById('btnSpinner').style.display = 'block';
        btn.disabled = true;
      });
    }
  </script>
</body>
</html>
