<?php
// Super-admin: Manage cold-calling leads — assign to callers, import CSV.
require_once __DIR__ . '/lib.php';
$user = require_super();
$pdo = db();

// Lead statuses
$statuses = [
    'new' => 'New',
    'no_pick' => '📵 No Pick',
    'callback' => 'Call Back',
    'not_interested' => '❌ Not Interested',
    'interested' => '👍 Interested',
    'pending' => '⏳ Pending',
    'done' => '✅ Done',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'add-lead') {
        $pdo->prepare("INSERT INTO leads (restaurant_name, contact_name, phone, city, assigned_to)
                       VALUES (?, ?, ?, ?, ?)")
            ->execute([
                trim((string)($_POST['restaurant_name'] ?? '')),
                trim((string)($_POST['contact_name'] ?? '')),
                trim((string)($_POST['phone'] ?? '')),
                trim((string)($_POST['city'] ?? '')),
                (int)($_POST['assigned_to'] ?? 0) ?: null,
            ]);
        flash('Lead added!');
    } elseif ($action === 'assign') {
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $callerId = (int)($_POST['caller_id'] ?? 0) ?: null;
        $pdo->prepare("UPDATE leads SET assigned_to = ?, updated_at = datetime('now') WHERE id = ?")
            ->execute([$callerId, $leadId]);
        flash('Lead reassigned!');
    } elseif ($action === 'bulk-assign') {
        $ids = array_map('intval', $_POST['lead_ids'] ?? []);
        $callerId = (int)($_POST['caller_id'] ?? 0) ?: null;
        if ($ids && $callerId) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $st = $pdo->prepare("UPDATE leads SET assigned_to = ?, updated_at = datetime('now') WHERE id IN ($ph)");
            $st->execute(array_merge([$callerId], $ids));
            flash(count($ids) . ' leads assigned!');
        } else {
            flash('Please select leads and choose a caller!', false);
        }
    } elseif ($action === 'delete-lead') {
        $pdo->prepare("DELETE FROM leads WHERE id = ?")->execute([(int)($_POST['lead_id'] ?? 0)]);
        flash('Lead deleted.');
    } elseif ($action === 'import-csv' && isset($_FILES['csv'])) {
        $count = 0;
        if (($h = fopen($_FILES['csv']['tmp_name'], 'r')) !== false) {
            $header = fgetcsv($h); // skip header
            while (($row = fgetcsv($h)) !== false) {
                if (count($row) < 2) continue;
                $pdo->prepare("INSERT INTO leads (restaurant_name, contact_name, phone, city) VALUES (?, ?, ?, ?)")
                    ->execute([
                        trim($row[0] ?? ''),
                        trim($row[1] ?? ''),
                        trim($row[2] ?? ''),
                        trim($row[3] ?? ''),
                    ]);
                $count++;
            }
            fclose($h);
        }
        flash("$count leads imported!");
    }
    header('Location: admin-leads.php');
    exit;
}

// Get all callers
$callers = $pdo->query("SELECT id, name FROM users WHERE role = 'caller' ORDER BY name")->fetchAll();

