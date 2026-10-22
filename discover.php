<?php
// Assanio Discover — Foodpanda-style restaurant directory.
// Auto-lists all active stores. New restaurants appear automatically.
require_once __DIR__ . '/lib.php';
$pdo = db();

// Get all active stores with order counts (only those marked visible in Discover)
$stores = $pdo->query("SELECT s.name, s.slug, s.template, s.city, s.whatsapp,
                       (SELECT COUNT(*) FROM orders o WHERE o.store_id = s.id) AS order_count
                       FROM stores s WHERE (s.status = 'active' OR s.status IS NULL)
                       AND (s.show_in_discover = 1 OR s.show_in_discover IS NULL)
                       ORDER BY order_count DESC, s.created_at DESC")->fetchAll();

// Template info: display name + image
$tplInfo = [
    'pizza-fastfood' => ['name' => 'Pizza & Fast Food', 'img' => 'assets/landing/t1-pizza.jpg', 'emoji' => '🍕'],
    'desi-bbq'       => ['name' => 'Desi & BBQ',          'img' => 'assets/landing/t2-dera.jpg',  'emoji' => '🍖'],
    'cafe'           => ['name' => 'Cafe',                'img' => 'assets/landing/t3-cafe.jpg',   'emoji' => '☕'],
    'fine-dining'    => ['name' => 'Fine Dining',         'img' => 'assets/landing/t4-noore.jpg',  'emoji' => '🍽️'],
];

page_head('Discover Restaurants — Assanio');

$discTitle = setting_get('discover_title', '🍽️ Discover Restaurants');
$discSubtitle = setting_get('discover_subtitle', 'Order directly from the restaurant — no middleman, no extra charges.');
?>
<style>
.disc-wrap { max-width:1200px; margin:0 auto; padding:0 16px; }
.disc-hero { background:linear-gradient(135deg,#C8102E,#8B0A1F); color:#fff; text-align:center; padding:3rem 1rem 2.5rem; margin:0 -16px 2rem; border-radius:0 0 24px 24px; }
.disc-hero h1 { font-size:2.4rem; margin:0 0 .5rem; }
.disc-hero p { opacity:.9; font-size:1.1rem; margin:0 0 1.5rem; }
.disc-search { max-width:500px; margin:0 auto; position:relative; }
.disc-search input { width:100%; padding:.9rem 1.2rem .9rem 3rem; border:none; border-radius:50px; font-size:1rem; box-shadow:0 4px 15px rgba(0,0,0,.15); }
.disc-search .s-icon { position:absolute; left:1.1rem; top:50%; transform:translateY(-50%); font-size:1.2rem; }
.disc-filters { display:flex; gap:.6rem; flex-wrap:wrap; justify-content:center; margin:1.5rem 0; }
.disc-filter { padding:.5rem 1.1rem; border:1px solid #ddd; border-radius:50px; background:#fff; cursor:pointer; font-size:.9rem; transition:all .15s; }
.disc-filter:hover, .disc-filter.active { background:#C8102E; color:#fff; border-color:#C8102E; }
.disc-section { margin:2rem 0; }
.disc-section h2 { font-size:1.3rem; margin-bottom:1rem; display:flex; align-items:center; gap:.5rem; }
.disc-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:1.2rem; }
.dcard { background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,.08); text-decoration:none; color:#222; display:block; }
.dcard:hover { box-shadow:0 6px 20px rgba(0,0,0,.13); }
.dcard-img { width:100%; height:160px; background-size:cover; background-position:center; position:relative; display:block; }
.dcard-badge { position:absolute; top:8px; left:8px; background:#1a9e4b; color:#fff; font-size:11px; font-weight:700; padding:4px 10px; border-radius:20px; }
.dcard-body { padding:12px 14px 14px; display:block; }
.dcard-body h3 { margin:0 0 4px; font-size:17px; font-weight:700; color:#222; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.dcard-meta { font-size:13px; color:#777; margin-bottom:8px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.dcard-tags { display:flex; gap:12px; font-size:12px; color:#666; margin-bottom:10px; }
.dcard-btn { display:block; width:100%; background:#C8102E; color:#fff; text-align:center; padding:9px; border-radius:8px; font-weight:700; font-size:14px; text-decoration:none; border:none; }
.dcard-btn:hover { background:#a00d26; color:#fff; }
</style>

<div class="disc-hero">
  <div class="disc-wrap">
    <h1><?= e($discTitle) ?></h1>
    <p><?= e($discSubtitle) ?></p>
    <div class="disc-search">
      <span class="s-icon">🔍</span>
      <input type="text" id="discSearch" placeholder="Search restaurants or cuisines..." oninput="filterCards()">
    </div>
  </div>
</div>

<div class="disc-wrap">
  <div class="disc-filters" id="discFilters">
    <button class="disc-filter active" onclick="filterBy('all',this)">All</button>
    <?php foreach ($tplInfo as $key => $t): ?>
      <button class="disc-filter" onclick="filterBy('<?= e($key) ?>',this)"><?= e($t['emoji'] . ' ' . $t['name']) ?></button>
    <?php endforeach; ?>
  </div>

  <?php if (!$stores): ?>
    <div class="disc-empty">
      <div class="big">🍽️</div>
      <h3>Restaurants are joining soon!</h3>
      <p>Own a restaurant? <a href="register.php" style="color:#C8102E;font-weight:700">Claim your free setup →</a></p>
    </div>
  <?php else: ?>
    <div class="disc-section">
      <h2>🔥 All Restaurants (<?= count($stores) ?>)</h2>
      <div class="disc-grid" id="discGrid">
        <?php foreach ($stores as $s):
          $tpl = $tplInfo[$s['template']] ?? ['name' => $s['template'], 'img' => 'assets/landing/t1-pizza.jpg', 'emoji' => '🍴'];
          $waNum = preg_replace('/\D/', '', $s['whatsapp'] ?? '');
          if ($waNum && $waNum[0] === '0') $waNum = '92' . substr($waNum, 1);
        ?>
        <a class="dcard" href="/<?= e($s['slug']) ?>/" data-tpl="<?= e($s['template']) ?>" data-name="<?= e(strtolower($s['name'] . ' ' . $tpl['name'] . ' ' . ($s['city'] ?? ''))) ?>">
          <span class="dcard-img" style="background-image:url('<?= e($tpl['img']) ?>')">
            <span class="dcard-badge">🛵 Order Direct</span>
          </span>
          <span class="dcard-body">
            <h3><?= e($s['name']) ?></h3>
            <div class="dcard-meta"><?= e($tpl['emoji']) ?> <?= e($tpl['name']) ?> · 📍 <?= e($s['city'] ?: 'Pakistan') ?></div>
            <div class="dcard-tags">
              <?php if ((int)$s['order_count'] > 0): ?>
                <span>📦 <?= (int)$s['order_count'] ?> orders</span>
              <?php else: ?>
                <span>✨ New</span>
              <?php endif; ?>
            </div>
            <span class="dcard-btn">View Menu →</span>
          </span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div style="text-align:center; padding:2.5rem 1rem 3rem;">
    <h3>Own a restaurant?</h3>
    <p style="color:#777">Get your own website + WhatsApp ordering. Setup FREE with yearly billing.</p>
    <a class="rp-btn rp-btn-red" href="register.php" style="font-size:1.05rem;padding:.8rem 2rem">Claim My Free Setup →</a>
  </div>
</div>

<script>
function filterCards() {
  const q = document.getElementById('discSearch').value.toLowerCase();
  document.querySelectorAll('.dcard').forEach(c => {
    c.style.display = c.dataset.name.includes(q) ? '' : 'none';
  });
}
function filterBy(tpl, btn) {
  document.querySelectorAll('.disc-filter').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('discSearch').value = '';
  document.querySelectorAll('.dcard').forEach(c => {
    c.style.display = (tpl === 'all' || c.dataset.tpl === tpl) ? '' : 'none';
  });
}
</script>

<?php page_foot(); ?>
