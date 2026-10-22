<?php
require_once __DIR__ . '/lib.php';
$user = require_login();

// Only callers can access
if (($user['role'] ?? 'owner') !== 'caller') {
    header('Location: dashboard.php');
    exit;
}

$pdo = db();
$stats = mlm_caller_stats($pdo, (int)$user['id']);

// My clients
$st = $pdo->prepare("
    SELECT r.id as referral_id, r.plan, r.created_at,
           u.name as client_name, u.email as client_email,
           (SELECT COUNT(*) FROM stores s WHERE s.owner_id = u.id) as store_count
    FROM referrals r
    JOIN users u ON u.id = r.client_id
    WHERE r.caller_id = ? AND r.status = 'active'
    ORDER BY r.created_at DESC
");
$st->execute([(int)$user['id']]);
$clients = $st->fetchAll();

// Commission history
$st = $pdo->prepare("
    SELECT c.month, c.amount, c.type, c.paid, c.paid_at,
           u.name as client_name
    FROM commissions c
    JOIN referrals r ON r.id = c.referral_id
    JOIN users u ON u.id = r.client_id
    WHERE r.caller_id = ?
    ORDER BY c.month DESC, c.created_at DESC
    LIMIT 50
");
$st->execute([(int)$user['id']]);
$history = $st->fetchAll();

$firstName = trim((string)strtok((string)($user['name'] ?? ''), ' '));
page_head('My Earnings');
rp_app_open($user, 'caller', [
    'title_html' => e('My Earnings, ' . ($firstName !== '' ? $firstName : 'there')),
    'sub' => 'Track your clients and commissions.',
]);
?>
  <div class="rp-stats">
    <div class="rp-stat">
      <div class="rp-stat-num"><?= (int)($stats['clients'] ?? 0) ?></div>
      <div class="rp-stat-lbl">Active Clients</div>
    </div>
    <div class="rp-stat">
      <div class="rp-stat-num">Rs <?= number_format((int)($stats['pending'] ?? 0)) ?></div>
      <div class="rp-stat-lbl">Pending Commission</div>
    </div>
    <div class="rp-stat">
      <div class="rp-stat-num">Rs <?= number_format((int)($stats['paid'] ?? 0)) ?></div>
      <div class="rp-stat-lbl">Total Earned</div>
    </div>
  </div>

  <div class="rp-card">
    <h2>My Clients</h2>
    <?php if (!$clients): ?>
      <p class="rp-muted">No clients yet. Bring your first restaurant to start earning!</p>
    <?php else: ?>
      <div class="rp-table-wrap"><table class="rp-table">
        <thead><tr><th>Client</th><th>Plan</th><th>Stores</th><th>Since</th></tr></thead>
        <tbody>
          <?php foreach ($clients as $c): ?>
            <tr>
              <td><b><?= e($c['client_name']) ?></b><br><small><?= e($c['client_email']) ?></small></td>
              <td><span class="rp-pill"><?= e(ucfirst($c['plan'])) ?></span></td>
              <td><?= (int)$c['store_count'] ?></td>
              <td><?= e(date('M Y', strtotime($c['created_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>

  <div class="rp-card">
    <h2>Commission History</h2>
    <?php if (!$history): ?>
      <p class="rp-muted">No commissions yet.</p>
    <?php else: ?>
      <div class="rp-table-wrap"><table class="rp-table">
        <thead><tr><th>Month</th><th>Client</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($history as $h): ?>
            <tr>
              <td><?= e($h['month']) ?></td>
              <td><?= e($h['client_name']) ?></td>
              <td><?= e(ucfirst($h['type'])) ?></td>
              <td><b>Rs <?= number_format((int)$h['amount']) ?></b></td>
              <td><?= $h['paid'] ? '<span class="rp-pill pill-live">Paid</span>' : '<span class="rp-pill">Pending</span>' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>

  <div class="rp-card">
    <h2>How It Works</h2>
    <ul class="rp-list">
      <li><b>Closing bonus:</b> Rs 1,000-2,000 when your client makes their first payment</li>
      <li><b>Monthly recurring:</b> Rs 200-500 every month your client stays active</li>
      <li><b>Keep them happy:</b> The longer they stay, the more you earn</li>
    </ul>
  </div>
<?php rp_app_close(); page_foot(); ?>