// Get all leads with caller names
$leads = $pdo->query("SELECT l.*, u.name AS caller_name
                      FROM leads l LEFT JOIN users u ON u.id = l.assigned_to
                      ORDER BY l.created_at DESC LIMIT 200")->fetchAll();

// Stats
$stats = $pdo->query("SELECT status, COUNT(*) as c FROM leads GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

page_head('Lead Management');
rp_app_open($user, 'leads', [
    'title' => 'Lead Management',
    'sub' => 'Assign cold-calling leads to your callers. They\'ll see them in their CRM.',
    'crumb' => 'Admin',
]);
?>

<div class="rp-stats" style="grid-template-columns:repeat(4,1fr)">
  <?= rp_stat('Total Leads', (string)count($leads)) ?>
  <?= rp_stat('New', (string)($stats['new'] ?? 0)) ?>
  <?= rp_stat('Done ✅', (string)($stats['done'] ?? 0)) ?>
  <?= rp_stat('Callers', (string)count($callers)) ?>
</div>

<div class="rp-card">
  <h2>Add Lead</h2>
  <form method="post" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.8rem">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add-lead">
    <input class="rp-input" name="restaurant_name" placeholder="Restaurant name" required>
    <input class="rp-input" name="contact_name" placeholder="Contact person">
    <input class="rp-input" name="phone" placeholder="Phone (03XX...)" required>
    <input class="rp-input" name="city" placeholder="City">
    <select class="rp-select" name="assigned_to">
      <option value="">— Assign to caller —</option>
      <?php foreach ($callers as $c): ?>
        <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <div><button class="rp-btn rp-btn-red" type="submit">Add Lead</button></div>
  </form>
</div>

<div class="rp-card">
  <h2>Import CSV</h2>
  <p class="rp-hint">Format: <code>restaurant_name, contact_name, phone, city</code> (first row = header)</p>
  <form method="post" enctype="multipart/form-data" style="display:flex;gap:.8rem;align-items:center">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="import-csv">
    <input type="file" name="csv" accept=".csv" required>
    <button class="rp-btn rp-btn-dark" type="submit">Import</button>
  </form>
</div>

<div class="rp-card">
  <h2>All Leads (<?= count($leads) ?>)</h2>
  <!-- Bulk assign bar -->
  <form method="post" id="bulkForm" style="display:flex;gap:.6rem;align-items:center;margin-bottom:.8rem;flex-wrap:wrap">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="bulk-assign">
    <label style="display:flex;align-items:center;gap:.4rem"><input type="checkbox" id="checkAll"> <b>Select All</b></label>
    <select name="caller_id" required class="rp-select">
      <option value="">— Choose caller —</option>
      <?php foreach ($callers as $c): ?>
        <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="rp-btn rp-btn-dark" type="submit">Assign Selected</button>
  </form>
  <script>
    document.getElementById('checkAll').addEventListener('change', function() {
      document.querySelectorAll('.lead-check').forEach(cb => cb.checked = this.checked);
    });
  </script>
  <div class="rp-tablewrap">
  <div style="display:flex;gap:.6rem;margin-bottom:.8rem;flex-wrap:wrap">
    <input class="rp-input" id="leadSearch" placeholder="Search restaurant, contact, phone..." style="max-width:280px" onkeyup="filterLeads()">
    <select class="rp-input" id="statusFilter" onchange="filterLeads()" style="max-width:180px">
      <option value="">All statuses</option>
      <?php foreach ($statuses as $k => $v): ?>
        <option value="<?= e($k) ?>"><?= e($v) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <script>
  function filterLeads() {
    var q = document.getElementById('leadSearch').value.toLowerCase();
    var st = document.getElementById('statusFilter').value;
    document.querySelectorAll('#leadsTable tbody tr').forEach(function(tr) {
      var txt = tr.textContent.toLowerCase();
      var rowStatus = tr.getAttribute('data-status') || '';
      var ok = (!q || txt.indexOf(q) !== -1) && (!st || rowStatus === st);
      tr.style.display = ok ? '' : 'none';
    });
  }
  </script>
  <table class="rp-table" id="leadsTable">
    <thead><tr><th></th><th>Restaurant</th><th>Contact</th><th>Phone</th><th>City</th><th>Status</th><th>Assigned To</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($leads as $l): ?>
      <tr data-status="<?= e($l['status']) ?>">
        <td><input type="checkbox" class="lead-check" name="lead_ids[]" value="<?= (int)$l['id'] ?>" form="bulkForm"></td>
        <td><b><?= e($l['restaurant_name']) ?></b></td>
        <td><?= e($l['contact_name']) ?></td>
        <td><a href="tel:<?= e($l['phone']) ?>" style="color:#1a73e8;text-decoration:none"><?= e($l['phone']) ?></a></td>
        <td><?= e($l['city']) ?></td>
        <td><?= e($statuses[$l['status']] ?? $l['status']) ?></td>
        <td><?= e($l['caller_name'] ?? '—') ?></td>
        <td style="white-space:nowrap">
          <form method="post" class="rp-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="assign">
            <input type="hidden" name="lead_id" value="<?= (int)$l['id'] ?>">
            <select class="rp-select" name="caller_id" onchange="this.form.submit()" style="font-size:.8rem;padding:.3rem">
              <option value="">Unassigned</option>
              <?php foreach ($callers as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)$l['assigned_to'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
          <form method="post" class="rp-inline" onsubmit="return confirm('Delete this lead?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete-lead">
            <input type="hidden" name="lead_id" value="<?= (int)$l['id'] ?>">
            <button class="rp-btn rp-btn-xs" style="background:#666;color:#fff" type="submit">🗑️</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<?php rp_app_close(); page_foot(); ?>
