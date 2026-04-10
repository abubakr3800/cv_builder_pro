<?php
// ============================================================
//  CV Builder Pro — Account Settings
//  Author  : Ahmed Mohamed Abubakr
//  Site    : https://abubakr.rf.gd/
//  Phase   : 2 — Auth + UI Shell
// ============================================================

$page_title  = 'Account';
$active_page = 'account';

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../services/auth_service.php';
require_once __DIR__ . '/../includes/layout.php';

$user  = auth_user();
$uid   = (int)$user['id'];

// Fresh user row for display (session might be stale)
$dbUser = Database::fetchOne(
    'SELECT id, name, email, avatar, role, created_at FROM users WHERE id = ? LIMIT 1',
    [$uid]
);

$profileMsg  = ['type' => '', 'text' => ''];
$passwordMsg = ['type' => '', 'text' => ''];
$avatarMsg   = ['type' => '', 'text' => ''];

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_abort();

    $action = post('action');

    // ── Update profile ────────────────────────────────────────
    if ($action === 'update_profile') {
        $name  = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
            $profileMsg = ['type' => 'error', 'text' => 'Name must be 2–150 characters.'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $profileMsg = ['type' => 'error', 'text' => 'Invalid email address.'];
        } else {
            // Check email uniqueness (exclude self)
            $taken = Database::fetchOne(
                'SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1',
                [$email, $uid]
            );
            if ($taken) {
                $profileMsg = ['type' => 'error', 'text' => 'That email is already in use.'];
            } else {
                Database::execute(
                    'UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?',
                    [$name, $email, $uid]
                );
                // Update session
                $_SESSION['user']['name']  = $name;
                $_SESSION['user']['email'] = $email;
                $dbUser['name']  = $name;
                $dbUser['email'] = $email;
                $profileMsg = ['type' => 'success', 'text' => 'Profile updated successfully.'];
            }
        }
    }

    // ── Change password ───────────────────────────────────────
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new !== $confirm) {
            $passwordMsg = ['type' => 'error', 'text' => 'New passwords do not match.'];
        } else {
            $result = auth_change_password($uid, $current, $new);
            $passwordMsg = [
                'type' => $result['success'] ? 'success' : 'error',
                'text' => $result['message'],
            ];
        }
    }

    // ── Upload avatar ─────────────────────────────────────────
    if ($action === 'upload_avatar' && isset($_FILES['avatar'])) {
        $file = $_FILES['avatar'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $avatarMsg = ['type' => 'error', 'text' => 'Upload failed. Please try again.'];
        } elseif ($file['size'] > MAX_UPLOAD_SIZE) {
            $avatarMsg = ['type' => 'error', 'text' => 'File must be under 5 MB.'];
        } elseif (!in_array($file['type'], ALLOWED_IMG_TYPES)) {
            $avatarMsg = ['type' => 'error', 'text' => 'Only JPEG, PNG, or WebP allowed.'];
        } else {
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'avatar_' . $uid . '_' . time() . '.' . $ext;
            $dest     = UPLOADS_PATH . '/avatars/' . $filename;

            if (!is_dir(UPLOADS_PATH . '/avatars')) {
                mkdir(UPLOADS_PATH . '/avatars', 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // Delete old avatar file if exists
                if (!empty($dbUser['avatar'])) {
                    @unlink(UPLOADS_PATH . '/avatars/' . $dbUser['avatar']);
                }
                Database::execute(
                    'UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?',
                    [$filename, $uid]
                );
                $_SESSION['user']['avatar'] = $filename;
                $dbUser['avatar']           = $filename;
                $avatarMsg = ['type' => 'success', 'text' => 'Avatar updated.'];
            } else {
                $avatarMsg = ['type' => 'error', 'text' => 'Could not save file.'];
            }
        }
    }

    // ── Delete avatar ─────────────────────────────────────────
    if ($action === 'delete_avatar') {
        if (!empty($dbUser['avatar'])) {
            @unlink(UPLOADS_PATH . '/avatars/' . $dbUser['avatar']);
        }
        Database::execute('UPDATE users SET avatar = NULL WHERE id = ?', [$uid]);
        $_SESSION['user']['avatar'] = null;
        $dbUser['avatar']           = null;
        $avatarMsg = ['type' => 'success', 'text' => 'Avatar removed.'];
    }
}

// ── Helpers ───────────────────────────────────────────────────
function msg_box(array $m): string {
    if (!$m['text']) return '';
    $c = $m['type'] === 'success' ? '#16a34a' : '#eb1b26';
    $i = $m['type'] === 'success' ? '✅' : '❌';
    return "<div class=\"msg-box\" style=\"border-color:{$c};\">{$i} " . htmlspecialchars($m['text']) . "</div>";
}
?>

