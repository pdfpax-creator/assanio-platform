<?php
// Admin: Password Reset Requests — view pending resets, copy link to share via WhatsApp
require_once __DIR__ . '/lib.php';
$user = require_login();
if ($user['role'] !== 'super') { header('Location: dashboard.php'); exit; }

$pdo = db();
$pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,
    used INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");

// Handle revoke
if (($_GET['revoke'] ?? '') !== '' && isset($_GET['csrf']) && $_GET['csrf'] === ($_SESSION['csrf'] ?? '')) {
    $pdo->prepare("UPDATE password_resets SET used=1 WHERE token=?")->execute([$_GET['revoke']]);
    flash('Reset link revoked.');
    header('Location: admin-resets.php'); exit;
}

$resets = $pdo->query("
    SELECT pr.*, u.name, u.email
    FROM password_resets pr
    JOIN users u ON u.id = pr.user_id
    ORDER BY pr.created_at DESC LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

page_head('Password Resets');
rp_app_open($user, 'resets', ['title' => 'Password Reset Requests', 'sub' => 'Share the link via WhatsApp with the user', 'crumb' => 'Resets']);
?>
<div class="rp-card">
  <p style="color:#666;margin-bottom:1rem">When a user requests a password reset, the link appears here. Copy it and send via WhatsApp. Links expire after 1 hour.</p>
  <?php if (!$resets): ?>
    <p style="color:#999">No reset requests yet.</p>
  <?php else: ?>
  <table class="rp-table">
    <tr><th>User</th><th>Email</th><th>Requested</th><th>Expires</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($resets as $r):
      $expired = strtotime($r['expires_at']) < time();
      $link = 'https://' . PLATFORM_DOMAIN . '/reset-password.php?token=' . $r['token'];
    ?>
    <tr>
      <td><?= e($r['name']) ?></td>
      <td><?= e($r['email']) ?></td>
      <td><?= e(date('d M H:i', strtotime($r['created_at']))) ?></td>
      <td><?= e(date('d M H:i', strtotime($r['expires_at']))) ?></td>
      <td>
        <?php if ($r['used']): ?><span class="rp-badge gray">Used/Revoked</span>
        <?php elseif ($expired): ?><span class="rp-badge red">Expired</span>
        <?php else: ?><span class="rp-badge green">Active</span><?php endif; ?>
      </td>
      <td>
        <?php if (!$r['used'] && !$expired): ?>
          <button class="rp-btn rp-btn-sm" onclick="navigator.clipboard.writeText('<?= e($link) ?>');this.textContent='Copied!'">📋 Copy Link</button>
          <a class="rp-btn rp-btn-sm rp-btn-gray" href="?revoke=<?= e($r['token']) ?>&csrf=<?= e($_SESSION['csrf'] ?? '') ?>" onclick="return confirm('Revoke this link?')">Revoke</a>
        <?php else: ?>—<?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<?php rp_app_close(); page_foot(); ?>
