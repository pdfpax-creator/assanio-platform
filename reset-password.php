<?php
// Reset Password — user sets new password via token link
require_once __DIR__ . '/lib.php';

$token = (string)($_GET['token'] ?? ($_POST['token'] ?? ''));
$msg = '';
$msgType = '';
$valid = false;
$userId = null;

$pdo = db();
$pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,
    used INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");

if ($token !== '') {
    $r = $pdo->prepare("SELECT * FROM password_resets WHERE token=? AND used=0");
    $r->execute([$token]);
    $reset = $r->fetch(PDO::FETCH_ASSOC);

    if ($reset && strtotime($reset['expires_at']) > time()) {
        $valid = true;
        $userId = (int)$reset['user_id'];
    } else {
        $msg = 'This reset link is invalid or has expired. Please request a new one.';
        $msgType = 'err';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    csrf_check();
    $pass = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password2'] ?? '');

    if (strlen($pass) < 8) {
        $msg = 'Password must be at least 8 characters.';
        $msgType = 'err';
    } elseif ($pass !== $pass2) {
        $msg = 'Passwords do not match.';
        $msgType = 'err';
    } else {
        // Update password
        $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")
            ->execute([password_hash($pass, PASSWORD_DEFAULT), $userId]);
        // Mark token as used
        $pdo->prepare("UPDATE password_resets SET used=1 WHERE token=?")->execute([$token]);
        $msg = 'Your password has been reset! You can now log in.';
        $msgType = 'ok';
        $valid = false; // Don't show form again
    }
}

page_head('Reset Password');
?>
<div class="rp-auth-wrap">
  <div class="rp-card rp-auth-card">
    <h2>Set a new password</h2>
    <?php if ($msg): ?><div class="rp-msg <?= $msgType ?>"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msgType === 'ok' && !$valid): ?>
      <p style="text-align:center"><a href="login.php" class="rp-btn rp-btn-red">Log in →</a></p>
    <?php elseif ($valid): ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <label>New password (min 8 characters)
          <input type="password" name="password" required minlength="8" autocomplete="new-password">
        </label>
        <label>Confirm new password
          <input type="password" name="password2" required minlength="8" autocomplete="new-password">
        </label>
        <button class="rp-btn rp-btn-red" type="submit" style="width:100%">Reset Password →</button>
      </form>
    <?php elseif (!$token): ?>
      <p>No reset token provided. <a href="forgot-password.php">Request a new link</a></p>
    <?php else: ?>
      <p style="text-align:center"><a href="forgot-password.php">Request a new reset link →</a></p>
    <?php endif; ?>
  </div>
</div>
<?php page_foot(); ?>
