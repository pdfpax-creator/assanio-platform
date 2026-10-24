<?php
// Assanio Discover — Foodpanda-style restaurant directory.
// Auto-lists all active stores. New restaurants appear automatically.
require_once __DIR__ . '/lib.php';
$pdo = db();

// Get all active stores with order counts (only those marked visible in Discover)
$stores = $pdo->query("SELECT s.name, s.slug, s.template, s.city, s.whatsapp, s.featured,
                       (SELECT COUNT(*) FROM orders o WHERE o.store_id = s.id) AS order_count
                       FROM stores s WHERE (s.status = 'active' OR s.status IS NULL)
                       AND (s.show_in_discover = 1 OR s.show_in_discover IS NULL)
                       ORDER BY s.featured DESC, order_count DESC, s.created_at DESC")->fetchAll();

// Split featured
$featured = array_filter($stores, fn($s) => !empty($s['featured']));
$regular = array_filter($stores, fn($s) => empty($s['featured']));

// Template info: display name + image
$tplInfo = [
    'pizza-fastfood' => ['name' => 'Pizza & Fast Food', 'img' => 'templates/pizza-fastfood/images/hero-pizza.jpg', 'emoji' => '🍕'],
    'desi-bbq'       => ['name' => 'Desi & BBQ',          'img' => 'templates/desi-bbq/images/bbq-platter.jpg',  'emoji' => '🍖'],
    'cafe'           => ['name' => 'Cafe',                'img' => 'templates/cafe/images/latte-art.jpg',   'emoji' => '☕'],
    'fine-dining'    => ['name' => 'Fine Dining',         'img' => 'templates/fine-dining/images/hero-dish.jpg',  'emoji' => '🍽️'],
];

page_head('Discover Restaurants — Assanio');

$discTitle = setting_get('discover_title', 'Discover Restaurants');
$discSubtitle = setting_get('discover_subtitle', 'Order directly from the restaurant — no middleman, no extra charges.');
?>
<style>
.disc-wrap { max-width:1200px; margin:0 auto; padding:0 16px; }
.disc-hero { background:linear-gradient(135deg,#C8102E,#8B0A1F); color:#fff; text-align:center; padding:3.5rem 1rem 3rem; margin:0 -16px 2rem; border-radius:0 0 24px 24px; }
.disc-hero h1 { font-size:2.6rem; margin:0 0 .6rem; font-weight:800; letter-spacing:-.5px; }
.disc-hero p { opacity:.92; font-size:1.15rem; margin:0 0 1.8rem; max-width:600px; margin-left:auto; margin-right:auto; }
.disc-search { max-width:520px; margin:0 auto; position:relative; }
.disc-search input { width:100%; padding:1rem 1.2rem 1rem 3.2rem; border:none; border-radius:50px; font-size:1rem; box-shadow:0 4px 20px rgba(0,0,0,.2); }
.disc-search input:focus { outline:2px solid #fff; outline-offset:2px; }
.disc-search .s-icon { position:absolute; left:1.2rem; top:50%; transform:translateY(-50%); font-size:1.2rem; opacity:.6; }
.disc-filters { display:flex; gap:.6rem; flex-wrap:wrap; justify-content:center; margin:1.8rem 0; }
.disc-filter { padding:.55rem 1.2rem; border:1.5px solid #e0e0e0; border-radius:50px; background:#fff; cursor:pointer; font-size:.92rem; font-weight:600; transition:all .2s; color:#555; }
.disc-filter:hover { border-color:#C8102E; color:#C8102E; transform:translateY(-1px); }
.disc-filter.active { background:#C8102E; color:#fff; border-color:#C8102E; box-shadow:0 3px 12px rgba(200,16,46,.3); }
.disc-section { margin:2.2rem 0; }
.disc-section h2 { font-size:1.4rem; margin-bottom:1.2rem; font-weight:700; color:#222; }
.disc-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1.4rem; }
.dcard { background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.08); text-decoration:none; color:#222; display:block; transition:all .25s ease; border:1px solid #f0f0f0; }
.dcard:hover { box-shadow:0 8px 28px rgba(0,0,0,.15); transform:translateY(-4px); border-color:#e0e0e0; }
.dcard-img { width:100%; height:180px; background-size:cover; background-position:center; position:relative; display:block; }
.dcard-img::after { content:''; position:absolute; bottom:0; left:0; right:0; height:60px; background:linear-gradient(transparent, rgba(0,0,0,.4)); }
.dcard-badge { position:absolute; top:10px; left:10px; background:#1a9e4b; color:#fff; font-size:11px; font-weight:700; padding:5px 12px; border-radius:20px; z-index:2; box-shadow:0 2px 8px rgba(0,0,0,.2); }
.dcard-status { position:absolute; top:10px; right:10px; font-size:11px; font-weight:700; padding:5px 12px; border-radius:20px; z-index:2; }
.dcard-status.open { background:#fff; color:#1a9e4b; }
.dcard-status.closed { background:rgba(0,0,0,.7); color:#fff; }
.dcard-body { padding:14px 16px 16px; display:block; }
.dcard-body h3 { margin:0 0 5px; font-size:18px; font-weight:700; color:#1a1a1a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; letter-spacing:-.3px; }
.dcard-meta { font-size:13.5px; color:#777; margin-bottom:10px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.dcard-tags { display:flex; gap:12px; font-size:12.5px; color:#666; margin-bottom:12px; align-items:center; }
.dcard-tags .rating { color:#e6a817; font-weight:700; }
.dcard-btn { display:block; width:100%; background:#C8102E; color:#fff; text-align:center; padding:10px; border-radius:10px; font-weight:700; font-size:14.5px; text-decoration:none; border:none; transition:background .2s; }
.dcard-btn:hover { background:#a00d26; color:#fff; }
.disc-empty { text-align:center; padding:4rem 1rem; }
.disc-empty .big { font-size:4rem; margin-bottom:1rem; }
.disc-empty h3 { font-size:1.5rem; margin-bottom:.5rem; color:#333; }
.disc-empty p { color:#777; font-size:1.05rem; }
.disc-cta { text-align:center; padding:3rem 1rem 3.5rem; background:#fafafa; border-radius:20px; margin:2rem 0; }
.disc-cta h3 { font-size:1.6rem; margin-bottom:.5rem; color:#222; }
.disc-cta p { color:#777; margin-bottom:1.5rem; font-size:1.05rem; }
@media (max-width:600px) {
  .disc-hero h1 { font-size:2rem; }
  .disc-hero { padding:2.5rem 1rem 2rem; }
  .disc-grid { grid-template-columns:1fr; }
}
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
    <?php if ($featured): ?>
    <div class="disc-section" style="margin-bottom:2.5rem;">
      <h2>⭐ Featured Restaurants</h2>
      <div class="disc-grid" id="discFeatured">
        <?php foreach ($featured as $s):
          $tpl = $tplInfo[$s['template']] ?? $tplInfo['pizza-fastfood'];
        ?>
        <a class="dcard" href="/<?= e($s['slug']) ?>/" data-tpl="<?= e($s['template']) ?>" data-name="<?= e(strtolower($s['name'] . ' ' . $tpl['name'] . ' ' . ($s['city'] ?? ''))) ?>" style="border:2px solid #c9a227;">
          <span class="dcard-img" style="background-image:url('<?= e($tpl['img']) ?>')">
            <span class="dcard-badge" style="background:#c9a227;">⭐ FEATURED</span>
          </span>
          <span class="dcard-body">
            <h3><?= e($s['name']) ?></h3>
            <div class="dcard-meta"><?= e($tpl['emoji'] . ' ' . $tpl['name']) ?> · <?= e($s['city']) ?></div>
            <div class="dcard-tags">
              <?php if ((int)$s['order_count'] > 0): ?>
                <span>📦 <?= (int)$s['order_count'] ?> orders</span>
              <?php else: ?>
                <span style="color:#1a9e4b;font-weight:600">✨ New on Assanio</span>
              <?php endif; ?>
              <span style="margin-left:auto;color:#999">No commission</span>
            </div>
            <span class="dcard-btn">Order Direct →</span>
          </span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
    <div class="disc-section">
      <h2>All Restaurants <span id="allCount" style="color:#999;font-weight:400;font-size:1rem">(<?= count($regular) ?>)</span></h2>
      <div class="disc-grid" id="discGrid">
        <?php foreach ($regular as $s):
          $tpl = $tplInfo[$s['template']] ?? ['name' => $s['template'], 'img' => 'assets/landing/t1-pizza.jpg', 'emoji' => '🍴'];
          $waNum = preg_replace('/\D/', '', $s['whatsapp'] ?? '');
          if ($waNum && $waNum[0] === '0') $waNum = '92' . substr($waNum, 1);
        ?>
        <a class="dcard" href="/<?= e($s['slug']) ?>/" data-tpl="<?= e($s['template']) ?>" data-name="<?= e(strtolower($s['name'] . ' ' . $tpl['name'] . ' ' . ($s['city'] ?? ''))) ?>">
          <span class="dcard-img" style="background-image:url('<?= e($tpl['img']) ?>')">
            <span class="dcard-badge">Order Direct</span>
          </span>
          <span class="dcard-body">
            <h3><?= e($s['name']) ?></h3>
            <div class="dcard-meta"><?= e($tpl['name']) ?> · <?= e($s['city'] ?: 'Pakistan') ?></div>
            <div class="dcard-tags">
              <?php if ((int)$s['order_count'] > 0): ?>
                <span>📦 <?= (int)$s['order_count'] ?> orders</span>
              <?php else: ?>
                <span style="color:#1a9e4b;font-weight:600">✨ New on Assanio</span>
              <?php endif; ?>
              <span style="margin-left:auto;color:#999">No commission</span>
            </div>
            <span class="dcard-btn">View Menu →</span>
          </span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="disc-cta">
    <h3>Own a restaurant?</h3>
    <p>Get your own website + WhatsApp ordering. Setup FREE with yearly billing.</p>
    <a class="rp-btn rp-btn-red" href="register.php" style="font-size:1.05rem;padding:.85rem 2.2rem;border-radius:50px">Claim My Free Setup →</a>
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
  let visible = 0;
  document.querySelectorAll('#discGrid .dcard').forEach(c => {
    const show = (tpl === 'all' || c.dataset.tpl === tpl);
    c.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  document.querySelectorAll('#discFeatured .dcard').forEach(c => {
    c.style.display = (tpl === 'all' || c.dataset.tpl === tpl) ? '' : 'none';
  });
  const featSec = document.querySelector('#discFeatured');
  if (featSec) {
    const anyFeatVisible = [...document.querySelectorAll('#discFeatured .dcard')].some(c => c.style.display !== 'none');
    featSec.closest('.disc-section').style.display = anyFeatVisible ? '' : 'none';
  }
  const countEl = document.getElementById('allCount');
  if (countEl) countEl.textContent = '(' + visible + ')';
}
</script>

<?php page_foot(); ?>