<!-- ─────────────────────────────────────────────────────── -->
<!--  Page-level styles                                      -->
<!-- ─────────────────────────────────────────────────────── -->
<style>
  .account-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: var(--sp-6);
    align-items: start;
  }
  @media (max-width: 820px) { .account-layout { grid-template-columns: 1fr; } }

  /* ── Profile sidebar card ──────────────────────────────── */
  .profile-aside {
    display: flex;
    flex-direction: column;
    gap: var(--sp-4);
  }
  .profile-card {
    padding: var(--sp-6);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--sp-3);
    text-align: center;
  }

  .avatar-large {
    width: 88px; height: 88px;
    border-radius: 50%;
    background: var(--sc-red);
    color: #fff;
    font-family: 'Anton', sans-serif;
    font-size: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    border: 3px solid var(--border);
    transition: border-color .2s;
  }
  .avatar-large img { width: 100%; height: 100%; object-fit: cover; }
  .avatar-large:hover { border-color: var(--sc-red); }

  .avatar-upload-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.55);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    opacity: 0;
    transition: opacity .2s;
    cursor: pointer;
    border-radius: 50%;
  }
  .avatar-large:hover .avatar-upload-overlay { opacity: 1; }

  .profile-name {
    font-family: 'Anton', sans-serif;
    font-size: var(--t-xl);
    text-transform: uppercase;
    letter-spacing: .03em;
    color: var(--txt-primary);
  }
  .profile-email { font-size: var(--t-sm); color: var(--txt-muted); word-break: break-all; }
  .profile-role {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: var(--t-xs);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .08em;
  }
  .profile-role.admin { background: rgba(235,27,38,.12); color: var(--sc-red); }
  .profile-role.user  { background: rgba(100,100,100,.10); color: var(--txt-muted); }
  .profile-joined { font-size: var(--t-xs); color: var(--txt-muted); }

  /* Avatar action buttons */
  .avatar-actions { display: flex; gap: var(--sp-2); width: 100%; }
  .avatar-actions .btn { flex: 1; justify-content: center; font-size: 11px; padding: 7px var(--sp-2); }

  /* Quick links */
  .quick-links-card { padding: var(--sp-4); }
  .quick-links-title {
    font-size: var(--t-xs);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--txt-muted);
    padding: 0 var(--sp-2) var(--sp-2);
  }

  /* ── Settings area ─────────────────────────────────────── */
  .settings-area {
    display: flex;
    flex-direction: column;
    gap: var(--sp-5);
  }

  .settings-card { padding: var(--sp-6); }
  .settings-card-head {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    margin-bottom: var(--sp-5);
    padding-bottom: var(--sp-4);
    border-bottom: 1px solid var(--border);
  }
  .settings-card-icon {
    width: 38px; height: 38px;
    border-radius: var(--radius-sm);
    background: rgba(235,27,38,.10);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
  }
  .settings-card-title {
    font-family: 'Anton', sans-serif;
    font-size: var(--t-lg);
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--txt-primary);
  }
  .settings-card-sub { font-size: var(--t-xs); color: var(--txt-muted); margin-top: 2px; }

  /* Form grid */
  .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--sp-4);
  }
  @media (max-width: 560px) { .form-grid { grid-template-columns: 1fr; } }
  .form-grid .full { grid-column: 1 / -1; }

  .form-group { display: flex; flex-direction: column; gap: 6px; }
  .form-label {
    font-size: var(--t-xs);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--txt-secondary);
  }
  .form-input {
    width: 100%;
    padding: 10px var(--sp-3);
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-family: 'Poppins', sans-serif;
    font-size: var(--t-sm);
    color: var(--txt-primary);
    transition: border-color .2s, box-shadow .2s;
    outline: none;
  }
  .form-input:focus {
    border-color: var(--sc-red);
    box-shadow: 0 0 0 3px rgba(235,27,38,.08);
  }

  /* Password field wrapper */
  .pw-wrap { position: relative; }
  .pw-wrap .form-input { padding-right: 44px; }
  .pw-eye {
    position: absolute;
    right: 0; top: 0; bottom: 0;
    width: 40px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px;
    color: var(--txt-muted);
    cursor: pointer;
    transition: color .2s;
  }
  .pw-eye:hover { color: var(--sc-red); }

  /* Password strength */
  .strength-bar {
    height: 3px;
    border-radius: 2px;
    background: var(--border);
    margin-top: 4px;
    overflow: hidden;
  }
  .strength-fill {
    height: 100%;
    border-radius: 2px;
    width: 0;
    transition: width .3s, background .3s;
  }
  .strength-hint {
    font-size: 10px;
    color: var(--txt-muted);
    margin-top: 3px;
  }

  /* Match hint */
  .match-hint { font-size: 11px; margin-top: 3px; height: 14px; }
  .match-hint.ok  { color: #16a34a; }
  .match-hint.bad { color: var(--sc-red); }

  /* Form footer */
  .form-footer {
    display: flex;
    justify-content: flex-end;
    margin-top: var(--sp-5);
    padding-top: var(--sp-4);
    border-top: 1px solid var(--border);
  }

  /* Message box */
  .msg-box {
    padding: 10px var(--sp-4);
    border-radius: var(--radius-sm);
    border-left: 3px solid;
    font-size: var(--t-sm);
    background: var(--bg-input);
    margin-bottom: var(--sp-4);
    animation: fadeIn .3s ease;
  }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }

  /* Danger zone */
  .danger-zone-card {
    padding: var(--sp-5) var(--sp-6);
    border-color: rgba(235,27,38,.25);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-4);
    flex-wrap: wrap;
  }
  .danger-zone-text {}
  .danger-zone-title { font-weight: 600; font-size: var(--t-sm); color: var(--sc-red); margin-bottom: 3px; }
  .danger-zone-desc  { font-size: var(--t-xs); color: var(--txt-muted); }
