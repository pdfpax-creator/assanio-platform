<?php
// MASTER ADMIN PANEL - One login, everything here.
require_once __DIR__ . '/lib.php';
$user = require_login();
$pdo = db();

// Only super admin
if (($user['role'] ?? '') !== 'super') {
    header('Location: dashboard.php');
    exit;
}

// Get all stores
$stores = $pdo->query("SELECT id, name, slug, template, whatsapp, city, created_at FROM stores ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$totalStores = count($stores);
$totalLeads = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
$totalCallers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='caller'")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// Get callers
$callers = $pdo->query("SELECT id, name, email, referral_code FROM users WHERE role='caller' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get recent leads
$leads = $pdo->query("SELECT l.*, u.name as caller_name FROM leads l LEFT JOIN users u ON l.assigned_to = u.id ORDER BY l.created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

rp_app_open($user, 'master', ['title' => '🔑 Master Admin', 'sub' => 'Sab kuch ek jagah — stores, leads, callers, orders']);
?>

<style>
.master-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.stat-card { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; }
.stat-card .num { font-size: 2.5rem; font-weight: 800; color: #1b2a4a; }
.stat-card .lbl { color: #666; font-size: 0.9rem; margin-top: 0.3rem; }
.section { background: #fff; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.section h3 { margin-bottom: 1rem; color: #1b2a4a; }
.store-row { display: flex; align-items: center; justify-content: space-between; padding: 0.8rem; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 0.5rem; }
.store-row:last-child { border-bottom: none; }
.btn { display: inline-block; padding: 0.4rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600; margin: 0.2rem; }
.btn-blue { background: #1b2a4a; color: #fff; }
.btn-green { background: #2a9d8f; color: #fff; }
.btn-orange { background: #f4a261; color: #fff; }
.tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.tab { padding: 0.6rem 1.2rem; border-radius: 8px; background: #f0f0f0; cursor: pointer; font-weight: 600; border: none; }
.tab.active { background: #1b2a4a; color: #fff; }
.tab-content { display: none; }
.tab-content.active { display: block; }
table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
th, td { padding: 0.6rem; text-align: left; border-bottom: 1px solid #eee; }
th { background: #f8f9fa; font-weight: 600; }
</style>

<div class="tabs">
  <button class="tab active" onclick="showTab('stores')">🏪 Stores</button>
  <button class="tab" onclick="showTab('leads')">📞 Leads</button>
  <button class="tab" onclick="showTab('callers')">👥 Callers</button>
  <button class="tab" onclick="showTab('links')">🔗 Quick Links</button>
</div>

<!-- STATS -->
<div class="master-grid">
  <div class="stat-card"><div class="num"><?= $totalStores ?></div><div class="lbl">Websites</div></div>
  <div class="stat-card"><div class="num"><?= $totalLeads ?></div><div class="lbl">Leads</div></div>
  <div class="stat-card"><div class="num"><?= $totalCallers ?></div><div class="lbl">Callers</div></div>
  <div class="stat-card"><div class="num"><?= $totalOrders ?></div><div class="lbl">Orders</div></div>
</div>

<!-- STORES TAB -->
<div id="tab-stores" class="tab-content active">
  <div class="section">
    <h3>🏪 All Websites</h3>
    <?php foreach ($stores as $s): ?>
    <div class="store-row">
      <div>
        <strong><?= e($s['name']) ?></strong>
        <span style="color:#888;font-size:0.85rem;">(<?= e($s['slug']) ?>)</span><br>
        <span style="font-size:0.8rem;color:#666;"><?= e($s['city']) ?> • <?= e($s['template']) ?></span>
      </div>
      <div>
        <a class="btn btn-green" href="/<?= e($s['slug']) ?>/" target="_blank">👁 View</a>
        <a class="btn btn-blue" href="/stores/<?= e($s['slug']) ?>/admin/" target="_blank">⚙️ Admin</a>
        <a class="btn btn-orange" href="store-settings.php?id=<?= $s['id'] ?>">📝 Edit</a>
      </div>
    </div>
    <?php endforeach; ?>
    <p style="margin-top:1rem;font-size:0.85rem;color:#666;">
      💡 <strong>Tip:</strong> Store admin passwords are in your <code>ASSANIO-STORE-PASSWORDS.md</code> file.
      <br>Super admin can reset any store password from <a href="admin.php">Super Admin</a>.
    </p>
  </div>
</div>

<!-- LEADS TAB -->
<div id="tab-leads" class="tab-content">
  <div class="section">
    <h3>📞 Recent Leads</h3>
    <table>
      <tr><th>Restaurant</th><th>Phone</th><th>City</th><th>Caller</th><th>Status</th></tr>
      <?php foreach ($leads as $l): ?>
      <tr>
        <td><?= e($l['restaurant_name']) ?></td>
        <td><a href="tel:<?= e($l['phone']) ?>"><?= e($l['phone']) ?></a></td>
        <td><?= e($l['city']) ?></td>
        <td><?= e($l['caller_name'] ?? 'Unassigned') ?></td>
        <td><?= e($l['status']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <a class="btn btn-blue" href="admin-leads.php" style="margin-top:1rem;">Manage All Leads →</a>
  </div>
</div>

<!-- CALLERS TAB -->
<div id="tab-callers" class="tab-content">
  <div class="section">
    <h3>👥 Callers</h3>
    <table>
      <tr><th>Name</th><th>Email</th><th>Referral Code</th><th>Action</th></tr>
      <?php foreach ($callers as $c): ?>
      <tr>
        <td><?= e($c['name']) ?></td>
        <td><?= e($c['email']) ?></td>
        <td><code><?= e($c['referral_code']) ?></code></td>
        <td><a class="btn btn-blue" href="caller-crm.php?caller=<?= $c['id'] ?>">View Leads</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <a class="btn btn-blue" href="admin-referrals.php" style="margin-top:1rem;">Manage Referrals →</a>
  </div>
</div>

<!-- LINKS TAB -->
<div id="tab-links" class="tab-content">
  <div class="section">
    <h3>🔗 Quick Links — Sab Kuch Ek Jagah</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
      <a class="btn btn-blue" href="admin.php" style="padding:1rem;text-align:center;">🛡️ Super Admin</a>
      <a class="btn btn-blue" href="admin-leads.php" style="padding:1rem;text-align:center;">📞 All Leads</a>
      <a class="btn btn-blue" href="admin-referrals.php" style="padding:1rem;text-align:center;">💰 Referrals & Commissions</a>
      <a class="btn btn-blue" href="caller-crm.php" style="padding:1rem;text-align:center;">👥 Caller CRM</a>
      <a class="btn btn-blue" href="orders.php" style="padding:1rem;text-align:center;">📦 Orders</a>
      <a class="btn btn-blue" href="create-store.php" style="padding:1rem;text-align:center;">➕ Create Website</a>
      <a class="btn btn-blue" href="templates.php" style="padding:1rem;text-align:center;">🎨 Templates</a>
      <a class="btn btn-blue" href="dashboard.php" style="padding:1rem;text-align:center;">📊 Dashboard</a>
    </div>
  </div>
</div>

<script>
function showTab(name) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
  event.target.classList.add('active');
  document.getElementById('tab-' + name).classList.add('active');
}
</script>

<?php rp_app_close(); ?>
