<?php
require_once __DIR__ . '/lib.php';
$user = require_login();
$pdo = db();

// Status change (POST).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $orderId = (string)($_POST['order_id'] ?? '');
    $status  = (string)($_POST['status'] ?? '');
    if (in_array($status, RP_ORDER_STATUSES, true)) {
        $up = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ? AND store_id IN (SELECT id FROM stores WHERE owner_id = ?)');
        $up->execute([$status, $orderId, (int)$user['id']]);
        flash($up->rowCount() ? 'Order ' . $orderId . ' ' . rp_status_past($status) . '.' : 'That order was not found.', (bool)$up->rowCount());
    }
    $back = 'orders.php';
    $qs = [];
    if (!empty($_POST['store'])) $qs['store'] = (int)$_POST['store'];
    if (isset($_POST['source']) && in_array($_POST['source'], ['website', 'whatsapp'], true)) $qs['source'] = $_POST['source'];
    if ($qs) $back .= '?' . http_build_query($qs);
    header('Location: ' . $back);
    exit;
}

$storeFilter = (int)($_GET['store'] ?? 0);
$sourceFilter = strtolower(trim((string)($_GET['source'] ?? 'all')));
if (!in_array($sourceFilter, ['all', 'website', 'whatsapp'], true)) $sourceFilter = 'all';
$st = $pdo->prepare('SELECT id, name FROM stores WHERE owner_id = ? ORDER BY name');
$st->execute([(int)$user['id']]);
$myStores = $st->fetchAll();
$storeNames = array_column($myStores, 'name', 'id');

$sql = 'SELECT o.*, s.name AS store_name, s.slug AS store_slug
        FROM orders o JOIN stores s ON s.id = o.store_id
        WHERE s.owner_id = ?';
$params = [(int)$user['id']];
if ($storeFilter && isset($storeNames[$storeFilter])) {
    $sql .= ' AND o.store_id = ?';
    $params[] = $storeFilter;
}
if ($sourceFilter !== 'all') {
    $sql .= ' AND o.source = ?';
    $params[] = $sourceFilter;
}
$sql .= ' ORDER BY o.created_at DESC, o.id DESC LIMIT 200';
$q = $pdo->prepare($sql);
$q->execute($params);
$orders = $q->fetchAll();