</style>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  LAYOUT                                                  -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="account-layout">

  <!-- ── Left: profile aside ─────────────────────────────── -->
  <div class="profile-aside">

    <!-- Profile card -->
    <div class="card profile-card">

      <!-- Avatar -->
      <form method="POST" enctype="multipart/form-data" id="avatarForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload_avatar">
        <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/webp"
               style="display:none" onchange="document.getElementById('avatarForm').submit()">

        <label for="avatarInput" style="cursor:pointer">
          <div class="avatar-large">
            <?php if (!empty($dbUser['avatar'])): ?>
              <img src="<?= APP_URL ?>/uploads/avatars/<?= htmlspecialchars($dbUser['avatar']) ?>" alt="avatar">
            <?php else: ?>
              <?= strtoupper(mb_substr($dbUser['name'], 0, 2)) ?>
            <?php endif; ?>
            <div class="avatar-upload-overlay">📷</div>
          </div>
        </label>
      </form>

      <?= msg_box($avatarMsg) ?>

      <div class="profile-name"><?= htmlspecialchars($dbUser['name']) ?></div>
      <div class="profile-email"><?= htmlspecialchars($dbUser['email']) ?></div>

      <span class="profile-role <?= $dbUser['role'] ?>">
        <?= $dbUser['role'] === 'admin' ? '🛡️ Admin' : '👤 User' ?>
      </span>

      <div class="profile-joined">
        Member since <?= date('F Y', strtotime($dbUser['created_at'])) ?>
      </div>

      <?php if (!empty($dbUser['avatar'])): ?>
      <div class="avatar-actions">
        <label for="avatarInput" class="btn btn-ghost" style="cursor:pointer;justify-content:center;flex:1;font-size:11px;padding:7px 8px">
          📷 Change
        </label>
        <form method="POST" style="flex:1">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete_avatar">
          <button type="submit" class="btn btn-ghost" style="width:100%;font-size:11px;padding:7px 8px;color:var(--sc-red)">
            🗑 Remove
          </button>
        </form>
      </div>
      <?php endif; ?>

    </div><!-- /.profile-card -->

    <!-- Quick links -->
    <div class="card quick-links-card">
      <div class="quick-links-title">Navigation</div>
      <?= nav_item(APP_URL . '/pages/dashboard.php', '📊', 'Dashboard', '') ?>
      <?= nav_item(APP_URL . '/pages/my-cvs.php',    '📄', 'My CVs',    '') ?>
    </div>

  </div><!-- /.profile-aside -->

  <!-- ── Right: settings area ────────────────────────────── -->
  <div class="settings-area">

    <!-- 1. Profile information -->
    <div class="card settings-card">
      <div class="settings-card-head">
        <div class="settings-card-icon">👤</div>
        <div>
          <div class="settings-card-title">Profile Information</div>
          <div class="settings-card-sub">Update your name and email address</div>
        </div>
      </div>

      <?= msg_box($profileMsg) ?>

      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_profile">

        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label" for="name">Full Name</label>
            <input class="form-input" type="text" id="name" name="name"
                   value="<?= htmlspecialchars($dbUser['name']) ?>"
                   required minlength="2" maxlength="150"
                   placeholder="Your full name">
          </div>

          <div class="form-group full">
            <label class="form-label" for="email">Email Address</label>
            <input class="form-input" type="email" id="email" name="email"
                   value="<?= htmlspecialchars($dbUser['email']) ?>"
                   required
                   placeholder="you@example.com">
          </div>
        </div>

        <div class="form-footer">
          <button type="submit" class="btn btn-primary">Save Profile</button>
        </div>
      </form>
    </div><!-- /.settings-card -->

    <!-- 2. Change password -->
    <div class="card settings-card">
      <div class="settings-card-head">
        <div class="settings-card-icon">🔐</div>
        <div>
          <div class="settings-card-title">Change Password</div>
          <div class="settings-card-sub">Min 8 chars · 1 uppercase · 1 number</div>
        </div>
      </div>

      <?= msg_box($passwordMsg) ?>

      <form method="POST" id="pwForm" onsubmit="return validatePwForm()">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_password">

        <div class="form-grid">
          <!-- Current -->
          <div class="form-group full">
            <label class="form-label" for="current_password">Current Password</label>
            <div class="pw-wrap">
              <input class="form-input" type="password" id="current_password"
                     name="current_password" required placeholder="••••••••">
              <span class="pw-eye" onclick="togglePw('current_password', this)">👁</span>
            </div>
          </div>

          <!-- New -->
          <div class="form-group">
            <label class="form-label" for="new_password">New Password</label>
            <div class="pw-wrap">
              <input class="form-input" type="password" id="new_password"
                     name="new_password" required placeholder="••••••••"
                     oninput="checkStrength(this.value); checkMatch()">
              <span class="pw-eye" onclick="togglePw('new_password', this)">👁</span>
            </div>
            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
            <div class="strength-hint" id="strengthHint">Enter a new password</div>
          </div>

          <!-- Confirm -->
          <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm New Password</label>
            <div class="pw-wrap">
              <input class="form-input" type="password" id="confirm_password"
                     name="confirm_password" required placeholder="••••••••"
                     oninput="checkMatch()">
              <span class="pw-eye" onclick="togglePw('confirm_password', this)">👁</span>
            </div>
            <div class="match-hint" id="matchHint"></div>
          </div>
        </div>

        <div class="form-footer">
          <button type="submit" class="btn btn-primary" id="pwSubmit">Update Password</button>
        </div>
      </form>
    </div><!-- /.settings-card -->

    <!-- 3. Danger zone -->
    <div class="card danger-zone-card">
      <div class="danger-zone-text">
        <div class="danger-zone-title">⚠️ Delete Account</div>
        <div class="danger-zone-desc">
          Permanently deletes your account, all CVs, and exported files. This cannot be undone.
        </div>
      </div>
      <button class="btn btn-ghost" style="border-color:rgba(235,27,38,.4);color:var(--sc-red)"
              onclick="alert('Account deletion will be available in Phase 5. Contact support for now.')">
        Delete Account
      </button>
    </div>

  </div><!-- /.settings-area -->

