<?php
// ============================================================
//  CV Builder Pro — Dashboard
//  Author  : Ahmed Mohamed Abubakr
//  Site    : https://abubakr.rf.gd/
//  Phase   : 2 — Auth + UI Shell
// ============================================================

$page_title  = 'Dashboard';
$active_page = 'dashboard';

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';  // opens <main>

$user = auth_user();
$uid  = (int)$user['id'];

// ── Fetch data ───────────────────────────────────────────────
$cvs = Database::fetchAll(
    'SELECT * FROM cvs WHERE user_id = ? AND is_active = 1 ORDER BY updated_at DESC',
    [$uid]
);

$total_cvs    = count($cvs);
$export_count = (int)(Database::fetchOne(
    'SELECT COUNT(*) AS c FROM export_logs WHERE user_id = ?', [$uid]
)['c'] ?? 0);

$last_export = Database::fetchOne(
    'SELECT exported_at, format FROM export_logs WHERE user_id = ? ORDER BY exported_at DESC LIMIT 1',
    [$uid]
);

// Average completeness
$avg_complete = $total_cvs > 0
    ? (int)round(array_sum(array_column($cvs, 'completeness')) / $total_cvs)
    : 0;
?>

<!-- ─────────────────────────────────────────────────────── -->
<!--  Page-level styles                                      -->
<!-- ─────────────────────────────────────────────────────── -->
<style>
  /* ── Welcome banner ────────────────────────────────────── */
  .welcome-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-6);
    background: linear-gradient(115deg, var(--sc-black) 0%, #1a0203 60%, #2d0507 100%);
    border-radius: var(--radius-lg);
    padding: var(--sp-6) var(--sp-8);
    margin-bottom: var(--sp-6);
    overflow: hidden;
    position: relative;
  }
  .welcome-banner::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 80% 50%, rgba(235,27,38,.18) 0%, transparent 65%);
    pointer-events: none;
  }
  .welcome-banner::after {
    content: 'CV';
    position: absolute;
    right: -10px; top: -20px;
    font-family: 'Anton', sans-serif;
    font-size: 160px;
    color: rgba(235,27,38,.06);
    line-height: 1;
    pointer-events: none;
    letter-spacing: -.02em;
  }
  .welcome-text {}
  .welcome-greeting {
    font-size: var(--t-sm);
    color: rgba(255,255,255,.50);
    font-weight: 400;
    margin-bottom: 6px;
    letter-spacing: .05em;
    text-transform: uppercase;
  }
  .welcome-name {
    font-family: 'Anton', sans-serif;
    font-size: clamp(22px, 3vw, 36px);
    color: #fff;
    text-transform: uppercase;
    letter-spacing: .03em;
    line-height: 1.1;
  }
  .welcome-name span { color: var(--sc-red); }
  .welcome-sub {
    font-size: var(--t-sm);
    color: rgba(255,255,255,.40);
    margin-top: var(--sp-2);
  }
  .welcome-cta { flex-shrink: 0; position: relative; z-index: 1; }

  /* ── Stat grid ─────────────────────────────────────────── */
  .stat-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--sp-4);
    margin-bottom: var(--sp-6);
  }
  @media (max-width: 900px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 480px) { .stat-grid { grid-template-columns: 1fr; } }

  .stat-card {
    padding: var(--sp-5);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);
    transition: transform .2s var(--ease), box-shadow .2s var(--ease);
  }
  .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
  }
  .stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--accent, var(--sc-red));
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
  }
  .stat-icon {
    font-size: 22px;
    line-height: 1;
    margin-bottom: var(--sp-1);
  }
  .stat-value {
    font-family: 'Anton', sans-serif;
    font-size: clamp(28px, 4vw, 40px);
    color: var(--txt-primary);
    line-height: 1;
    letter-spacing: -.01em;
  }
  .stat-label {
    font-size: var(--t-xs);
    color: var(--txt-muted);
    text-transform: uppercase;
    letter-spacing: .08em;
    font-weight: 600;
  }
  .stat-meta {
    font-size: var(--t-xs);
    color: var(--txt-muted);
    margin-top: var(--sp-1);
  }
  .stat-meta .pos { color: #16a34a; }
  .stat-meta .neg { color: var(--sc-red); }

  /* ── CV cards grid ─────────────────────────────────────── */
  .cv-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--sp-5);
    gap: var(--sp-4);
    flex-wrap: wrap;
  }

  .cv-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: var(--sp-5);
  }

  /* CV Card */
  .cv-card {
    padding: var(--sp-5);
    position: relative;
    overflow: hidden;
    cursor: pointer;
    transition: transform .2s var(--ease), box-shadow .2s var(--ease), border-color .2s;
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
  }
  .cv-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(235,27,38,.25);
  }

  /* Template badge strip */
  .cv-card-strip {
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
  }
  .cv-card-strip.classic { background: linear-gradient(90deg, #2563eb, #60a5fa); }
  .cv-card-strip.modern  { background: linear-gradient(90deg, var(--sc-red), #f97316); }
  .cv-card-strip.minimal { background: linear-gradient(90deg, #16a34a, #4ade80); }

  /* Top row: title + actions */
  .cv-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-2);
    padding-top: var(--sp-1);
  }
  .cv-card-title {
    font-family: 'Anton', sans-serif;
    font-size: var(--t-lg);
    text-transform: uppercase;
    letter-spacing: .03em;
    color: var(--txt-primary);
    line-height: 1.15;
    word-break: break-word;
  }
  .cv-card-actions {
    display: flex;
    gap: var(--sp-1);
    flex-shrink: 0;
  }
  .cv-action-btn {
    width: 30px; height: 30px;
    border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    color: var(--txt-muted);
    border: 1px solid transparent;
    transition: background var(--dur), color var(--dur), border-color var(--dur);
  }
  .cv-action-btn:hover { background: var(--bg-hover); color: var(--sc-red); border-color: rgba(235,27,38,.2); }

  /* Meta row */
  .cv-card-meta {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    flex-wrap: wrap;
  }
  .cv-meta-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: var(--t-xs);
    color: var(--txt-muted);
    font-weight: 500;
  }

  /* Completeness ring */
  .cv-ring-wrap {
    display: flex;
    align-items: center;
    gap: var(--sp-4);
    padding: var(--sp-3) 0;
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
  }
  .ring-svg { flex-shrink: 0; }
  .ring-track { fill: none; stroke: var(--border); stroke-width: 5; }
  .ring-fill  {
    fill: none;
    stroke-width: 5;
    stroke-linecap: round;
    transform: rotate(-90deg);
    transform-origin: 50% 50%;
    transition: stroke-dashoffset .8s var(--ease);
  }
  .ring-fill.high   { stroke: #16a34a; }
  .ring-fill.medium { stroke: #d97706; }
  .ring-fill.low    { stroke: var(--sc-red); }
  .ring-label {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Anton', sans-serif;
    font-size: 14px;
    color: var(--txt-primary);
  }
  .ring-container { position: relative; width: 54px; height: 54px; flex-shrink: 0; }
  .ring-info {}
  .ring-pct {
    font-family: 'Anton', sans-serif;
    font-size: var(--t-xl);
    color: var(--txt-primary);
    letter-spacing: -.01em;
  }
  .ring-hint {
    font-size: var(--t-xs);
    color: var(--txt-muted);
    margin-top: 2px;
  }

  /* CV card footer */
  .cv-card-footer {
    display: flex;
    gap: var(--sp-2);
    margin-top: auto;
  }
  .cv-card-footer .btn {
    flex: 1;
    justify-content: center;
    font-size: 11px;
    padding: 7px var(--sp-3);
  }

  /* ── New CV card ─────────────────────────────────────── */
  .cv-new-card {
    padding: var(--sp-5);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--sp-3);
    min-height: 220px;
    border: 2px dashed var(--border);
    border-radius: var(--radius-lg);
    cursor: pointer;
    transition: border-color .2s, background .2s, transform .2s;
    text-align: center;
    background: transparent;
    width: 100%;
    color: var(--txt-muted);
  }
  .cv-new-card:hover {
    border-color: var(--sc-red);
    background: var(--bg-hover);
    transform: translateY(-2px);
    color: var(--sc-red);
  }
  .cv-new-icon {
    font-size: 32px;
    opacity: .5;
    transition: opacity .2s, transform .2s;
  }
  .cv-new-card:hover .cv-new-icon { opacity: 1; transform: scale(1.15); }
  .cv-new-label {
    font-family: 'Anton', sans-serif;
    font-size: var(--t-lg);
    text-transform: uppercase;
    letter-spacing: .05em;
  }
  .cv-new-hint { font-size: var(--t-xs); }

  /* ── Recent activity panel ──────────────────────────── */
  .two-col {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: var(--sp-5);
    margin-top: var(--sp-6);
  }
  @media (max-width: 900px) { .two-col { grid-template-columns: 1fr; } }

  .activity-card { padding: var(--sp-5); }
  .activity-title {
    font-family: 'Anton', sans-serif;
    font-size: var(--t-lg);
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--txt-primary);
    margin-bottom: var(--sp-4);
  }
  .activity-list { display: flex; flex-direction: column; gap: var(--sp-1); }
  .activity-item {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    padding: 10px var(--sp-3);
    border-radius: var(--radius-sm);
    transition: background var(--dur);
  }
  .activity-item:hover { background: var(--bg-hover); }
  .activity-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
  }
  .activity-dot.red   { background: var(--sc-red); }
  .activity-dot.green { background: #16a34a; }
  .activity-dot.blue  { background: #2563eb; }
  .activity-text { flex: 1; font-size: var(--t-sm); color: var(--txt-secondary); }
  .activity-time { font-size: var(--t-xs); color: var(--txt-muted); white-space: nowrap; }

  /* Tips card */
  .tips-card { padding: var(--sp-5); display: flex; flex-direction: column; gap: var(--sp-3); }
  .tips-title {
    font-family: 'Anton', sans-serif;
    font-size: var(--t-lg);
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--txt-primary);
  }
  .tip-item {
    display: flex;
    gap: var(--sp-3);
    align-items: flex-start;
    padding: var(--sp-3);
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
    transition: border-color .2s, background .2s;
  }
  .tip-item:hover { border-color: rgba(235,27,38,.3); background: var(--bg-hover); }
  .tip-icon { font-size: 20px; flex-shrink: 0; }
  .tip-text {}
  .tip-label { font-size: var(--t-sm); font-weight: 600; color: var(--txt-primary); margin-bottom: 2px; }
  .tip-desc  { font-size: var(--t-xs); color: var(--txt-muted); line-height: 1.5; }
</style>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  WELCOME BANNER                                          -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="welcome-banner">
  <div class="welcome-text">
    <div class="welcome-greeting">👋 Welcome back</div>
    <div class="welcome-name">
      <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>
      <span>.</span>
    </div>
    <div class="welcome-sub">
      You have <?= $total_cvs ?> CV<?= $total_cvs !== 1 ? 's' : '' ?> · <?= $export_count ?> export<?= $export_count !== 1 ? 's' : '' ?> total
    </div>
  </div>
  <div class="welcome-cta">
    <a href="<?= APP_URL ?>/pages/cv-builder.php?new=1" class="btn btn-primary">
      ＋ New CV
    </a>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  STAT CARDS                                              -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="stat-grid">
  <div class="card stat-card" style="--accent:#eb1b26">
    <div class="stat-icon">📄</div>
    <div class="stat-value"><?= $total_cvs ?></div>
    <div class="stat-label">Total CVs</div>
    <div class="stat-meta">
      <?= $total_cvs > 0 ? '<span class="pos">✓ Active</span>' : 'No CVs yet' ?>
    </div>
  </div>

  <div class="card stat-card" style="--accent:#2563eb">
    <div class="stat-icon">📤</div>
    <div class="stat-value"><?= $export_count ?></div>
    <div class="stat-label">Exports</div>
    <div class="stat-meta">
      <?php if ($last_export): ?>
        Last: <?= strtoupper($last_export['format']) ?> · <?= date('d M', strtotime($last_export['exported_at'])) ?>
      <?php else: ?>
        No exports yet
      <?php endif; ?>
    </div>
  </div>

  <div class="card stat-card" style="--accent:#16a34a">
    <div class="stat-icon">✅</div>
    <div class="stat-value"><?= $avg_complete ?>%</div>
    <div class="stat-label">Avg. Completeness</div>
    <div class="stat-meta">
      <?php if ($avg_complete >= 80): ?>
        <span class="pos">Looking great!</span>
      <?php elseif ($avg_complete >= 40): ?>
        <span class="neg">Keep filling in sections</span>
      <?php else: ?>
        Add more details
      <?php endif; ?>
    </div>
  </div>

  <div class="card stat-card" style="--accent:#d97706">
    <div class="stat-icon">🕐</div>
    <div class="stat-value"><?= date('d M') ?></div>
    <div class="stat-label">Today</div>
    <div class="stat-meta"><?= date('l, Y') ?></div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  CV CARDS                                                -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="cv-section-head">
  <div>
    <div class="section-title">Your CVs</div>
    <div class="section-sub"><?= $total_cvs ?> document<?= $total_cvs !== 1 ? 's' : '' ?></div>
  </div>
  <?php if ($total_cvs > 0): ?>
  <a href="<?= APP_URL ?>/pages/my-cvs.php" class="btn btn-ghost" style="font-size:12px">
    View all →
  </a>
  <?php endif; ?>
</div>

<div class="cv-grid">

  <?php foreach ($cvs as $cv):
    $pct       = (int)$cv['completeness'];
    $ringClass = $pct >= 70 ? 'high' : ($pct >= 35 ? 'medium' : 'low');
    $r         = 22; $circ = 2 * M_PI * $r;
    $offset    = $circ * (1 - $pct / 100);
    $strip     = in_array($cv['template'], ['classic','modern','minimal']) ? $cv['template'] : 'modern';
    $langLabel = ['en' => '🇬🇧 English', 'ar' => '🇪🇬 Arabic', 'both' => '🌐 Bilingual'][$cv['lang']] ?? $cv['lang'];
  ?>
  <div class="card cv-card" onclick="window.location='<?= APP_URL ?>/pages/cv-builder.php?id=<?= $cv['id'] ?>'">
    <div class="cv-card-strip <?= $strip ?>"></div>

    <div class="cv-card-top">
      <div class="cv-card-title"><?= htmlspecialchars($cv['title']) ?></div>
      <div class="cv-card-actions" onclick="event.stopPropagation()">
        <button class="cv-action-btn" title="Preview" onclick="window.location='<?= APP_URL ?>/pages/preview.php?id=<?= $cv['id'] ?>'">👁</button>
        <button class="cv-action-btn" title="Duplicate">⧉</button>
        <button class="cv-action-btn" title="Delete" onclick="confirmDelete(<?= $cv['id'] ?>, '<?= htmlspecialchars(addslashes($cv['title'])) ?>')">🗑</button>
      </div>
    </div>

    <div class="cv-card-meta">
      <span class="cv-meta-chip">📐 <?= ucfirst($cv['template']) ?></span>
      <span class="cv-meta-chip"><?= $langLabel ?></span>
      <span class="cv-meta-chip">🕐 <?= date('d M Y', strtotime($cv['updated_at'])) ?></span>
    </div>

    <div class="cv-ring-wrap">
      <div class="ring-container">
        <svg class="ring-svg" width="54" height="54" viewBox="0 0 54 54">
          <circle class="ring-track" cx="27" cy="27" r="<?= $r ?>"/>
          <circle class="ring-fill <?= $ringClass ?>"
            cx="27" cy="27" r="<?= $r ?>"
            stroke-dasharray="<?= round($circ, 2) ?>"
            stroke-dashoffset="<?= round($offset, 2) ?>"/>
        </svg>
        <div class="ring-label"><?= $pct ?>%</div>
      </div>
      <div class="ring-info">
        <div class="ring-pct"><?= $pct ?>%</div>
        <div class="ring-hint">
          <?php if ($pct === 100): ?>✨ Complete
          <?php elseif ($pct >= 70): ?>Almost there — <?= 100 - $pct ?>% left
          <?php else: ?>Fill in more sections
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="cv-card-footer">
      <a href="<?= APP_URL ?>/pages/cv-builder.php?id=<?= $cv['id'] ?>" class="btn btn-primary" onclick="event.stopPropagation()">
        ✏️ Edit
      </a>
      <a href="<?= APP_URL ?>/pages/preview.php?id=<?= $cv['id'] ?>" class="btn btn-ghost" onclick="event.stopPropagation()">
        👁 Preview
      </a>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- New CV card -->
  <button class="cv-new-card" onclick="window.location='<?= APP_URL ?>/pages/cv-builder.php?new=1'">
    <div class="cv-new-icon">＋</div>
    <div class="cv-new-label">New CV</div>
    <div class="cv-new-hint">Start from scratch or a template</div>
  </button>

</div><!-- /.cv-grid -->

<!-- ══════════════════════════════════════════════════════════ -->
<!--  TWO-COL BOTTOM ROW                                      -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="two-col">

  <!-- Recent activity -->
  <div class="card activity-card">
    <div class="activity-title">Recent Activity</div>
    <div class="activity-list">
      <?php if ($total_cvs === 0 && $export_count === 0): ?>
        <div style="text-align:center;padding:var(--sp-6) 0;color:var(--txt-muted);font-size:var(--t-sm);">
          No activity yet. Create your first CV to get started.
        </div>
      <?php else: ?>
        <?php foreach (array_slice($cvs, 0, 4) as $cv): ?>
        <div class="activity-item">
          <div class="activity-dot blue"></div>
          <div class="activity-text">Updated <strong><?= htmlspecialchars($cv['title']) ?></strong></div>
          <div class="activity-time"><?= date('d M', strtotime($cv['updated_at'])) ?></div>
        </div>
        <?php endforeach; ?>
        <?php if ($export_count > 0): ?>
        <div class="activity-item">
          <div class="activity-dot green"></div>
          <div class="activity-text">Exported CV as <strong><?= strtoupper($last_export['format'] ?? 'PDF') ?></strong></div>
          <div class="activity-time"><?= $last_export ? date('d M', strtotime($last_export['exported_at'])) : '' ?></div>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Quick tips -->
  <div class="card tips-card">
    <div class="tips-title">💡 Quick Tips</div>

    <div class="tip-item">
      <div class="tip-icon">📸</div>
      <div class="tip-text">
        <div class="tip-label">Add a professional photo</div>
        <div class="tip-desc">CVs with photos get 40% more responses in the MENA region.</div>
      </div>
    </div>

    <div class="tip-item">
      <div class="tip-icon">🌐</div>
      <div class="tip-text">
        <div class="tip-label">Enable bilingual mode</div>
        <div class="tip-desc">Arabic + English CV doubles your reach for local & international jobs.</div>
      </div>
    </div>

    <div class="tip-item">
      <div class="tip-icon">🎯</div>
      <div class="tip-text">
        <div class="tip-label">Target 80%+ completeness</div>
        <div class="tip-desc">Fill every section — recruiters scan for gaps before reading content.</div>
      </div>
    </div>
  </div>

</div><!-- /.two-col -->

<!-- ── Delete confirm modal ─────────────────────────────── -->
<div id="deleteModal" style="
  display:none; position:fixed; inset:0; z-index:1000;
  background:rgba(0,0,0,.6); align-items:center; justify-content:center;">
  <div class="card" style="max-width:400px;width:90%;padding:var(--sp-6);text-align:center;">
    <div style="font-size:36px;margin-bottom:var(--sp-3)">🗑️</div>
    <div style="font-family:'Anton',sans-serif;font-size:var(--t-xl);text-transform:uppercase;margin-bottom:var(--sp-2)">
      Delete CV?
    </div>
    <div id="deleteModalText" style="font-size:var(--t-sm);color:var(--txt-muted);margin-bottom:var(--sp-6)"></div>
    <div style="display:flex;gap:var(--sp-3);justify-content:center">
      <button class="btn btn-ghost" onclick="closeDeleteModal()">Cancel</button>
      <a id="deleteConfirmBtn" href="#" class="btn btn-primary" style="background:var(--sc-red)">Delete</a>
    </div>
  </div>
</div>

<script>
function confirmDelete(id, title) {
  document.getElementById('deleteModalText').textContent =
    `"${title}" will be permanently deleted. This cannot be undone.`;
  document.getElementById('deleteConfirmBtn').href =
    '<?= APP_URL ?>/api/cv-delete.php?id=' + id + '&_token=<?= csrf_token() ?>';
  document.getElementById('deleteModal').style.display = 'flex';
}
function closeDeleteModal() {
  document.getElementById('deleteModal').style.display = 'none';
}
</script>

<?php layout_end(); ?>
