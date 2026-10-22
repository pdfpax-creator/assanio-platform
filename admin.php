<?php
// Super-admin panel: every owner, every store, every order — plus per-store
// plan / payment / renewal controls and Suspend / Activate.
require_once __DIR__ . '/lib.php';
$user = require_super();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action  = (string)($_POST['action'] ?? '');

    // User actions (no store needed)
    if ($action === 'user-approve') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $pdo->prepare('UPDATE users SET approved = 1 WHERE id = ?')->execute([$userId]);
        flash('User approved! They can now login.');
        header('Location: admin.php');
        exit;
    } elseif ($action === 'user-reject') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $pdo->prepare('DELETE FROM users WHERE id = ? AND role != "super"')->execute([$userId]);
        flash('User rejected and removed.');
        header('Location: admin.php');
        exit;
    } elseif ($action === 'save-discover') {
        setting_set('discover_title', trim((string)($_POST['discover_title'] ?? '')));
        setting_set('discover_subtitle', trim((string)($_POST['discover_subtitle'] ?? '')));
        flash('Discover page updated!');
        header('Location: admin.php');
        exit;
    }

    // Store actions (need valid store)
    $storeId = (int)($_POST['store_id'] ?? 0);
    $st = $pdo->prepare('SELECT * FROM stores WHERE id = ?');
    $st->execute([$storeId]);
    $store = $st->fetch();
    if (!$store) {
        flash('Store not found.', false);
    } elseif ($action === 'store-plan') {
        $plan    = (string)($_POST['plan'] ?? 'starter');
        $pay     = (string)($_POST['payment_status'] ?? 'unpaid');
        $renewal = trim((string)($_POST['renewal_date'] ?? ''));
        if (!in_array($plan, RP_PLANS, true)) $plan = 'starter';
        if (!in_array($pay, ['paid', 'unpaid'], true)) $pay = 'unpaid';
        if ($renewal !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $renewal)) $renewal = '';
        $pdo->prepare('UPDATE stores SET plan = ?, payment_status = ?, renewal_date = ? WHERE id = ?')
            ->execute([$plan, $pay, $renewal, $storeId]);
        flash('Plan details saved for "' . $store['name'] . '".');
    } elseif ($action === 'store-suspend') {
        set_store_suspended($store, true);
        flash('"' . $store['name'] . '" is suspended — its website now shows the unavailable screen.');
    } elseif ($action === 'store-activate') {
        set_store_suspended($store, false);
        flash('"' . $store['name'] . '" is active again.');
    } elseif ($action === 'store-delete') {
        $slug = $store['slug'];
        // Delete store files
        $storeDir = RP_STORES_DIR . '/' . $slug;
        if (is_dir($storeDir)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($storeDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
            @rmdir($storeDir);
        }
        // Delete from DB (orders cascade)
        $pdo->prepare('DELETE FROM stores WHERE id = ?')->execute([$storeId]);
        flash('"' . $store['name'] . '" deleted permanently.');
    } elseif ($action === 'toggle-discover') {
        $show = (int)($_POST['show'] ?? 0) ? 1 : 0;
        $pdo->prepare('UPDATE stores SET show_in_discover = ? WHERE id = ?')->execute([$show, $storeId]);
        flash('"' . $store['name'] . '" ' . ($show ? 'will now appear' : 'hidden from') . ' Discover page.');
    }
    header('Location: admin.php');
    exit;
}