$nextStatus = [
    'new'       => ['accepted', 'cancelled'],
    'accepted'  => ['preparing', 'cancelled'],
    'preparing' => ['delivered', 'cancelled'],
    'delivered' => [],
    'cancelled' => [],
];
page_head('Orders');
rp_app_open($user, 'orders', [
    'title' => 'Orders' . ($storeFilter && isset($storeNames[$storeFilter]) ? ' — ' . $storeNames[$storeFilter] : ''),
    'sub'   => 'Website and WhatsApp orders, newest first.',
]);
?>
  <form method="get" style="margin-bottom:1rem;display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
    <label class="rp-label" style="margin:0" for="fStore">Store:</label>
    <select class="rp-select" id="fStore" name="store" onchange="this.form.submit()">
      <option value="0">All stores</option>
      <?php foreach ($myStores as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= $storeFilter === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <label class="rp-label" style="margin:0" for="fSource">Source:</label>
    <select class="rp-select" id="fSource" name="source" onchange="this.form.submit()">
      <option value="all" <?= $sourceFilter === 'all' ? 'selected' : '' ?>>All</option>
      <option value="website" <?= $sourceFilter === 'website' ? 'selected' : '' ?>>Website Direct</option>
      <option value="whatsapp" <?= $sourceFilter === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
    </select>
  </form>

  <?php if (!$orders): ?>
    <?= rp_empty('No orders yet', 'When a customer orders on your website or WhatsApp, it shows up here in seconds.', '', rp_icon('receipt', 26)) ?>
  <?php else: ?>
    <div class="rp-card" style="padding:.4rem .8rem"><div class="rp-tablewrap">
    <table class="rp-table">
      <thead><tr><th>Order</th><th>Store</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Source</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o):
        $items = json_decode($o['items_json'], true) ?: [];
        $itemSummary = implode(', ', array_map(fn($i) => (int)($i['qty'] ?? 1) . '× ' . ($i['name'] ?? ''), array_slice($items, 0, 2)));
        if (count($items) > 2) $itemSummary .= ' +' . (count($items) - 2) . ' more';
      ?>
        <tr>
          <td><a href="order.php?id=<?= e($o['id']) ?>" style="font-weight:700"><?= e($o['id']) ?></a><br><span class="rp-hint"><?= e(pk_time($o['created_at'])) ?></span></td>
          <td><?= e($o['store_name']) ?></td>
          <td><?= e($o['customer_name']) ?><br><span class="rp-hint"><?= e($o['customer_phone']) ?></span></td>
          <td><?= e($itemSummary) ?></td>
          <td><?= rp_money($o['total']) ?><?php if ((float)$o['discount'] > 0): ?><br><span class="rp-hint">−<?= e(money($o['discount'])) ?><?= $o['coupon'] ? ' (' . e($o['coupon']) . ')' : '' ?></span><?php endif; ?></td>
          <td><?= rp_pill($o['status']) ?></td>
          <td><?= rp_source_chip($o['source'] ?? null) ?></td>
          <td>
            <div class="rp-statusbtns">
            <button class="rp-btn rp-btn-line rp-btn-xs" type="button" onclick="rpOpenDrawer('<?= e($o['id']) ?>')">View</button>
            <?php foreach ($nextStatus[$o['status']] ?? [] as $ns): ?>
              <form method="post" class="rp-inline"<?= $ns === 'cancelled' ? ' onsubmit="return confirm(\'Cancel order ' . e($o['id']) . '?\')"' : '' ?>>
                <?= csrf_field() ?>
                <input type="hidden" name="order_id" value="<?= e($o['id']) ?>">
                <input type="hidden" name="status" value="<?= e($ns) ?>">
                <?php if ($storeFilter): ?><input type="hidden" name="store" value="<?= $storeFilter ?>"><?php endif; ?>
                <?php if ($sourceFilter !== 'all'): ?><input type="hidden" name="source" value="<?= e($sourceFilter) ?>"><?php endif; ?>
<?php
              $btnLabel = ['accepted' => 'Accept', 'preparing' => 'Prepare', 'delivered' => 'Deliver', 'cancelled' => 'Cancel'][$ns] ?? ucfirst($ns);
              ?>
                <button class="rp-btn <?= $ns === 'cancelled' ? 'rp-btn-line' : 'rp-btn-red' ?> rp-btn-xs" type="submit"><?= e($btnLabel) ?></button>
              </form>
            <?php endforeach; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div></div>
  <?php endif; ?>

<div class="rp-overlay" id="rpOverlay" onclick="rpCloseDrawer()"></div>
<aside class="rp-drawer" id="rpDrawer" aria-label="Order details">
  <iframe id="rpDrawerFrame" title="Order details"></iframe>
</aside>
<script>
function rpOpenDrawer(id){
  var f=document.getElementById('rpDrawerFrame');
  f.src='order.php?id='+encodeURIComponent(id)+'&embed=1';
  document.getElementById('rpDrawer').classList.add('show');
  document.getElementById('rpOverlay').classList.add('show');
  document.body.style.overflow='hidden';
}
function rpCloseDrawer(){
  document.getElementById('rpDrawer').classList.remove('show');
  document.getElementById('rpOverlay').classList.remove('show');
  document.body.style.overflow='';
  document.getElementById('rpDrawerFrame').src='about:blank';
}
document.addEventListener('keydown',function(e){if(e.key==='Escape')rpCloseDrawer()});
</script>
<?php rp_app_close(); page_foot(); ?>
