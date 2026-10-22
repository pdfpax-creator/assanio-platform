<?php
require_once __DIR__ . '/lib.php';
$user = require_login();
if (($user['role'] ?? 'owner') !== 'super') {
    http_response_code(403);
    exit('Forbidden');
}
$pdo = db();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'assign') {
        $callerId = (int)($_POST['caller_id'] ?? 0);
        $clientId = (int)($_POST['client_id'] ?? 0);
        $plan = $_POST['plan'] ?? 'starter';
        try {
            mlm_create_referral($pdo, $callerId, $clientId, $plan);
            flash('Ho gaya! Client caller se link ho gaya.');
        } catch (Throwable $e) {
            flash('Error: ' . $e->getMessage(), false);
        }
    } elseif ($action === 'calculate') {
        $month = $_POST['month'] ?? date('Y-m');
        $count = mlm_calculate_monthly($pdo, $month);
        flash("$count commissions ban gaye $month ke liye.");
    } elseif ($action === 'pay') {
        $ids = array_map('intval', $_POST['commission_ids'] ?? []);
        $count = mlm_mark_paid($pdo, $ids);
        flash("$count commissions paid mark ho gaye.");
    } elseif ($action === 'deactivate') {
        $rid = (int)($_POST['referral_id'] ?? 0);
        $pdo->prepare("UPDATE referrals SET status = 'inactive' WHERE id = ?")->execute([$rid]);
        flash('Band kar diya. Ab iska monthly commission nahi ayega.');
    } elseif ($action === 'add_caller') {
        $name = trim($_POST['caller_name'] ?? '');
        $email = strtolower(trim($_POST['caller_email'] ?? ''));
        $pass = (string)($_POST['caller_pass'] ?? '');
        if (!$name || !$email || !$pass) {
            flash('Naam, email aur password teeno zaruri hain.', false);
        } else {
            $st = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $st->execute([$email]);
            if ($st->fetchColumn()) {
                flash('Ye email pehle se hai!', false);
            } else {
                $code = strtoupper(substr(preg_replace('/[^a-z]/i', '', $name), 0, 4)) . '-' . rand(1000, 9999);
                $pdo->prepare("INSERT INTO users (name, email, password_hash, role, referral_code, approved) VALUES (?,?,?,?,?,1)")
                    ->execute([$name, $email, password_hash($pass, PASSWORD_BCRYPT), 'caller', $code]);
                flash("Caller ban gaya! Email: $email | Code: $code");
            }
        }
    } elseif ($action === 'make_caller') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $code = strtoupper(substr(preg_replace('/[^a-z]/i', '', $_POST['caller_name'] ?? 'CALLER'), 0, 4)) . '-' . rand(1000, 9999);
        $pdo->prepare("UPDATE users SET role = 'caller', referral_code = ? WHERE id = ?")->execute([$code, $userId]);
        flash("Caller bana diya! Uska code: $code");
    }
    header('Location: admin-referrals.php');
    exit;
}

// Get all callers
$callers = $pdo->query("SELECT id, name, email, referral_code FROM users WHERE role = 'caller' ORDER BY name")->fetchAll();

// Get owners who can become callers
$owners = $pdo->query("SELECT id, name, email FROM users WHERE role = 'owner' ORDER BY name LIMIT 50")->fetchAll();