</div><!-- /.account-layout -->

<script>
/* ── Password eye toggle ───────────────────────────────── */
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  inp.type  = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}

/* ── Password strength ─────────────────────────────────── */
const fill = document.getElementById('strengthFill');
const hint = document.getElementById('strengthHint');
const colors = ['#eb1b26','#f97316','#d97706','#16a34a','#16a34a'];
const labels  = ['Too short','Weak','Fair','Good','Strong 💪'];

function checkStrength(pw) {
  let s = 0;
  if (pw.length >= 8)    s++;
  if (/[A-Z]/.test(pw))  s++;
  if (/[0-9]/.test(pw))  s++;
  if (/[^A-Za-z0-9]/.test(pw)) s++;
  if (pw.length >= 14)   s++;
  s = Math.max(0, Math.min(4, s));
  fill.style.width      = ((s + 1) * 20) + '%';
  fill.style.background = colors[s];
  hint.textContent      = pw.length ? labels[s] : 'Enter a new password';
  hint.style.color      = colors[s];
}

/* ── Password match check ──────────────────────────────── */
const matchHint = document.getElementById('matchHint');
const pwSubmit  = document.getElementById('pwSubmit');

function checkMatch() {
  const a = document.getElementById('new_password').value;
  const b = document.getElementById('confirm_password').value;
  if (!b) { matchHint.textContent = ''; matchHint.className = 'match-hint'; return; }
  if (a === b) {
    matchHint.textContent = '✓ Passwords match';
    matchHint.className   = 'match-hint ok';
  } else {
    matchHint.textContent = '✗ Passwords do not match';
    matchHint.className   = 'match-hint bad';
  }
}

function validatePwForm() {
  const a = document.getElementById('new_password').value;
  const b = document.getElementById('confirm_password').value;
  if (a !== b) {
    alert('New passwords do not match.');
    return false;
  }
  return true;
}
</script>

<?php layout_end(); ?>
