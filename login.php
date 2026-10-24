<?php
require_once __DIR__ . '/lib.php';
if (current_user()) {
    $cu = current_user();
    $r = $cu['role'] ?? 'owner';
    if ($r === 'caller') { header('Location: caller-crm.php'); }
    elseif ($r === 'super') { header('Location: admin.php'); }
    else { header('Location: dashboard.php'); }
    exit;
}
$err = '';
$next = safe_next((string)($_GET['next'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $next = safe_next((string)($_POST['next'] ?? $next));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $pass  = (string)($_POST['password'] ?? '');
    $st = db()->prepare('SELECT id, password_hash, approved, role FROM users WHERE email = ?');
    $st->execute([$email]);
    $u = $st->fetch();
    if ($u && password_verify($pass, $u['password_hash'])) {
        // Super admins bypass approval
        if (($u['role'] ?? 'owner') !== 'super' && !(int)($u['approved'] ?? 0)) {
            $err = 'Your account is pending approval. Please complete payment and send screenshot on WhatsApp. We will activate your account shortly.';
        } else {
            login_user((int)$u['id']);
            // Role-based redirect: caller ALWAYS -> My Leads (never dashboard)
            $role = $u['role'] ?? 'owner';
            if ($role === 'caller') {
                $next = 'caller-crm.php';
            } elseif ($role === 'super' && empty($_POST['next'] ?? '') && empty($_GET['next'] ?? '')) {
                $next = 'admin.php';
            } elseif (empty($_POST['next'] ?? '') && empty($_GET['next'] ?? '')) {
                $next = 'dashboard.php';
            }
            header('Location: ' . $next);
            exit;
        }
    } else {
        $err = 'Email or password is incorrect.';
    }
}
page_head('Log in');
?>
<div class="rp-auth">
  <div class="rp-auth-form">
    <div><?= rp_logo(true) ?></div>
    <h1>Welcome back</h1>
    <p class="lede">Your restaurants and orders are waiting.</p>
    <div class="box" style="margin-top:1.1rem">
      <?php if ($err): ?><div class="rp-msg err"><?= e($err) ?></div><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <label class="rp-label" for="lEmail">Email address</label>
        <input class="rp-input" id="lEmail" type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email" placeholder="you@example.com">
        <label class="rp-label" for="lPass">Password</label>
        <input class="rp-input" id="lPass" type="password" name="password" required autocomplete="current-password" placeholder="Your password">
        <div style="margin-top:1.1rem"><button class="rp-btn rp-btn-red rp-btn-block" type="submit">Log in →</button></div>
      </form>
      <p class="rp-hint" style="margin-top:.5rem;text-align:center"><a href="forgot-password.php">Forgot your password?</a></p>
      <p class="rp-hint" style="margin-top:.5rem;text-align:center">New here? <a href="register.php" style="font-weight:700">Create an account</a></p>
    </div>
    <?php rp_auth_mini_foot(); ?>
  </div>
  <div class="rp-auth-side">
    <span class="kick">● Your restaurant, online</span>
    <h2>All your restaurants, one dashboard</h2>
    <p class="lede">Websites, menus and orders — built for restaurants in Pakistan.</p>
    <div class="rp-feat"><span class="fi"><?= rp_icon('chart') ?></span><div><b>One dashboard for every order</b><p>Website and WhatsApp orders land in a single queue — accept, prepare, deliver.</p></div></div>
    <div class="rp-feat"><span class="fi"><?= rp_icon('pencil') ?></span><div><b>Edit your site in one click</b><p>Jump straight into your website editor from the dashboard — no separate password.</p></div></div>
    <?= rp_pilot_panel() ?>
  </div>
</div>
<?php page_foot(); ?>
