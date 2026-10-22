<?php
require_once __DIR__ . '/lib.php';
$user = require_login();
$pdo = db();
$id = (string)($_GET['id'] ?? $_POST['order_id'] ?? '');
$embed = (($_GET['embed'] ?? '') === '1') || (($_POST['embed'] ?? '') === '1');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $status = (string)($_POST['status'] ?? '');
    if (in_array($status, RP_ORDER_STATUSES, true)) {
        $up = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ? AND store_id IN (SELECT id FROM stores WHERE owner_id = ?)');
        $up->execute([$status, $id, (int)$user['id']]);
        flash($up->rowCount() ? 'Order ' . $id . ' ' . rp_status_past($status) . '.' : 'That order was not found.', (bool)$up->rowCount());
    }
    header('Location: order.php?id=' . urlencode($id) . ($embed ? '&embed=1' : ''));
    exit;
}

$st = $pdo->prepare('SELECT o.*, s.name AS store_name, s.slug AS store_slug, s.template AS store_template
                     FROM orders o JOIN stores s ON s.id = o.store_id
                     WHERE o.id = ? AND s.owner_id = ?');
$st->execute([$id, (int)$user['id']]);
$o = $st->fetch();
if (!$o) rp_error_page(404, 'Order not found', 'This order does not exist or belongs to a different account.', 'orders.php', 'Back to orders');
$items = json_decode($o['items_json'], true) ?: [];
$nextStatus = [
    'new'       => ['accepted', 'cancelled'],
    'accepted'  => ['preparing', 'cancelled'],
    'preparing' => ['delivered', 'cancelled'],
    'delivered' => [],
    'cancelled' => [],
];
$actionLabel = ['accepted' => 'Accept order', 'preparing' => 'Start preparing', 'delivered' => 'Mark delivered', 'cancelled' => 'Cancel order'];
$waDigits = normalize_phone((string)($o['customer_phone'] ?? ''))['digits'];
$when = pk_time((string)$o['created_at'], 'g:i A · j M Y');

ob_start();
?>
<div class="dr">
  <div class="dr-head">
    <h2>Order <?= e($o['id']) ?> <?= rp_pill($o['status']) ?></h2>
    <?php if ($embed): ?>
      <button class="dr-x" onclick="if(window.parent&&parent.rpCloseDrawer)parent.rpCloseDrawer()" aria-label="Close">✕</button>
    <?php else: ?>
      <a class="dr-x" href="orders.php" aria-label="Back to orders" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none">✕</a>
    <?php endif; ?>
  </div>
  <div class="dr-meta"><span><?= rp_icon('clock', 14) ?> Received <?= e($when) ?> PKT</span><span class="rp-tpltag"><?= e($o['store_name']) ?></span></div>
  <?php $chip = rp_source_chip($o['source'] ?? null); if ($chip !== ''): ?>
    <div style="margin-bottom:.9rem"><?= $chip ?></div>
  <?php endif; ?>

  <div class="dr-cust">
    <div class="lbl">CUSTOMER</div>
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.8rem">
      <div><div class="nm"><?= e($o['customer_name']) ?></div><div class="ph"><?= e($o['customer_phone']) ?></div></div>
      <?php if ($waDigits !== ''): ?>
        <a class="rp-btn rp-btn-green rp-btn-sm" href="https://wa.me/<?= e($waDigits) ?>" target="_blank" rel="noopener"><?= rp_icon('chat', 15) ?> WhatsApp</a>
      <?php endif; ?>
    </div>
    <div class="addr"><?= rp_icon('pin', 14) ?> <span><?= e($o['customer_address']) ?></span></div>
    <?php if (!empty($o['customer_notes'])): ?>
      <div class="note"><?= rp_icon('note', 14) ?> <b>Note:</b> <?= e($o['customer_notes']) ?></div>
    <?php endif; ?>
  </div>

  <div class="dr-sec">ORDER ITEMS (<?= count($items) ?>)</div>
  <div class="dr-items">
    <?php foreach ($items as $it): ?>
      <div class="dr-item">
        <span class="dr-qty"><?= (int)($it['qty'] ?? 0) ?>x</span>
        <span class="in"><b><?= e($it['name'] ?? '') ?></b></span>
        <span class="amt"><?= e(money(((float)($it['price'] ?? 0)) * ((int)($it['qty'] ?? 0)))) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="dr-totals">
    <?php if ($o['subtotal'] !== null): ?><div class="tr"><span>Subtotal</span><span><?= e(money($o['subtotal'])) ?></span></div><?php endif; ?>
    <?php if ((float)$o['discount'] > 0): ?><div class="tr"><span>Discount<?= $o['coupon'] ? ' (' . e($o['coupon']) . ')' : '' ?></span><span>−<?= e(money($o['discount'])) ?></span></div><?php endif; ?>
    <div class="tr grand"><span>Total</span><span><?= e(money($o['total'])) ?></span></div>
  </div>

  <?php if (!empty($nextStatus[$o['status']])): ?>
    <div class="dr-actions">
      <?php $i = 0; foreach ($nextStatus[$o['status']] as $ns): $i++;
        $primary = $ns !== 'cancelled'; ?>
        <form method="post" class="<?= $primary ? 'grow' : '' ?>" style="<?= $primary ? 'flex:1;display:flex' : '' ?>"
              <?= $ns === 'cancelled' ? 'onsubmit="return confirm(\'Cancel this order?\')"' : '' ?>>
          <?= csrf_field() ?>
          <input type="hidden" name="order_id" value="<?= e($o['id']) ?>">
          <input type="hidden" name="status" value="<?= e($ns) ?>">
          <?php if ($embed): ?><input type="hidden" name="embed" value="1"><?php endif; ?>
          <?php if ($primary): ?>
            <button class="rp-btn rp-btn-red rp-btn-block" type="submit"><?= e($actionLabel[$ns] ?? ucfirst($ns)) ?></button>
          <?php else: ?>
            <button class="rp-linkbtn" type="submit" style="color:var(--rp-red)">Cancel</button>
          <?php endif; ?>
        </form>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="dr-noteline">This order is <b>&nbsp;<?= e($o['status']) ?>&nbsp;</b>.</div>
  <?php endif; ?>
</div>
<?php
$detail = ob_get_clean();

if ($embed) {
    // Drawer mode: bare fragment for the orders.php slide-in (no app shell).
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="assets/style.css">';
    echo '<style>body{background:#fff}</style></head><body>';
    echo $detail;
    echo '</body></html>';
    exit;
}

page_head('Order ' . $o['id']);
rp_app_open($user, 'orders', [
    'title' => 'Order ' . $o['id'],
    'sub'   => '<a href="orders.php">← All orders</a>',
    'crumb' => 'Orders',
]);
echo '<div class="rp-card" style="max-width:680px">' . $detail . '</div>';
rp_app_close();
page_foot();
