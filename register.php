<?php
require_once __DIR__ . '/lib.php';
if (current_user()) { header('Location: dashboard.php'); exit; }
// Remember which template the visitor picked on the landing/templates page.
$preTemplate = (string)($_GET['template'] ?? '');
if ($preTemplate !== '' && isset(RP_TEMPLATES[$preTemplate])) {
    $_SESSION['pre_template'] = $preTemplate;
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name  = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $pass  = (string)($_POST['password'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Please enter your name and a valid email address.';
    } elseif (strlen($pass) < 8) {
        $err = 'Password must be at least 8 characters.';
    } else {
        $pdo = db();
        $st = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) {
            $err = 'An account with this email already exists. Try logging in.';
        } else {
            $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
            $ins->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            // Do NOT auto-login — account needs admin approval after payment
            flash('Account created! Please send payment screenshot on WhatsApp with your email. We will approve your account shortly.');
            header('Location: login.php?pending=1');
            exit;
        }
    }
}
page_head('Create your account');
?>
<div class="rp-auth">
  <div class="rp-auth-form">
    <div><?= rp_logo(true) ?></div>
    <h1>Create your account</h1>
    <p class="lede">Your restaurant's own website and WhatsApp ordering — ready in minutes.</p>
    <div class="box" style="margin-top:1.1rem">
      <?php if ($err): ?><div class="rp-msg err"><?= e($err) ?></div><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <label class="rp-label" for="rName">Your name</label>
        <input class="rp-input" id="rName" type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>" autocomplete="name" placeholder="e.g. Ahmed Raza">
        <label class="rp-label" for="rEmail">Email address</label>
        <input class="rp-input" id="rEmail" type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email" placeholder="you@example.com">
        <label class="rp-label" for="rPass">Password</label>
        <input class="rp-input" id="rPass" type="password" name="password" required autocomplete="new-password" placeholder="Min. 8 characters">
        <p class="rp-hint">Must be at least 8 characters long.</p>
        <div style="margin-top:1.1rem"><button class="rp-btn rp-btn-red rp-btn-block" type="submit">Create my account →</button></div>
      </form>
      <p class="rp-hint" style="margin-top:1rem;text-align:center">Already have an account? <a href="login.php" style="font-weight:700">Log in</a></p>
    </div>
    <p class="rp-hint" style="text-align:center;margin-top:.9rem">✓ 3-minute setup · No credit card · No coding</p>
    <?php rp_auth_mini_foot(); ?>
  </div>
  <div class="rp-auth-side">
    <span class="kick">● Your restaurant, online</span>
    <h2>Take orders on your own website</h2>
    <p class="lede">Your menu, your number, your customer data. Website and WhatsApp orders with zero commission.</p>
    <div class="rp-feat"><span class="fi"><?= rp_icon('globe') ?></span><div><b>Your own restaurant website</b><p>Keep your customer data and 100% of your sales — no marketplace commission.</p></div></div>
    <div class="rp-feat"><span class="fi"><?= rp_icon('pencil') ?></span><div><b>Change your menu from your phone</b><p>Prices, deals, daily specials — updated in seconds. No coding.</p></div></div>
    <div class="rp-feat"><span class="fi"><?= rp_icon('chat') ?></span><div><b>Instant WhatsApp order alerts</b><p>Every order arrives on your WhatsApp as a ready-to-read message.</p></div></div>
    <?= rp_pilot_panel() ?>
  </div>
</div>
<?php page_foot(); ?>