// Pending approvals
$pending = $pdo->query("SELECT id, name, email, created_at FROM users
                        WHERE approved = 0 AND role = 'owner' ORDER BY created_at DESC")->fetchAll();

$owners = $pdo->query('SELECT u.id, u.name, u.email, u.role, u.created_at,
                              (SELECT COUNT(*) FROM stores s WHERE s.owner_id = u.id) AS store_count,
                              (SELECT COUNT(*) FROM orders o JOIN stores s ON s.id = o.store_id WHERE s.owner_id = u.id) AS order_count
                       FROM users u ORDER BY u.created_at')->fetchAll();
$stores = $pdo->query('SELECT s.*, u.name AS owner_name, u.email AS owner_email,
                              (SELECT COUNT(*) FROM orders o WHERE o.store_id = s.id) AS order_count
                       FROM stores s JOIN users u ON u.id = s.owner_id ORDER BY s.created_at DESC')->fetchAll();
$orders = $pdo->query('SELECT o.*, s.name AS store_name, u.name AS owner_name
                       FROM orders o JOIN stores s ON s.id = o.store_id JOIN users u ON u.id = s.owner_id
                       ORDER BY o.created_at DESC, o.id DESC LIMIT 100')->fetchAll();

page_head('Super Admin');
rp_app_open($user, 'admin', [
    'title' => 'Super Admin',
    'sub'   => 'All owners, stores and orders across ' . PLATFORM_NAME . '. Handle with care — suspension takes a store\'s website offline.',
    'crumb' => 'Admin',
]);
?>
  <?php if ($pending): ?>
  <div class="rp-card" style="border:2px solid #e8a838">
    <h2>⏳ Pending Approvals (<?= count($pending) ?>)</h2>
    <p class="rp-hint">These users signed up but haven't been approved yet. Verify their payment on WhatsApp, then approve.</p>
    <div class="rp-table-wrap"><table class="rp-table">
      <thead><tr><th>Name</th><th>Email</th><th>Signed Up</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($pending as $p): ?>
          <tr>
            <td><b><?= e($p['name']) ?></b></td>
            <td><?= e($p['email']) ?></td>
            <td><?= e(date('d M Y', strtotime($p['created_at']))) ?></td>
            <td>
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="user-approve">
                <input type="hidden" name="user_id" value="<?= (int)$p['id'] ?>">
                <button class="rp-btn rp-btn-red rp-btn-sm" type="submit">✅ Approve</button>
              </form>
              <form method="post" style="display:inline" onsubmit="return confirm('Reject and delete this user?')">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="user-reject">
                <input type="hidden" name="user_id" value="<?= (int)$p['id'] ?>">
                <button class="rp-btn rp-btn-sm" type="submit">❌ Reject</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
  <?php endif; ?>
  <div class="rp-stats" style="grid-template-columns:repeat(3,1fr)">
    <?= rp_stat('Owners', (string)count($owners)) ?>
    <?= rp_stat('Stores', (string)count($stores)) ?>
    <?= rp_stat('Orders (latest 100)', (string)count($orders)) ?>
  </div>

  <div class="rp-card">
    <h2>Owners (<?= count($owners) ?>)</h2>
    <div class="rp-tablewrap"><table class="rp-table">
      <thead><tr><th>Owner</th><th>Email</th><th>Role</th><th>Stores</th><th>Orders</th><th>Joined</th></tr></thead>
      <tbody>
      <?php foreach ($owners as $o): ?>
        <tr>
          <td><b><?= e($o['name']) ?></b></td><td><?= e($o['email']) ?></td>
          <td><?= ($o['role'] ?? 'owner') === 'super' ? '<span class="rp-pill pill-accepted"><span class="dot"></span>Super</span>' : '<span class="rp-hint">owner</span>' ?></td>
          <td><?= (int)$o['store_count'] ?></td><td><?= (int)$o['order_count'] ?></td>
          <td><span class="rp-hint"><?= e(pk_time($o['created_at'], 'j M Y')) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>

  <div class="rp-card">
    <h2>Stores (<?= count($stores) ?>)</h2>
    <div class="rp-tablewrap"><table class="rp-table">
      <thead><tr><th>Store</th><th>Owner</th><th>Plan / Payment / Renewal</th><th>Status</th><th>Controls</th></tr></thead>
      <tbody>
      <?php foreach ($stores as $s): ?>
        <tr>
          <td><b><?= e($s['name']) ?></b><br><span class="rp-hint"><?= e($s['slug']) ?> · <?= e(RP_TEMPLATES[$s['template']]['name'] ?? $s['template']) ?> · <?= (int)$s['order_count'] ?> orders</span></td>
          <td><?= e($s['owner_name']) ?><br><span class="rp-hint"><?= e($s['owner_email']) ?></span></td>
          <td>
            <form method="post" style="display:flex;gap:.4rem;flex-wrap:wrap;align-items:center">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="store-plan">
              <input type="hidden" name="store_id" value="<?= (int)$s['id'] ?>">
              <select class="rp-select" name="plan">
                <?php foreach (RP_PLANS as $p): ?><option value="<?= e($p) ?>" <?= ($s['plan'] ?? 'starter') === $p ? 'selected' : '' ?>><?= e(ucfirst($p)) ?></option><?php endforeach; ?>
              </select>
              <select class="rp-select" name="payment_status">
                <?php foreach (['paid', 'unpaid'] as $p): ?><option value="<?= e($p) ?>" <?= ($s['payment_status'] ?? 'unpaid') === $p ? 'selected' : '' ?>><?= e(ucfirst($p)) ?></option><?php endforeach; ?>
              </select>
              <input class="rp-input" type="date" name="renewal_date" value="<?= e($s['renewal_date'] ?? '') ?>" style="width:auto;min-height:38px">
              <button class="rp-btn rp-btn-dark rp-btn-xs" type="submit">Save</button>
            </form>
          </td>
          <td>
            <?php if (($s['status'] ?? 'active') === 'suspended'): ?><span class="rp-pill pill-gray">Suspended</span><?php else: ?><span class="rp-pill pill-delivered"><span class="dot"></span>Active</span><?php endif; ?>
          </td>
          <td>
            <a class="rp-btn rp-btn-dark rp-btn-xs" href="edit-website.php?id=<?= (int)$s['id'] ?>" target="_blank">Edit Website</a>
            <form method="post" class="rp-inline" title="<?= ($s['show_in_discover'] ?? 1) ? 'Showing in Discover — click to hide' : 'Hidden from Discover — click to show' ?>">
              <?= csrf_field() ?><input type="hidden" name="action" value="toggle-discover"><input type="hidden" name="store_id" value="<?= (int)$s['id'] ?>">
              <input type="hidden" name="show" value="<?= ($s['show_in_discover'] ?? 1) ? '0' : '1' ?>">
              <button class="rp-btn rp-btn-xs" style="<?= ($s['show_in_discover'] ?? 1) ? 'background:#1a9e4b;color:#fff' : 'background:#ddd;color:#666' ?>" type="submit"><?= ($s['show_in_discover'] ?? 1) ? 'Visible' : 'Hidden' ?></button>
            </form>
            <?php if (($s['status'] ?? 'active') === 'suspended'): ?>
              <form method="post" class="rp-inline" onsubmit="return confirm('Activate <?= e($s['name']) ?>? Its website goes live again.')">
                <?= csrf_field() ?><input type="hidden" name="action" value="store-activate"><input type="hidden" name="store_id" value="<?= (int)$s['id'] ?>">
                <button class="rp-btn rp-btn-green rp-btn-xs" type="submit">Activate</button>
              </form>
            <?php else: ?>
              <form method="post" class="rp-inline" onsubmit="return confirm('Suspend <?= e($s['name']) ?>? Its website will show an unavailable screen.')">
                <?= csrf_field() ?><input type="hidden" name="action" value="store-suspend"><input type="hidden" name="store_id" value="<?= (int)$s['id'] ?>">
                <button class="rp-btn rp-btn-xs" style="background:#e67e22;color:#fff" type="submit">Suspend</button>
              </form>
            <?php endif; ?>
            <form method="post" class="rp-inline" onsubmit="return confirm('DELETE <?= e($s['name']) ?>? This cannot be undone!')">
              <?= csrf_field() ?><input type="hidden" name="action" value="store-delete"><input type="hidden" name="store_id" value="<?= (int)$s['id'] ?>">
              <button class="rp-btn rp-btn-red rp-btn-xs" type="submit" title="Delete store">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>

  <div class="rp-card">
    <h2>Latest orders</h2>
    <?php if (!$orders): ?><p class="rp-hint">No orders anywhere yet.</p><?php else: ?>
    <div class="rp-tablewrap"><table class="rp-table">
      <thead><tr><th>Order</th><th>Store / Owner</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><b><?= e($o['id']) ?></b><br><span class="rp-hint"><?= e(pk_time($o['created_at'])) ?></span></td>
          <td><?= e($o['store_name']) ?><br><span class="rp-hint"><?= e($o['owner_name']) ?></span></td>
          <td><?= e($o['customer_name']) ?><br><span class="rp-hint"><?= e($o['customer_phone']) ?></span></td>
          <td><?= rp_money($o['total']) ?></td>
          <td><?= rp_pill($o['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </div>

  <div class="rp-card">
    <h2>Discover Page Settings</h2>
    <p class="rp-hint">Edit what customers see on the <a href="discover.php" target="_blank">Discover page</a>.</p>
    <form method="post" style="display:grid;gap:.8rem;max-width:600px">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save-discover">
      <div>
        <label style="font-weight:600;display:block;margin-bottom:.3rem">Title</label>
        <input class="rp-input" type="text" name="discover_title" value="<?= e(setting_get('discover_title', '🍽️ Discover Restaurants')) ?>" style="width:100%">
      </div>
      <div>
        <label style="font-weight:600;display:block;margin-bottom:.3rem">Subtitle</label>
        <input class="rp-input" type="text" name="discover_subtitle" value="<?= e(setting_get('discover_subtitle', '')) ?>" style="width:100%">
      </div>
      <div>
        <button class="rp-btn rp-btn-red" type="submit">Save Discover Page</button>
      </div>
    </form>
  </div>
<?php rp_app_close(); page_foot(); ?>
