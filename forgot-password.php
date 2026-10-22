<?php
// Forgot Password — user enters email, gets reset link
require_once __DIR__ . '/lib.php';

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim(strtolower((string)($_POST['email'] ?? '')));
    $pdo = db();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Please enter a valid email address.';
        $msgType = 'err';
    } else {
        $u = $pdo->prepare("SELECT id, name FROM users WHERE email=?");
        $u->execute([$email]);
        $user = $u->fetch(PDO::FETCH_ASSOC);

        // Always show success (don't reveal if email exists)
        $msg = 'If an account exists with this email, a reset link has been sent to it. Check your inbox (and spam folder). If you don\'t receive it, contact support on WhatsApp.';
        $msgType = 'ok';

        if ($user) {
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            // Store token (create table if needed)
            $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token TEXT NOT NULL UNIQUE,
                expires_at TEXT NOT NULL,
                used INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )");
            // Clear old tokens for this user
            $pdo->prepare("DELETE FROM password_resets WHERE user_id=?")->execute([$user['id']]);
            // Insert new
            $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?,?,?)")
                ->execute([$user['id'], $token, $expires]);

            // Build reset link
            $resetLink = 'https://' . PLATFORM_DOMAIN . '/reset-password.php?token=' . $token;

            // Try to send email
            $subject = 'Reset your Assanio password';
            $body = "Hi {$user['name']},\n\n"
                  . "Click the link below to reset your password (valid for 1 hour):\n\n"
                  . $resetLink . "\n\n"
                  . "If you didn't request this, just ignore this email.\n\n"
                  . "— Team Assanio";
            $headers = "From: noreply@" . PLATFORM_DOMAIN . "\r\n"
                     . "Reply-To: noreply@" . PLATFORM_DOMAIN . "\r\n"
                     . "Content-Type: text/plain; charset=UTF-8\r\n";

            @mail($email, $subject, $body, $headers);

            // Log for admin visibility (in case email doesn't deliver)
            error_log("ASSANIO password reset for $email: $resetLink");
        }
    }
}

page_head('Forgot Password');
?>
<div class="rp-auth-wrap">
  <div class="rp-card rp-auth-card">
    <h2>Forgot your password?</h2>
    <p class="card-sub">Enter your email and we'll send you a reset link.</p>
    <?php if ($msg): ?><div class="rp-msg <?= $msgType ?>"><?= e($msg) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <label>Email address
        <input type="email" name="email" required placeholder="you@example.com" autocomplete="email">
      </label>
      <button class="rp-btn rp-btn-red" type="submit" style="width:100%">Send Reset Link →</button>
    </form>
    <p style="text-align:center;margin-top:1rem"><a href="login.php">← Back to login</a></p>
  </div>
</div>
<?php page_foot(); ?>