// Get unreferred clients (show restaurant/store name, not just email)
$clients = $pdo->query("
    SELECT u.id, u.name, u.email,
           (SELECT GROUP_CONCAT(s.name, ', ') FROM stores s WHERE s.owner_id = u.id) as stores
    FROM users u
    WHERE u.role = 'owner' AND u.id NOT IN (SELECT client_id FROM referrals WHERE status = 'active')
    ORDER BY u.name
")->fetchAll();

// All referrals
$referrals = $pdo->query("
    SELECT r.id, r.plan, r.status, r.created_at,
           c1.name as caller_name, u.name as client_name, u.email as client_email,
           (SELECT GROUP_CONCAT(s.name, ', ') FROM stores s WHERE s.owner_id = u.id) as client_stores,
           (SELECT SUM(amount) FROM commissions WHERE referral_id = r.id AND paid = 0) as pending,
           (SELECT SUM(amount) FROM commissions WHERE referral_id = r.id AND paid = 1) as paid
    FROM referrals r
    JOIN users c1 ON c1.id = r.caller_id
    JOIN users u ON u.id = r.client_id
    ORDER BY r.created_at DESC
")->fetchAll();

// Unpaid
$unpaid = $pdo->query("
    SELECT c.id, c.month, c.amount, c.type, c1.name as caller_name, u.name as client_name
    FROM commissions c
    JOIN referrals r ON r.id = c.referral_id
    JOIN users c1 ON c1.id = r.caller_id
    JOIN users u ON u.id = r.client_id
    WHERE c.paid = 0
    ORDER BY c.month DESC, c1.name
")->fetchAll();

// Stats
$totalPending = array_sum(array_map(fn($u) => (int)$u['amount'], $unpaid));

page_head('Referrals');
rp_app_open($user, 'referrals', [
    'title' => 'Callers',
    'sub'   => 'Cold callers banao aur leads assign karo.',
]);
?>

<!-- STEP 1 -->
<div class="rp-card">
  <h2>Step 1: Naya Caller Add Karo</h2>
  <p class="rp-hint">Naam, email aur password dalo — caller ka login ban jayega.</p>
    <form method="post" class="rp-form-grid" style="max-width:500px">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_caller">
      <label>Naam
        <input type="text" name="caller_name" required class="rp-input" style="width:100%;margin-top:4px" placeholder="e.g. Ahmed Raza">
      </label>
      <label>Email (yehi login hoga)
        <input type="email" name="caller_email" required class="rp-input" style="width:100%;margin-top:4px" placeholder="caller@example.com">
      </label>
      <label>Password
        <input type="text" name="caller_pass" required class="rp-input" style="width:100%;margin-top:4px" placeholder="e.g. Caller123">
      </label>
      <div><button class="rp-btn rp-btn-red" type="submit">Caller Banao</button></div>
    </form>

  <?php if ($callers): ?>
    <h3 style="margin-top:1rem">Tumhare Callers (<?= count($callers) ?>)</h3>
    <div class="rp-table-wrap"><table class="rp-table">
      <thead><tr><th>Naam</th><th>Email</th><th>Uska Code</th></tr></thead>
      <tbody>
        <?php foreach ($callers as $c): ?>
          <tr><td><b><?= e($c['name']) ?></b></td><td><?= e($c['email']) ?></td><td><code><?= e($c['referral_code'] ?: '—') ?></code></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>

<!-- STEP 2 -->
<div class="rp-card">
  <h2>Step 2: Client ko Caller se Joro</h2>
  <p class="rp-hint">Jab caller koi client laye, yahan usko link karo. (Paise ka hisaab tum WhatsApp pe manually karoge.)</p>
  <?php if (!$callers || !$clients): ?>
    <p class="rp-muted">
      <?php if (!$callers): ?>Pehle Step 1 mein caller banao.<?php endif; ?>
      <?php if (!$clients): ?>Koi naya client nahi hai link karne ke liye.<?php endif; ?>
    </p>
  <?php else: ?>
    <form method="post" class="rp-form-grid" style="max-width:600px">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="assign">
      <label>Caller kaun hai?
        <select name="caller_id" required class="rp-select" style="width:100%;margin-top:4px">
          <option value="">— Caller select karo —</option>
          <?php foreach ($callers as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Client kaun hai? (jisko caller laya)
        <select name="client_id" required class="rp-select" style="width:100%;margin-top:4px">
          <option value="">— Client select karo —</option>
          <?php foreach ($clients as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= e($c['stores'] ?: $c['name']) ?> (<?= e($c['name']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Client ne kaunsa plan liya?
        <select name="plan" class="rp-select" style="width:100%;margin-top:4px">
          <option value="starter">Starter — Rs 1,999/month</option>
          <option value="business" selected>Business — Rs 3,499/month</option>
          <option value="pro">Funnel Pro — Rs 5,999/month</option>
        </select>
      </label>
      <div><button class="rp-btn rp-btn-red" type="submit">Link Kar Do</button></div>
    </form>
  <?php endif; ?>
</div>

<!-- STEP 3: Monthly commissions -->
<div class="rp-card">
  <h2>Monthly Commissions</h2>
  <p class="rp-hint">Generate recurring commissions for all active referrals. Run on the 1st of each month (or auto via cron).</p>
  <form method="post" class="rp-form-inline">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="calculate">
    <input type="month" name="month" value="<?= e(date('Y-m')) ?>" required class="rp-input">
    <button class="rp-btn rp-btn-dark" type="submit">Generate Monthly Commissions</button>
  </form>
</div>

<!-- STEP 4: Mark as paid -->
<div class="rp-card">
  <h2>Commission Payments</h2>
  <p class="rp-hint">After paying via bank transfer / Easypaisa, mark commissions as paid here.</p>

<!-- ALL REFERRALS -->
<?php if ($referrals): ?>
<div class="rp-card">
  <h2>Sab Links (<?= count($referrals) ?>)</h2>
  <div class="rp-table-wrap"><table class="rp-table">
    <thead><tr><th>Caller</th><th>Client</th><th>Plan</th><th>Linked</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($referrals as $r): ?>
        <tr>
          <td><?= e($r['caller_name']) ?></td>
          <td><?= e($r['client_stores'] ?: $r['client_name']) ?></td>
          <td><?= e(ucfirst($r['plan'])) ?></td>
          <td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td>
          <td>
            <?php if ($r['status'] === 'active'): ?>
              <form method="post" style="display:inline" onsubmit="return confirm('Band kar dun? Monthly ruk jayega.')">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="deactivate">
                <input type="hidden" name="referral_id" value="<?= (int)$r['id'] ?>">
                <button class="rp-btn rp-btn-sm" type="submit">Band Karo</button>
              </form>
            <?php else: ?>
              <span class="rp-pill">Band</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>

<?php rp_app_close(); page_foot(); ?>
