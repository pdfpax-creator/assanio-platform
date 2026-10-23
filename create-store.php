<?php
require_once __DIR__ . '/lib.php';
$user = require_login();
$err = '';
$preTemplate = (string)($_GET['template'] ?? ($_SESSION['pre_template'] ?? ''));
$vals = ['name' => '', 'slug' => '', 'template' => isset(RP_TEMPLATES[$preTemplate]) ? $preTemplate : 'desi-bbq', 'whatsapp' => '', 'city' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($vals as $k => $_) $vals[$k] = trim((string)($_POST[$k] ?? ''));
    $pdo = db();
    $slug = slugify($vals['slug'] !== '' ? $vals['slug'] : $vals['name']);
    if ($vals['name'] === '') {
        $err = 'Please enter your restaurant name.';
    } elseif (!isset(RP_TEMPLATES[$vals['template']])) {
        $err = 'Please pick a template.';
    } elseif (!in_array($vals['template'], ['desi-bbq', 'pizza-fastfood'])) {
        $err = 'This template is coming soon. Please choose Tandoor Nights or Smash Bros.';
    } elseif (!preg_match('/^92?3\d{9}$/', normalize_phone($vals['whatsapp'])['digits'] ?? '')) {
        $err = 'Please enter a valid WhatsApp mobile number (e.g. 03xx xxxxxxx).';
    } else {
        $slug = unique_slug($pdo, $slug);
        $store = [
            'name'     => $vals['name'],
            'slug'     => $slug,
            'template' => $vals['template'],
            'whatsapp' => $vals['whatsapp'],
            'city'     => $vals['city'],
            'api_key'  => new_api_key(),
        ];
        $ins = $pdo->prepare('INSERT INTO stores (owner_id, name, slug, template, whatsapp, city, api_key)
                              VALUES (?, ?, ?, ?, ?, ?, ?)');
        $ins->execute([(int)$user['id'], $store['name'], $store['slug'], $store['template'],
                       $store['whatsapp'], $store['city'], $store['api_key']]);
        $store['id'] = (int)$pdo->lastInsertId();
        unset($_SESSION['pre_template']);
        try {
            generateStoreFiles($store);
            flash('“' . $store['name'] . '” is live! Your website is ready.');
        } catch (Throwable $ex) {
            flash('Your restaurant was created, but the website could not be built: ' . $ex->getMessage() . ' — open Settings and use "Regenerate website files".', false);
        }
        header('Location: store-settings.php?id=' . $store['id']);
        exit;
    }
}
page_head('Create a restaurant');
rp_app_open($user, 'dashboard', [
    'title' => 'Create your restaurant',
    'sub'   => 'Your website is generated the moment you submit — menu and design can be edited afterwards.',
    'crumb' => 'New Restaurant',
]);
$tplClass = ['pizza-fastfood' => 'tpl-pizza', 'desi-bbq' => 'tpl-desi', 'cafe' => 'tpl-cafe', 'fine-dining' => 'tpl-fine'];
?>
  <?php if ($err): ?><div class="rp-msg err"><?= e($err) ?></div><?php endif; ?>
  <div class="rp-card" style="max-width:860px">
    <h2>Let's set up your restaurant</h2>
    <p class="card-sub">A few details, and your website builds itself.</p>
    <form method="post" id="storeForm">
      <?= csrf_field() ?>
      <div class="rp-row">
        <div>
          <label class="rp-label" for="fName">Restaurant name *</label>
          <input class="rp-input" type="text" name="name" id="fName" required value="<?= e($vals['name']) ?>" placeholder="e.g. Spice Route Karachi">
        </div>
        <div>
          <label class="rp-label" for="fSlug">Website slug</label>
          <input class="rp-input" type="text" name="slug" id="fSlug" value="<?= e($vals['slug']) ?>" placeholder="auto from name">
          <p class="rp-hint">Lowercase letters, numbers and dashes only — this becomes part of your website address (<span id="slugPreview">your-slug</span>).</p>
        </div>
      </div>
      <label class="rp-label">Choose a template</label>
      <div class="tpl-grid">
        <?php
        $enabled_templates = ['desi-bbq', 'pizza-fastfood'];
        foreach (RP_TEMPLATES as $key => $t):
          $is_enabled = in_array($key, $enabled_templates);
        ?>
          <label class="tpl <?= $tplClass[$key] ?? '' ?> <?= $vals['template'] === $key ? 'sel' : '' ?> <?= !$is_enabled ? 'tpl-coming' : '' ?>">
            <?php if ($is_enabled): ?>
              <input type="radio" name="template" value="<?= e($key) ?>" <?= $vals['template'] === $key ? 'checked' : '' ?>>
            <?php else: ?>
              <input type="radio" name="template" value="<?= e($key) ?>" disabled>
            <?php endif; ?>
            <span class="tpl-visual"><img src="<?= e($t['img'] ?? '') ?>" alt="<?= e($t['name']) ?> template preview" loading="lazy">
              <?php if (!$is_enabled): ?>
                <span class="coming-badge">Coming Soon</span>
              <?php endif; ?>
            </span>
            <b><?= e($t['label']) ?></b>
            <p><?= e($t['blurb']) ?></p>
          </label>
        <?php endforeach; ?>
      </div>
      <style>
        .tpl-coming { opacity: 0.6; cursor: not-allowed; }
        .tpl-coming .tpl-visual { position: relative; }
        .coming-badge {
          position: absolute; top: 50%; left: 50%;
          transform: translate(-50%, -50%);
          background: #1b2a4a; color: #fff;
          padding: 0.4rem 1rem; border-radius: 6px;
          font-size: 13px; font-weight: 700;
          white-space: nowrap;
        }
      </style>
      <div class="rp-row">
        <div>
          <label class="rp-label" for="fWa">WhatsApp number (orders go here) *</label>
          <input class="rp-input" type="tel" name="whatsapp" id="fWa" required value="<?= e($vals['whatsapp']) ?>" placeholder="03xx xxxxxxx">
          <p class="rp-hint">Customers send their orders directly to this WhatsApp number.</p>
        </div>
        <div>
          <label class="rp-label" for="fCity">City</label>
          <input class="rp-input" type="text" name="city" id="fCity" value="<?= e($vals['city']) ?>" placeholder="e.g. Lahore">
        </div>
      </div>
      <div style="margin-top:1.3rem;display:flex;gap:.7rem;align-items:center;flex-wrap:wrap">
        <button class="rp-btn rp-btn-red" type="submit">Create my restaurant →</button>
        <a class="rp-btn rp-btn-line" href="dashboard.php">Back</a>
      </div>
    </form>
  </div>
  <p class="rp-hint" style="text-align:center">✓ Zero commission &nbsp;·&nbsp; ✓ Instant WhatsApp sync &nbsp;·&nbsp; ✓ Free SSL</p>
<script>
(function(){
  var name=document.getElementById('fName'), slug=document.getElementById('fSlug'), prev=document.getElementById('slugPreview'), touched=false;
  slug.addEventListener('input',function(){touched=true;prev.textContent=slug.value||'your-slug';});
  name.addEventListener('input',function(){
    if(touched) return;
    var s=name.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
    slug.value=s; prev.textContent=s||'your-slug';
  });
  document.querySelectorAll('.tpl').forEach(function(card){
    card.addEventListener('click',function(){
      document.querySelectorAll('.tpl').forEach(function(c){c.classList.remove('sel')});
      card.classList.add('sel');
    });
  });
})();
</script>
<?php rp_app_close(); page_foot(); ?>
