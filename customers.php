<?php
require_once __DIR__ . '/lib.php';
$user = require_login();
$pdo = db();

$isSuper = ($user['role'] ?? '') === 'super';

// Get stores: super sees ALL, owner sees own
if ($isSuper) {
    $stores = $pdo->query('SELECT id, name FROM stores ORDER BY created_at DESC')->fetchAll();
} else {
    $st = $pdo->prepare('SELECT id, name FROM stores WHERE owner_id = ? ORDER BY created_at DESC');
    $st->execute([(int)$user['id']]);
    $stores = $st->fetchAll();
}

$storeId = (int)($_GET['store'] ?? ($stores[0]['id'] ?? 0));

// Check for "all" mode (super admin only)
$showAll = $isSuper && (($_GET['store'] ?? '') === 'all');

// Verify ownership
$valid = false;
foreach ($stores as $s) { if ((int)$s['id'] === $storeId) { $valid = true; $storeName = $s['name']; break; } }
if (!$valid && !$showAll) { $storeId = 0; }

// Get customers: all stores or single store
if ($showAll) {
    $customers = $pdo->query("SELECT c.*, s.name AS store_name FROM customers c
                              JOIN stores s ON s.id = c.store_id
                              ORDER BY c.total_spent DESC LIMIT 500")->fetchAll();
    $storeName = 'All Restaurants';
} else {
    $customers = $storeId ? get_store_customers($pdo, $storeId) : [];
}

// Handle CSV export
if (isset($_GET['export']) && ($storeId || $showAll)) {
    if ($showAll) {
        $expCustomers = $pdo->query("SELECT c.*, s.name AS store_name FROM customers c
                                    JOIN stores s ON s.id = c.store_id ORDER BY c.total_spent DESC LIMIT 10000")->fetchAll();
    } else {
        $expCustomers = get_store_customers($pdo, $storeId, 10000);
    }
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="customers-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name', 'Phone', 'Address', 'Store', 'Total Orders', 'Total Spent (Rs)', 'Last Order']);
    foreach ($expCustomers as $c) {
        fputcsv($out, [$c['name'], $c['phone'], $c['address'], $c['store_name'] ?? '', $c['total_orders'], $c['total_spent'], $c['last_order']]);
    }
    fclose($out);
    exit;
}

$customers = $storeId ? get_store_customers($pdo, $storeId) : [];
$totalCustomers = count($customers);
$totalRevenue = array_sum(array_map(fn($c) => (float)$c['total_spent'], $customers));

page_head('My Customers');
rp_app_open($user, 'customers', [
    'title' => 'My Customers',
    'sub'   => 'Har order se customer data auto-save hota hai. Marketing ke liye use karo.',
]);
?>

<?php if (!$stores): ?>
  <?= rp_empty('No restaurants yet', 'Create a restaurant first.', '<a class="rp-btn rp-btn-red" href="create-store.php">Create restaurant</a>') ?>
<?php else: ?>
  <div class="rp-card">
    <form method="get" class="rp-form-inline">
      <label>Restaurant:
        <select name="store" class="rp-select" onchange="this.form.submit()">
          <?php if ($isSuper): ?>
            <option value="all" <?= ($_GET['store'] ?? '') === 'all' ? 'selected' : '' ?>>🌐 All Restaurants</option>
          <?php endif; ?>
          <?php foreach ($stores as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= (int)$s['id'] === $storeId ? 'selected' : '' ?>><?= e($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php if (($storeId && $customers) || ($showAll && $customers)): ?>
        <a class="rp-btn rp-btn-red" href="?store=<?= $showAll ? 'all' : $storeId ?>&export=1">📥 Download CSV</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="rp-stats" style="grid-template-columns:repeat(3,1fr)">
    <?= rp_stat('Total Customers', (string)$totalCustomers) ?>
    <?= rp_stat('Revenue from them', 'Rs ' . number_format($totalRevenue)) ?>
    <?= rp_stat('Avg per customer', $totalCustomers ? 'Rs ' . number_format($totalRevenue / $totalCustomers) : 'Rs 0') ?>
  </div>

  <div class="rp-card">
    <h2>📢 Offer Bhejo (WhatsApp)</h2>
    <p class="rp-hint">Neeche apni offer likho, phir har customer ke saamne <b>WhatsApp button</b> dabao. Message ready khulega — bas Send dabana hai.</p>
    <div class="rp-form-grid" style="max-width:600px">
      <label>Tumhari offer likho:
        <textarea id="offerMsg" class="rp-input" rows="3" style="width:100%;margin-top:4px">Assalam-o-Alaikum! 🎉 <?= e($storeName ?? '') ?> mein aaj SPECIAL DEAL hai — 20% OFF har order pe! Order karo: <?= 'https://app.pdfpax.com/' ?></textarea>
      </label>
    </div>
  </div>

  <div class="rp-card">
    <h2>Customer List</h2>
    <p class="rp-hint">💡 <b>Marketing tip:</b> CSV download karo → WhatsApp broadcast mein numbers add karo → deals bhejo. Repeat customers = free marketing!</p>
    <?php if (!$customers): ?>
      <p class="rp-muted">Abhi koi customer nahi. Jab orders ayenge, yahan list banegi.</p>
    <?php else: ?>
      <div class="rp-table-wrap"><table class="rp-table">
        <thead><tr><th>Naam</th><th>Phone</th><?php if ($showAll): ?><th>Restaurant</th><?php endif; ?><th>Orders</th><th>Total</th><th>WhatsApp</th></tr></thead>
        <tbody>
          <?php foreach ($customers as $c): ?>
            <?php
              // Normalize phone for wa.me (Pakistan)
              $waPhone = preg_replace('/[^0-9]/', '', $c['phone']);
              if (str_starts_with($waPhone, '0')) $waPhone = '92' . substr($waPhone, 1);
            ?>
            <tr>
              <td><b><?= e($c['name'] ?: '—') ?></b></td>
              <td><code><?= e($c['phone']) ?></code></td>
              <?php if ($showAll): ?><td><?= e($c['store_name'] ?? '—') ?></td><?php endif; ?>
              <td><?= (int)$c['total_orders'] ?>x</td>
              <td><b>Rs <?= number_format((float)$c['total_spent']) ?></b></td>
              <td>
                <button class="rp-btn rp-btn-red rp-btn-xs wa-send" data-phone="<?= e($waPhone) ?>">📱 Send Offer</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
      <script>
        document.querySelectorAll('.wa-send').forEach(btn => {
          btn.addEventListener('click', function() {
            var phone = this.getAttribute('data-phone');
            var msg = document.getElementById('offerMsg').value;
            var url = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(msg);
            window.open(url, '_blank');
          });
        });
      </script>
    <?php endif; ?>
  </div>

  <div class="rp-card">
    <h2>📢 WhatsApp Marketing Templates</h2>
    <p class="rp-hint">Copy karo aur broadcast mein bhejo:</p>
    <div style="background:#f5f5f5;padding:12px;border-radius:8px;margin:8px 0;font-size:14px">
      <b>Deal Alert:</b><br>
      Assalam-o-Alaikum! <?= e($storeName ?? 'Hamare restaurant') ?> mein aaj <b>20% OFF</b> hai! Order karo: [tumhari website ka link]
    </div>
    <div style="background:#f5f5f5;padding:12px;border-radius:8px;margin:8px 0;font-size:14px">
      <b>Come Back:</b><br>
      Miss kar rahe hain aapko! Wapas aao aur <b>FREE drink</b> pao har Rs 1000 ke order pe. 🎉
    </div>
  </div>
<?php endif; ?>

<?php rp_app_close(); page_foot(); ?>
