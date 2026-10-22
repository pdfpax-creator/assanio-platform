<?php
require_once __DIR__ . '/lib.php';
$user = require_login();
$pdo = db();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM stores WHERE id = ? AND owner_id = ?');
$st->execute([$id, (int)$user['id']]);
$store = $st->fetch();
if (!$store) rp_error_page(404, 'Restaurant not found', 'This restaurant does not exist or belongs to a different account.');
$storeDir = RP_STORES_DIR . '/' . $store['slug'];
$cjFile = $storeDir . '/content.json';
$CHECKOUT_MODES = ['whatsapp', 'web', 'both'];

/** Read-modify-write helper for the store's content.json `site.*` keys. */
function cj_update_site(string $cjFile, callable $fn): string
{
    if (!file_exists($cjFile)) return 'Your website has not been built yet — use "Regenerate website files" below.';
    $cj = json_decode((string)file_get_contents($cjFile), true);
    if (!is_array($cj)) return 'Your website data could not be read — use "Regenerate website files" below.';
    if (!isset($cj['site']) || !is_array($cj['site'])) $cj['site'] = [];
    $fn($cj['site']);
    file_put_contents($cjFile, json_encode($cj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'regen-key') {
        $key = new_api_key();
        $pdo->prepare('UPDATE stores SET api_key = ? WHERE id = ?')->execute([$key, $id]);
        // Keep the generated site's platform.json in sync.
        $metaFile = RP_STORES_DIR . '/' . $store['slug'] . '/platform.json';
        if (file_exists($metaFile)) {
            $meta = json_decode((string)file_get_contents($metaFile), true) ?: [];
            $meta['apiKey'] = $key;
            file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        }
        flash('New key generated. The old key has stopped working — your website was updated automatically.');
    } elseif ($action === 'update') {
        $name = trim((string)($_POST['name'] ?? ''));
        $whatsapp = trim((string)($_POST['whatsapp'] ?? ''));
        $city = trim((string)($_POST['city'] ?? ''));
        if ($name === '') {
            flash("Please enter your restaurant's name.", false);
        } else {
            $pdo->prepare('UPDATE stores SET name = ?, whatsapp = ?, city = ? WHERE id = ?')
                ->execute([$name, $whatsapp, $city, $id]);
            flash('Settings saved.');
        }
    } elseif ($action === 'checkout-mode') {
        $mode = (string)($_POST['checkout_mode'] ?? 'whatsapp');
        if (!in_array($mode, $CHECKOUT_MODES, true)) {
            flash('That checkout option is not available.', false);
        } else {
            $errMsg = cj_update_site($cjFile, function (&$site) use ($mode) { $site['checkoutMode'] = $mode; });
            if ($errMsg !== '') flash($errMsg, false);
            else {
                $modeWords = ['whatsapp' => 'WhatsApp', 'web' => 'website checkout', 'both' => 'WhatsApp and website checkout'][$mode] ?? $mode;
                flash('Saved. Your website now takes orders by ' . $modeWords . '.');
            }
        }
    } elseif ($action === 'ordering-prefs') {
        $min = max(0, (int)($_POST['min_order'] ?? 0));
        $fee = max(0, (int)($_POST['delivery_fee'] ?? 0));
        $pay = [
            'cod'       => isset($_POST['pay_cod']),
            'easypaisa' => isset($_POST['pay_easypaisa']),
            'bank'      => isset($_POST['pay_bank']),
        ];
        if (!$pay['cod'] && !$pay['easypaisa'] && !$pay['bank']) {
            flash('Please turn on at least one payment method.', false);
        } else {
            $errMsg = cj_update_site($cjFile, function (&$site) use ($min, $fee, $pay) {
                $site['minOrderValue'] = $min;
                $site['deliveryFee'] = $fee;
                $site['payments'] = $pay;
            });
            if ($errMsg !== '') flash($errMsg, false);
            else flash('Saved. Your website now uses these payment and delivery settings.');
        }
    } elseif ($action === 'regenerate') {
        try {
            generateStoreFiles($store);
            flash('Website rebuilt from the "' . (RP_TEMPLATES[$store['template']]['name'] ?? $store['template']) . '" template.');
        } catch (Throwable $ex) {
            flash('Regeneration failed: ' . $ex->getMessage(), false);
        }
    } elseif ($action === 'save-domain') {
        $domain = strtolower(trim((string)($_POST['custom_domain'] ?? '')));
        // Clean up: remove protocol, path, trailing slash
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#/.*$#', '', $domain);
        $domain = trim($domain, '.');
        // Validate: letters, numbers, dots, hyphens only
        if ($domain !== '' && !preg_match('/^[a-z0-9][a-z0-9.-]*[a-z0-9]$/', $domain)) {
            flash('Invalid domain format. Use e.g. www.yourrestaurant.com', false);
        } else {
            // Check no other store uses this domain
            if ($domain !== '') {
                $chk = $pdo->prepare("SELECT id FROM stores WHERE LOWER(custom_domain) = ? AND id != ?");
                $chk->execute([$domain, $id]);
                if ($chk->fetch()) {
                    flash('This domain is already connected to another restaurant.', false);
                    header('Location: store-settings.php?id=' . $id);
                    exit;
                }
            }
            $pdo->prepare("UPDATE stores SET custom_domain = ? WHERE id = ?")->execute([$domain, $id]);
            $store['custom_domain'] = $domain;
            flash($domain ? "Custom domain saved: $domain 🌐" : 'Custom domain removed.');
        }
    }
    header('Location: store-settings.php?id=' . $id);
    exit;
}

$folderExists = is_dir(RP_STORES_DIR . '/' . $store['slug']);
$orderCount = (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE store_id = ' . $id)->fetchColumn();

// Current storefront-linked prefs live in the store's own content.json.
$checkoutMode = 'whatsapp';
$minOrder = 0;
$deliveryFee = 0;
$payments = ['cod' => true, 'easypaisa' => false, 'bank' => false];
if (file_exists($cjFile)) {
    $cjNow = json_decode((string)file_get_contents($cjFile), true);
    $site = (is_array($cjNow) && isset($cjNow['site']) && is_array($cjNow['site'])) ? $cjNow['site'] : [];
    $m = $site['checkoutMode'] ?? 'whatsapp';
    if (in_array($m, $CHECKOUT_MODES, true)) $checkoutMode = $m;
    $minOrder = max(0, (int)($site['minOrderValue'] ?? 0));
    $deliveryFee = max(0, (int)($site['deliveryFee'] ?? 0));
    if (isset($site['payments']) && is_array($site['payments'])) {
        foreach (['cod', 'easypaisa', 'bank'] as $k) $payments[$k] = !empty($site['payments'][$k]);
    }
}
$storeUrl = rp_store_url($store);

$chanMeta = [
    'whatsapp' => ['t' => 'WhatsApp Only', 'd' => 'Customers browse your menu online; the cart is sent to your WhatsApp as a ready-to-read order message.', 'best' => 'Best for: quick setup, no website payments'],
    'web'      => ['t' => 'Website Orders', 'd' => 'Customers place the order on your website itself (Cash on Delivery) — it lands straight in your dashboard.', 'best' => 'Best for: a full on-site checkout'],
    'both'     => ['t' => 'Both (Recommended)', 'd' => 'Offer full website checkout alongside direct WhatsApp ordering — customers choose what suits them.', 'best' => 'Best for: maximizing checkout conversion', 'flag' => 'Most Popular in Pakistan'],
];

page_head($store['name'] . ' — Settings');
rp_app_open($user, 'settings', [
    'title' => $store['name'] . ' — Settings',
    'sub'   => '<a href="dashboard.php">← My Restaurants</a> · <a href="orders.php?store=' . $id . '">Orders for this store</a>',
    'crumb' => 'My Restaurants / ' . $store['name'],
    'crumb_html' => '<a href="dashboard.php">My Restaurants</a> / <b>' . e($store['name']) . '</b>',
]);
?>
  <div class="rp-card">
    <h2>General Information</h2>
    <p class="card-sub">Keep your restaurant's details and customer-facing contact up to date.</p>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="update">
      <label class="rp-label" for="sName">Restaurant name</label>
      <input class="rp-input" id="sName" type="text" name="name" value="<?= e($store['name']) ?>" required>
      <div class="rp-row">
        <div>
          <label class="rp-label" for="sWa">WhatsApp order number</label>
          <input class="rp-input" id="sWa" type="tel" name="whatsapp" value="<?= e($store['whatsapp']) ?>" placeholder="03xx xxxxxxx">
          <p class="rp-hint">Used for incoming customer WhatsApp orders and order alerts.</p>
        </div>
        <div>
          <label class="rp-label" for="sCity">City</label>
          <input class="rp-input" id="sCity" type="text" name="city" value="<?= e($store['city']) ?>" placeholder="e.g. Lahore">
        </div>
      </div>
      <div style="margin-top:1rem"><button class="rp-btn rp-btn-red" type="submit">Save Changes</button></div>
    </form>
    <dl class="rp-kv">
      <dt>Website slug</dt><dd><code><?= e($store['slug']) ?></code></dd>
      <dt>Template</dt><dd><?= e(RP_TEMPLATES[$store['template']]['label'] ?? $store['template']) ?></dd>
      <dt>Orders received</dt><dd><?= $orderCount ?></dd>
      <dt>Created</dt><dd><?= e(pk_time($store['created_at'], 'j M Y')) ?></dd>
    </dl>
  </div>

  <div class="rp-card">
    <h2>Your website</h2>
    <p class="card-sub">Your restaurant's address on the web.</p>
    <label class="rp-label">Website address</label>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center">
      <input class="rp-input" type="text" readonly value="<?= e($storeUrl) ?>" onclick="this.select()" style="flex:1;min-width:220px">
      <?= rp_copy_btn($storeUrl, 'Copy Link') ?>
      <?php if ($folderExists): ?><a class="rp-btn rp-btn-line rp-btn-sm" href="<?= e($storeUrl) ?>" target="_blank">Open Website ↗</a><?php endif; ?>
    </div>
    <div style="margin-top:1.2rem;border:1px solid var(--rp-line);border-radius:var(--r-md);padding:1.2rem;background:#fafafa">
      <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.6rem">
        <b style="font-size:1.05rem">🌐 Connect Your Own Domain</b>
        <?php
        $cdomain = trim((string)($store['custom_domain'] ?? ''));
        $dnsOk = false;
        $dnsChecked = false;
        if ($cdomain !== '') {
            $dnsChecked = true;
            // Check if domain points to this server
            $serverIp = $_SERVER['SERVER_ADDR'] ?? '';
            $domainIp = @gethostbyname($cdomain);
            $dnsOk = ($domainIp !== $cdomain && $domainIp === $serverIp);
            // Also accept CNAME to platform domain
            if (!$dnsOk) {
                $records = @dns_get_record($cdomain, DNS_CNAME);
                if ($records) {
                    foreach ($records as $r) {
                        if (stripos($r['target'] ?? '', PLATFORM_DOMAIN) !== false) { $dnsOk = true; break; }
                    }
                }
            }
        }
        ?>
        <?php if ($cdomain === ''): ?>
          <span class="rp-pill" style="background:#eee;color:#666">Not connected</span>
        <?php elseif ($dnsOk): ?>
          <span class="rp-pill" style="background:#e8f5e9;color:#2e7d32">✅ Live</span>
        <?php else: ?>
          <span class="rp-pill" style="background:#fff3e0;color:#e65100">⏳ DNS pending</span>
        <?php endif; ?>
      </div>

      <?php if ($cdomain === '' || !$dnsOk): ?>
      <!-- STEP 1: Enter domain -->
      <div style="display:flex;gap:.8rem;margin-bottom:1rem">
        <div style="flex-shrink:0;width:28px;height:28px;border-radius:50%;background:var(--rp-red);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700">1</div>
        <div style="flex:1">
          <b>Enter your domain</b>
          <p class="rp-hint" style="margin:.2rem 0 .6rem">Buy a domain from GoDaddy, Namecheap, or any registrar (e.g. yourrestaurant.com)</p>
          <form method="post" style="display:flex;gap:.6rem;flex-wrap:wrap">
            <?= csrf_field() ?><input type="hidden" name="action" value="save-domain">
            <input class="rp-input" type="text" name="custom_domain" value="<?= e($cdomain) ?>"
                   placeholder="e.g. www.yourrestaurant.com" style="flex:1;min-width:200px">
            <button class="rp-btn rp-btn-red" type="submit"><?= $cdomain ? 'Update' : 'Continue →' ?></button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($cdomain !== '' && !$dnsOk): ?>
      <!-- STEP 2: Add DNS records -->
      <div style="display:flex;gap:.8rem;margin-bottom:1rem">
        <div style="flex-shrink:0;width:28px;height:28px;border-radius:50%;background:var(--rp-red);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700">2</div>
        <div style="flex:1">
          <b>Point your domain to Assanio</b>
          <p class="rp-hint" style="margin:.2rem 0 .6rem">Go to your domain registrar's DNS settings and add <b>ONE</b> of these:</p>
          <div style="background:#fff;border:1px solid var(--rp-line);border-radius:8px;padding:.8rem;margin-bottom:.6rem">
            <div style="font-size:.8rem;font-weight:700;color:#666;margin-bottom:.4rem">OPTION A — A Record (recommended)</div>
            <div style="display:grid;grid-template-columns:80px 1fr 1fr;gap:.4rem;font-size:.85rem;align-items:center">
              <span style="color:#888">Type</span><span style="color:#888">Name</span><span style="color:#888">Value</span>
              <code>A</code><code>@</code>
              <span><code><?= e($_SERVER['SERVER_ADDR'] ?? 'server IP') ?></code> <?= rp_copy_btn($_SERVER['SERVER_ADDR'] ?? '', 'Copy') ?></span>
            </div>
          </div>
          <div style="background:#fff;border:1px solid var(--rp-line);border-radius:8px;padding:.8rem">
            <div style="font-size:.8rem;font-weight:700;color:#666;margin-bottom:.4rem">OPTION B — CNAME Record</div>
            <div style="display:grid;grid-template-columns:80px 1fr 1fr;gap:.4rem;font-size:.85rem;align-items:center">
              <span style="color:#888">Type</span><span style="color:#888">Name</span><span style="color:#888">Value</span>
              <code>CNAME</code><code>www</code>
              <span><code><?= e(PLATFORM_DOMAIN) ?></code> <?= rp_copy_btn(PLATFORM_DOMAIN, 'Copy') ?></span>
            </div>
          </div>
          <p class="rp-hint" style="margin:.6rem 0">⏱️ DNS changes take 5 minutes to 24 hours. Come back and click Verify below.</p>
        </div>
      </div>

      <!-- STEP 3: Verify -->
      <div style="display:flex;gap:.8rem">
        <div style="flex-shrink:0;width:28px;height:28px;border-radius:50%;background:#ccc;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700">3</div>
        <div style="flex:1">
          <b>Verify connection</b>
          <p class="rp-hint" style="margin:.2rem 0 .6rem">Once DNS is set, click to check:</p>
          <div style="display:flex;gap:.6rem;flex-wrap:wrap">
            <a class="rp-btn rp-btn-dark" href="?id=<?= $id ?>&verify_dns=1">🔍 Verify Now</a>
            <form method="post" class="rp-inline" onsubmit="return confirm('Remove custom domain?')">
              <?= csrf_field() ?><input type="hidden" name="action" value="save-domain">
              <input type="hidden" name="custom_domain" value="">
              <button class="rp-btn rp-btn-line" type="submit">Remove</button>
            </form>
          </div>
          <?php if (isset($_GET['verify_dns'])): ?>
            <p style="margin:.6rem 0 0;padding:.6rem;background:#ffebee;border-radius:6px;font-size:.9rem">
              ❌ <b>Not yet pointing to Assanio.</b> DNS may still be propagating. Wait a bit and try again.
            </p>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($cdomain !== '' && $dnsOk): ?>
      <!-- CONNECTED! -->
      <div style="text-align:center;padding:1rem">
        <div style="font-size:2.5rem;margin-bottom:.5rem">🎉</div>
        <b style="font-size:1.1rem">Your domain is live!</b>
        <p class="rp-hint" style="margin:.4rem 0 1rem">Customers can now visit your restaurant at:</p>
        <a href="https://<?= e($cdomain) ?>" target="_blank" style="font-size:1.2rem;font-weight:700;color:var(--rp-red)">https://<?= e($cdomain) ?> ↗</a>
        <div style="margin-top:1rem">
          <form method="post" class="rp-inline" onsubmit="return confirm('Remove custom domain? Your Assanio subdomain will keep working.')">
            <?= csrf_field() ?><input type="hidden" name="action" value="save-domain">
            <input type="hidden" name="custom_domain" value="">
            <button class="rp-btn rp-btn-line rp-btn-sm" type="submit">Disconnect domain</button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <p style="font-size:.8rem;color:#888;margin:.8rem 0 0">💡 Need help? We can set up your domain for you — just ask on WhatsApp!</p>
    </div>
  </div>

  <div class="rp-card">
    <h2>How customers order</h2>
    <p class="card-sub">Choose how customers place orders through your website. Changes go live immediately.</p>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="checkout-mode">
      <div class="chan-grid">
        <?php foreach ($chanMeta as $val => $cm): ?>
          <label class="chan-card <?= $checkoutMode === $val ? 'sel' : '' ?>">
            <input type="radio" name="checkout_mode" value="<?= e($val) ?>" <?= $checkoutMode === $val ? 'checked' : '' ?>>
            <?php if (!empty($cm['flag'])): ?><span class="chan-flag"><?= e($cm['flag']) ?></span><?php endif; ?>
            <div class="ch-top"><span class="ch-ic"><?php
              if ($val === 'whatsapp') {
                echo '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>';
              } elseif ($val === 'web') {
                echo '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>';
              } else {
                echo '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><path d="M14 3h7v7M21 3l-8 8M3 14v7h7M3 21l8-8"/></svg>';
              }
            ?></span><span class="radio"></span></div>
            <h3><?= e($cm['t']) ?></h3>
            <p><?= e($cm['d']) ?></p>
            <div class="best"><?= e($cm['best']) ?></div>
          </label>
        <?php endforeach; ?>
      </div>
      <button class="rp-btn rp-btn-red" type="submit">Save</button>
    </form>
    <script>
    // Visual feedback for checkout-mode cards (radio is visually hidden).
    document.querySelectorAll('.chan-grid').forEach(function(grid){
      grid.addEventListener('change', function(e){
        if (e.target.name !== 'checkout_mode') return;
        grid.querySelectorAll('.chan-card').forEach(function(card){
          card.classList.toggle('sel', card.contains(e.target));
        });
      });
    });
    </script>
  </div>

  <div class="rp-card">
    <h2>Payments &amp; delivery</h2>
    <p class="card-sub">Choose which payment options your checkout shows, and set your delivery rules. Changes go live immediately.</p>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="ordering-prefs">
      <div class="rp-payrow">
        <div><b>Accept Cash on Delivery (COD)</b><p>Cash on delivery — the standard for restaurant delivery across Pakistan.</p></div>
        <label class="rp-switch"><input type="checkbox" name="pay_cod" <?= $payments['cod'] ? 'checked' : '' ?>><span class="trk"></span></label>
      </div>
      <div class="rp-payrow">
        <div><b>JazzCash / Easypaisa</b><p>Show your mobile-wallet number at checkout for direct transfers.</p></div>
        <label class="rp-switch"><input type="checkbox" name="pay_easypaisa" <?= $payments['easypaisa'] ? 'checked' : '' ?>><span class="trk"></span></label>
      </div>
      <div class="rp-payrow">
        <div><b>Direct Bank Transfer</b><p>Show your bank account / IBAN at checkout for direct transfers.</p></div>
        <label class="rp-switch"><input type="checkbox" name="pay_bank" <?= $payments['bank'] ? 'checked' : '' ?>><span class="trk"></span></label>
      </div>
      <div class="rp-row" style="margin-top:1rem">
        <div>
          <label class="rp-label" for="pMin">Minimum order value (Rs)</label>
          <input class="rp-input" id="pMin" type="number" name="min_order" min="0" step="1" value="<?= (int)$minOrder ?>" placeholder="0 = no minimum">
          <p class="rp-hint">Orders below this amount cannot proceed to checkout. 0 = off.</p>
        </div>
        <div>
          <label class="rp-label" for="pFee">Flat delivery charge (Rs)</label>
          <input class="rp-input" id="pFee" type="number" name="delivery_fee" min="0" step="1" value="<?= (int)$deliveryFee ?>" placeholder="0 = free delivery">
          <p class="rp-hint">Added automatically to every delivery basket. 0 = off.</p>
        </div>
      </div>
      <div style="margin-top:1rem"><button class="rp-btn rp-btn-red" type="submit">Save</button></div>
    </form>
  </div>

  <div class="rp-card">
    <h2>Website connection</h2>
    <p class="card-sub">Your website uses this key to send orders to your dashboard. Keep it private — never share it.</p>
    <div class="rp-keybox"><span><?= e($store['api_key']) ?></span><?= rp_copy_btn($store['api_key'], 'Copy Key') ?></div>
    <form method="post" style="margin-top:.9rem" onsubmit="return confirm('Regenerate the API key? The old key stops working immediately.')">
      <?= csrf_field() ?><input type="hidden" name="action" value="regen-key">
      <button class="rp-btn rp-btn-line" type="submit">Regenerate Key</button>
    </form>
    <p class="rp-hint" style="margin-top:.6rem">The old key stops working the moment you regenerate — your website updates automatically.</p>
  </div>

  <div class="rp-card">
    <h2>Website Files</h2>
    <p class="card-sub">Rebuilds your website from the original template with your name, WhatsApp number and city. Changes you made in the website editor (menu, photos, design) will be lost — only use this to fix a broken site.</p>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center">
      <?php if ($folderExists): ?><a class="rp-btn rp-btn-red rp-btn-sm" href="edit-website.php?id=<?= $id ?>" target="_blank" style="display:inline-flex;align-items:center;gap:.35rem"><?= rp_icon('pencil', 14) ?> Edit website</a><?php endif; ?>
      <form method="post" class="rp-inline" onsubmit="return confirm('Regenerate website files from the template? Store-admin edits will be overwritten.')">
        <?= csrf_field() ?><input type="hidden" name="action" value="regenerate">
        <button class="rp-btn rp-btn-line rp-btn-sm" type="submit">Regenerate website files</button>
      </form>
    </div>
    <?php if ($folderExists): ?><p class="rp-hint" style="margin-top:.6rem">One click signs you straight into your website editor — no separate password. The editor also has its own password login; <b>change the default password before you go live</b>.</p><?php endif; ?>
  </div>
<?= rp_copy_script() ?>
<?php rp_app_close(); page_foot(); ?>
