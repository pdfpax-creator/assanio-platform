<?php
require_once __DIR__ . '/lib.php';
$user = require_login();
$pdo = db();
$st = $pdo->prepare('SELECT s.*, (SELECT COUNT(*) FROM orders o WHERE o.store_id = s.id) AS order_count,
                     (SELECT COUNT(*) FROM orders o WHERE o.store_id = s.id AND o.status = \'new\') AS new_count
                     FROM stores s WHERE s.owner_id = ? ORDER BY s.created_at DESC');
$st->execute([(int)$user['id']]);
$stores = $st->fetchAll();

// Revenue this month: DELIVERED orders only, across all my stores.
$rev = $pdo->prepare("SELECT COALESCE(SUM(o.total), 0) FROM orders o JOIN stores s ON s.id = o.store_id
                      WHERE s.owner_id = ? AND o.status = 'delivered'
                        AND strftime('%Y-%m', o.created_at) = strftime('%Y-%m', 'now')");
$rev->execute([(int)$user['id']]);
$revenueMonth = (float)$rev->fetchColumn();
$totalOrders = 0;
foreach ($stores as $s) $totalOrders += (int)$s['order_count'];

// Recent orders across all my stores (latest 5).
$rec = $pdo->prepare('SELECT o.id, o.total, o.status, o.created_at, o.customer_name, s.name AS store_name
                      FROM orders o JOIN stores s ON s.id = o.store_id
                      WHERE s.owner_id = ? ORDER BY o.created_at DESC, o.id DESC LIMIT 5');
$rec->execute([(int)$user['id']]);
$recentOrders = $rec->fetchAll();

$firstName = trim((string)strtok((string)($user['name'] ?? ''), ' '));
page_head('Dashboard');
rp_app_open($user, 'dashboard', [
    'title' => rp_greet() . ', ' . ($firstName !== '' ? $firstName : 'there'),
    'title_badge' => '<span class="rp-pill pill-live"><span class="dot"></span>Live</span>',
    'sub'   => 'Here\'s what\'s happening with your restaurants today.',
    'actions' => '<a class="rp-btn rp-btn-red" href="create-store.php">+ New Restaurant</a>',
]);
?>
  <?php if (!$stores): ?>
    <?= rp_empty('No restaurants yet',
        'Pick a template, add your details, and your website builds itself in minutes.',
        '<a class="rp-btn rp-btn-red" href="create-store.php">Create your first restaurant</a>', rp_icon('grid', 26)) ?>
  <?php else: ?>
    <div class="rp-stats">
      <?= rp_stat('My Restaurants', (string)count($stores), count($stores) === 1 ? '1 website online' : 'All websites online') ?>
      <?= rp_stat('Revenue This Month', e(money($revenueMonth)), 'Delivered orders only') ?>
      <?= rp_stat('New Orders', (string)new_orders_count((int)$user['id']), new_orders_count((int)$user['id']) > 0 ? '<b>' . new_orders_count((int)$user['id']) . ' action required</b> · <a href="orders.php">View orders →</a>' : 'All caught up · <a href="orders.php">View orders →</a>') ?>
      <?= rp_stat('Total Orders', (string)$totalOrders, 'Across all stores') ?>
    </div>

    <div style="display:flex;align-items:baseline;justify-content:space-between;gap:1rem;margin:1.4rem 0 .8rem;flex-wrap:wrap">
      <div><h2 style="font-size:19px">My Restaurants</h2>
      <p class="rp-hint">Manage your websites, menus, and incoming customer orders.</p></div>
    </div>
    <div class="rp-stores">
      <?php foreach ($stores as $s):
        $suspended = ($s['status'] ?? 'active') === 'suspended';
        $siteUrl = rp_store_url($s);
        $siteHost = (string)preg_replace('#^https?://#', '', $siteUrl);
      ?>
        <div class="rp-store">
          <div class="top">
            <h3><?= e($s['name']) ?></h3>
            <?= $suspended ? '<span class="rp-pill pill-gray">Suspended</span>'
                          : '<span class="rp-pill pill-live"><span class="dot"></span>Live</span>' ?>
          </div>
          <span class="rp-tpltag"><?= e(RP_TEMPLATES[$s['template']]['name'] ?? $s['template']) ?> Template</span>
          <?php if (!empty($s['city'])): ?><p class="loc"><?= rp_icon('pin', 14) ?> <?= e($s['city']) ?></p><?php endif; ?>
          <div class="slugrow"><a href="<?= e($siteUrl) ?>" target="_blank" rel="noopener"><?= e($siteHost) ?></a><?php if ((int)$s['new_count']): ?> · <span class="rp-badge"><?= (int)$s['new_count'] ?> new</span><?php endif; ?></div>
          <div class="acts">
            <a class="rp-btn rp-btn-red rp-btn-sm" href="edit-website.php?id=<?= (int)$s['id'] ?>" target="_blank" style="display:inline-flex;align-items:center;gap:.35rem"><?= rp_icon('pencil', 14) ?> Edit website</a>
            <a class="rp-btn rp-btn-dark rp-btn-sm" href="orders.php?store=<?= (int)$s['id'] ?>">Orders<?php if ((int)$s['order_count']): ?> (<?= (int)$s['order_count'] ?>)<?php endif; ?></a>
            <a class="rp-btn rp-btn-line rp-btn-sm" href="store-settings.php?id=<?= (int)$s['id'] ?>">Settings</a>
<a class="rp-btn rp-btn-line rp-btn-sm" href="<?= e($s['slug']) ?>/" target="_blank">View site ↗</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($recentOrders): ?>
    <div class="rp-card" style="margin-top:1.4rem">
      <div style="display:flex;align-items:baseline;justify-content:space-between;gap:1rem;margin-bottom:.6rem;flex-wrap:wrap">
        <div><h2>Recent Orders</h2><p class="rp-hint">Latest orders across all your restaurants.</p></div>
        <a href="orders.php" style="font-weight:700;font-size:13.5px">View all orders →</a>
      </div>
      <div class="rp-tablewrap"><table class="rp-table">
        <thead><tr><th>Order</th><th>Restaurant</th><th>Customer</th><th>Total</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recentOrders as $o): ?>
          <tr>
            <td><b><?= e($o['id']) ?></b><br><span class="rp-hint"><?= e(pk_time($o['created_at'])) ?></span></td>
            <td><?= e($o['store_name']) ?></td>
            <td><?= e($o['customer_name']) ?></td>
            <td><?= rp_money($o['total']) ?></td>
            <td><?= rp_pill($o['status']) ?></td>
            <td><a class="rp-btn rp-btn-line rp-btn-xs" href="order.php?id=<?= e($o['id']) ?>">View</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
    <?php endif; ?>
  <?php endif; ?>
<?php rp_app_close(); page_foot(); ?>
