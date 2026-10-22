<?php
// Caller CRM: cold-calling dashboard — see assigned leads, update status/remarks.
require_once __DIR__ . '/lib.php';
$user = require_login();
$pdo = db();

// Only callers and super can access
$isSuper = ($user['role'] ?? '') === 'super';
$isCaller = ($user['role'] ?? '') === 'caller';
if (!$isSuper && !$isCaller) {
    header('Location: dashboard.php');
    exit;
}

$statuses = [
    'new' => '🆕 New',
    'no_pick' => '📵 No Pick',
    'callback' => '🔄 Call Back',
    'not_interested' => '❌ Not Interested',
    'interested' => '👍 Interested',
    'pending' => '⏳ Pending',
    'done' => '✅ Done',
];

$callerId = $isSuper ? (int)($_GET['caller'] ?? $user['id']) : (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update-lead') {
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $status = (string)($_POST['status'] ?? 'new');
        $remarks = trim((string)($_POST['remarks'] ?? ''));

        if (!array_key_exists($status, $statuses)) $status = 'new';

        // Verify this lead belongs to the caller (or super)
        $check = $pdo->prepare("SELECT id FROM leads WHERE id = ? AND (assigned_to = ? OR ? = 1)");
        $check->execute([$leadId, $callerId, $isSuper ? 1 : 0]);

        if ($check->fetch()) {
            $pdo->prepare("UPDATE leads SET status = ?, remarks = ?, last_called_at = datetime('now'),
                           call_count = call_count + 1, updated_at = datetime('now') WHERE id = ?")
                ->execute([$status, $remarks, $leadId]);
            flash('Lead updated!');
        } else {
            flash('Lead not found.', false);
        }
    }
    header('Location: caller-crm.php' . ($isSuper ? '?caller=' . $callerId : ''));
    exit;
}

// Get callers list for super
$callers = $isSuper ? $pdo->query("SELECT id, name FROM users WHERE role = 'caller' ORDER BY name")->fetchAll() : [];

// Get leads for this caller
$st = $pdo->prepare("SELECT * FROM leads WHERE assigned_to = ? ORDER BY
                     CASE status WHEN 'new' THEN 0 WHEN 'callback' THEN 1 WHEN 'pending' THEN 2 ELSE 3 END,
                     updated_at DESC");
$st->execute([$callerId]);
$leads = $st->fetchAll();

// Stats
$stats = [];
foreach ($leads as $l) {
    $stats[$l['status']] = ($stats[$l['status']] ?? 0) + 1;
}

$callerName = $pdo->query("SELECT name FROM users WHERE id = " . (int)$callerId)->fetchColumn();

page_head('Caller CRM');
rp_app_open($user, 'crm', [
    'title' => '📞 Caller CRM',
    'sub' => $isSuper ? "Viewing leads for: <b>" . e($callerName) . "</b>" : 'Your assigned leads. Call them and update status!',
    'crumb' => $isCaller ? 'CRM' : 'Admin',
]);
?>

<?php if ($isSuper && $callers): ?>
<div class="rp-card">
  <form method="get" style="display:flex;gap:.8rem;align-items:center">
    <label><b>View caller:</b></label>
    <select class="rp-select" name="caller" onchange="this.form.submit()">
      <?php foreach ($callers as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= (int)$callerId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>
<?php endif; ?>

<div class="rp-stats" style="grid-template-columns:repeat(4,1fr)">
  <?= rp_stat('Total', (string)count($leads)) ?>
  <?= rp_stat('🆕 New', (string)($stats['new'] ?? 0)) ?>
  <?= rp_stat('🔄 Call Back', (string)($stats['callback'] ?? 0)) ?>
  <?= rp_stat('✅ Done', (string)($stats['done'] ?? 0)) ?>
</div>

<?php if (!$leads): ?>
  <div class="rp-card" style="text-align:center;padding:3rem">
    <p style="font-size:1.2rem">📭 No leads assigned yet.</p>
    <p class="rp-hint">Ask your admin to assign leads to you.</p>
  </div>
<?php else: ?>
  <?php foreach ($leads as $l): ?>
  <div class="rp-card" style="<?= $l['status'] === 'done' ? 'opacity:.7' : '' ?>">
    <div style="display:flex;justify-content:space-between;align-items:start;gap:1rem;flex-wrap:wrap">
      <div>
        <h3 style="margin:0 0 .3rem"><?= e($l['restaurant_name']) ?></h3>
        <p style="margin:0;color:#666">
          👤 <?= e($l['contact_name'] ?: '—') ?> ·
          📍 <?= e($l['city'] ?: '—') ?> ·
          📞 <a href="tel:<?= e($l['phone']) ?>" style="font-weight:700;font-size:1.1rem"><?= e($l['phone']) ?></a>
        </p>
        <p style="margin:.4rem 0 0;font-size:.85rem;color:#888">
          <?= e($statuses[$l['status']] ?? $l['status']) ?> ·
          📊 <?= (int)$l['call_count'] ?> calls
          <?php if ($l['last_called_at']): ?>· Last: <?= e(date('d M, h:i A', strtotime($l['last_called_at']))) ?><?php endif; ?>
        </p>
        <?php if ($l['remarks']): ?>
          <p style="margin:.4rem 0 0;padding:.5rem;background:#f8f8f8;border-radius:6px;font-size:.9rem">
            💬 <?= e($l['remarks']) ?>
          </p>
        <?php endif; ?>
      </div>
      <div style="display:flex;gap:.5rem">
        <a class="rp-btn rp-btn-green" href="tel:<?= e($l['phone']) ?>">📞 Call Now</a>
        <?php
        $wa = preg_replace('/\D/', '', $l['phone']);
        if ($wa && $wa[0] === '0') $wa = '92' . substr($wa, 1);
        ?>
        <?php if ($wa): ?>
          <a class="rp-btn" href="https://wa.me/<?= e($wa) ?>" target="_blank">💬 WhatsApp</a>
        <?php endif; ?>
      </div>
    </div>

    <form method="post" style="margin-top:1rem;padding-top:1rem;border-top:1px solid #eee;display:grid;grid-template-columns:1fr 2fr auto;gap:.6rem;align-items:end">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update-lead">
      <input type="hidden" name="lead_id" value="<?= (int)$l['id'] ?>">
      <div>
        <label style="font-size:.8rem;font-weight:600">Status</label>
        <select class="rp-select" name="status" style="width:100%">
          <?php foreach ($statuses as $k => $v): ?>
            <option value="<?= e($k) ?>" <?= $l['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:.8rem;font-weight:600">Remarks</label>
        <input class="rp-input" name="remarks" value="<?= e($l['remarks']) ?>" placeholder="e.g., Owner busy, call after 5pm..." style="width:100%">
      </div>
      <div>
        <button class="rp-btn rp-btn-red" type="submit">💾 Save</button>
      </div>
    </form>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php rp_app_close(); page_foot(); ?>
