<?php
// Assanio core library: DB, auth, CSRF, helpers, store generation.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

/* ---------------- database ---------------- */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    if (!is_dir(RP_DATA_DIR)) mkdir(RP_DATA_DIR, 0775, true);
    // Protect the database from web access (self-healing on every deploy).
    $ht = RP_DATA_DIR . '/.htaccess';
    if (!file_exists($ht)) @file_put_contents($ht, "# Protect the platform database on Apache/Hostinger.\nRequire all denied\n");
    $pdo = new PDO('sqlite:' . RP_DB_FILE, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS stores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        owner_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        template TEXT NOT NULL,
        whatsapp TEXT NOT NULL DEFAULT '',
        city TEXT NOT NULL DEFAULT '',
        api_key TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id TEXT PRIMARY KEY,
        store_id INTEGER NOT NULL REFERENCES stores(id) ON DELETE CASCADE,
        status TEXT NOT NULL DEFAULT 'new',
        customer_name TEXT NOT NULL DEFAULT '',
        customer_phone TEXT NOT NULL DEFAULT '',
        customer_address TEXT NOT NULL DEFAULT '',
        customer_notes TEXT NOT NULL DEFAULT '',
        items_json TEXT NOT NULL DEFAULT '[]',
        subtotal REAL,
        discount REAL NOT NULL DEFAULT 0,
        coupon TEXT NOT NULL DEFAULT '',
        total REAL NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_orders_store ON orders(store_id, created_at)");

    /* ---- MLM referral system tables ---- */
    $pdo->exec("CREATE TABLE IF NOT EXISTS referrals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        caller_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        client_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        plan TEXT NOT NULL DEFAULT 'starter',
        status TEXT NOT NULL DEFAULT 'active',
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        UNIQUE(caller_id, client_id)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS commissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        referral_id INTEGER NOT NULL REFERENCES referrals(id) ON DELETE CASCADE,
        month TEXT NOT NULL,
        amount INTEGER NOT NULL,
        type TEXT NOT NULL DEFAULT 'recurring',
        paid INTEGER NOT NULL DEFAULT 0,
        paid_at TEXT,
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        UNIQUE(referral_id, month, type)
    )");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_referrals_caller ON referrals(caller_id, status)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_commissions_month ON commissions(month, paid)");

    /* ---- Platform settings (key-value for editable pages) ---- */
    $pdo->exec("CREATE TABLE IF NOT EXISTS platform_settings (
        key TEXT PRIMARY KEY,
        value TEXT NOT NULL DEFAULT ''
    )");
    // Default Discover page settings
    $defaults = [
        'discover_title' => '🍽️ Discover Restaurants',
        'discover_subtitle' => 'Order directly from the restaurant — no middleman, no extra charges.',
    ];
    foreach ($defaults as $k => $v) {
        $st = $pdo->prepare("INSERT OR IGNORE INTO platform_settings (key, value) VALUES (?, ?)");
        $st->execute([$k, $v]);
    }

    /* ---- Customer database (auto-collected from orders) ---- */
    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        store_id INTEGER NOT NULL REFERENCES stores(id) ON DELETE CASCADE,
        name TEXT NOT NULL DEFAULT '',
        phone TEXT NOT NULL,
        address TEXT NOT NULL DEFAULT '',
        total_orders INTEGER NOT NULL DEFAULT 0,
        total_spent REAL NOT NULL DEFAULT 0,
        first_order TEXT NOT NULL DEFAULT (datetime('now')),
        last_order TEXT NOT NULL DEFAULT (datetime('now')),
        UNIQUE(store_id, phone)
    )");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_customers_store ON customers(store_id, total_spent DESC)");

    /* ---- phase-2 migrations (idempotent) ---- */
    $userCols = $pdo->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('role', $userCols, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'owner'"); // 'owner' | 'super' | 'caller'
    }
    if (!in_array('approved', $userCols, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN approved INTEGER NOT NULL DEFAULT 0"); // 0=pending, 1=approved
    }
    if (!in_array('referral_code', $userCols, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN referral_code TEXT DEFAULT ''"); // caller's unique code
    }
    $storeCols = $pdo->query('PRAGMA table_info(stores)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('plan', $storeCols, true)) {
        $pdo->exec("ALTER TABLE stores ADD COLUMN plan TEXT NOT NULL DEFAULT 'starter'"); // starter|business|pro
    }
    if (!in_array('payment_status', $storeCols, true)) {
        $pdo->exec("ALTER TABLE stores ADD COLUMN payment_status TEXT NOT NULL DEFAULT 'unpaid'"); // paid|unpaid
    }
    if (!in_array('renewal_date', $storeCols, true)) {
        $pdo->exec("ALTER TABLE stores ADD COLUMN renewal_date TEXT NOT NULL DEFAULT ''"); // YYYY-MM-DD
    }
    if (!in_array('status', $storeCols, true)) {
        $pdo->exec("ALTER TABLE stores ADD COLUMN status TEXT NOT NULL DEFAULT 'active'"); // active|suspended
    }
    if (!in_array('show_in_discover', $storeCols, true)) {
        $pdo->exec("ALTER TABLE stores ADD COLUMN show_in_discover INTEGER NOT NULL DEFAULT 1"); // 1=show, 0=hide
    }
    if (!in_array('custom_domain', $storeCols, true)) {
        $pdo->exec("ALTER TABLE stores ADD COLUMN custom_domain TEXT NOT NULL DEFAULT ''"); // e.g. br9cafe.pk
    }
    $orderCols = $pdo->query('PRAGMA table_info(orders)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('source', $orderCols, true)) {
        // 'website' = ordered directly on the site (COD web checkout);
        // 'whatsapp' = WhatsApp checkout. Legacy rows pre-date the column —
        // default them to 'whatsapp', the only checkout mode that existed.
        $pdo->exec("ALTER TABLE orders ADD COLUMN source TEXT NOT NULL DEFAULT 'whatsapp'");
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS sso_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        token_hash TEXT NOT NULL UNIQUE,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        store_id INTEGER NOT NULL REFERENCES stores(id) ON DELETE CASCADE,
        expires_at INTEGER NOT NULL,
        used_at INTEGER,
        created_at INTEGER NOT NULL
    )");

    /* ---- Caller CRM: cold-calling leads ---- */
    $pdo->exec("CREATE TABLE IF NOT EXISTS leads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        restaurant_name TEXT NOT NULL DEFAULT '',
        contact_name TEXT NOT NULL DEFAULT '',
        phone TEXT NOT NULL,
        city TEXT NOT NULL DEFAULT '',
        assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
        status TEXT NOT NULL DEFAULT 'new',
        remarks TEXT NOT NULL DEFAULT '',
        last_called_at TEXT,
        call_count INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_leads_caller ON leads(assigned_to, status)");
    return $pdo;
}

define('RP_PLANS', ['starter', 'business', 'pro']);
define('RP_SSO_TTL', 120); // seconds — one-time Edit Website sign-in links

function gen_log(string $msg): void
{
    if (!is_dir(RP_DATA_DIR)) mkdir(RP_DATA_DIR, 0775, true);
    @file_put_contents(RP_DATA_DIR . '/generation.log', '[' . gmdate('c') . '] ' . $msg . "\n", FILE_APPEND);
}

/* ---------------- sessions & auth ---------------- */

function rp_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'path'     => '/',
    ]);
    session_start();
}

function current_user(): ?array
{
    rp_session_start();
    $id = $_SESSION['user_id'] ?? null;
    if (!$id) return null;
    $st = db()->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
    $st->execute([(int)$id]);
    $u = $st->fetch();
    return $u ?: null;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        // Remember where they were going (relative URL only) so login can send them back.
        $next = (string)($_SERVER['REQUEST_URI'] ?? '');
        if ($next !== '' && $next[0] === '/' && !str_starts_with($next, '//')) {
            header('Location: login.php?next=' . urlencode($next));
        } else {
            header('Location: login.php');
        }
        exit;
    }
    return $u;
}

/** Safe post-login destination: relative path on this site only. */
function safe_next(string $raw): string
{
    if ($raw !== '' && $raw[0] === '/' && !str_starts_with($raw, '//')) return $raw;
    return 'dashboard.php';
}

function require_super(): array
{
    $u = require_login();
    if (($u['role'] ?? 'owner') !== 'super') {
        rp_error_page(403, 'Not allowed', 'This area is for Assanio administrators only.');
    }
    return $u;
}

/* ---------------- Edit Website SSO ----------------
 * The platform mints a one-time token (only its SHA-256 hash is stored),
 * then redirects the owner's browser to the store's admin/platform_sso.php
 * with the raw token. That generated file atomically consumes the token
 * (single-use, ≤ RP_SSO_TTL seconds old, bound to that store) and starts
 * the store-admin session — the owner never types the store password. */

function create_sso_token(int $userId, int $storeId): string
{
    $pdo = db();
    $pdo->prepare('DELETE FROM sso_tokens WHERE expires_at < ?')->execute([time()]);
    $raw = bin2hex(random_bytes(32));
    $pdo->prepare('INSERT INTO sso_tokens (token_hash, user_id, store_id, expires_at, created_at)
                   VALUES (?, ?, ?, ?, ?)')
        ->execute([hash('sha256', $raw), $userId, $storeId, time() + RP_SSO_TTL, time()]);
    return $raw;
}

/* ---------------- store suspension ---------------- */

function store_status_file(array $store): string
{
    return RP_STORES_DIR . '/' . $store['slug'] . '/platform_status.json';
}

function set_store_suspended(array $store, bool $suspended): void
{
    db()->prepare('UPDATE stores SET status = ? WHERE id = ?')
        ->execute([$suspended ? 'suspended' : 'active', (int)$store['id']]);
    $dir = RP_STORES_DIR . '/' . $store['slug'];
    if (!is_dir($dir)) return;
    $f = $dir . '/platform_status.json';
    if ($suspended) {
        file_put_contents($f, json_encode(['status' => 'suspended']) . "\n");
    } elseif (file_exists($f)) {
        unlink($f);
    }
}

function login_user(int $userId): void
{
    rp_session_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void
{
    rp_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool)($p['secure'] ?? false), (bool)($p['httponly'] ?? true));
    }
    session_destroy();
}

/* ---------------- small helpers ---------------- */

function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    rp_session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    rp_session_start();
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        rp_error_page(403, 'Session expired', 'Your form session expired for security. Please go back and try again.');
    }
}

function flash(string $msg, bool $ok = true): void
{
    rp_session_start();
    $_SESSION['flash'] = ['msg' => $msg, 'ok' => $ok];
}

function take_flash(): ?array
{
    rp_session_start();
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function slugify(string $name): string
{
    $s = strtolower(trim($name));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    $s = trim($s, '-');
    return $s === '' ? 'restaurant' : substr($s, 0, 48);
}

/* ---------------- platform settings ---------------- */

function setting_get(string $key, string $default = ''): string
{
    $pdo = db();
    $st = $pdo->prepare("SELECT value FROM platform_settings WHERE key = ?");
    $st->execute([$key]);
    $row = $st->fetch();
    return $row ? (string)$row['value'] : $default;
}

function setting_set(string $key, string $value): void
{
    $pdo = db();
    $st = $pdo->prepare("INSERT INTO platform_settings (key, value) VALUES (?, ?)
                         ON CONFLICT(key) DO UPDATE SET value = excluded.value");
    $st->execute([$key, $value]);
}

function unique_slug(PDO $pdo, string $base): string
{
    // Reserved words that would collide with platform routes or folders.
    static $reserved = ['api', 'assets', 'admin', 'stores', 'data', 'tools',
        'login', 'logout', 'register', 'dashboard', 'orders', 'order',
        'templates', 'create-store', 'store-settings', 'edit-website', 'index'];
    $slug = $base;
    $n = 2;
    $st = $pdo->prepare('SELECT 1 FROM stores WHERE slug = ?');
    while (true) {
        $taken = in_array($slug, $reserved, true);
        if (!$taken) {
            $st->execute([$slug]);
            $taken = (bool)$st->fetch();
        }
        if (!$taken) return $slug;
        $slug = $base . '-' . $n++;
    }
}

// Normalize a Pakistani phone to intl digits (923xxxxxxxxx) + display form.
function normalize_phone(string $raw): array
{
    $d = preg_replace('/\D+/', '', $raw) ?? '';
    if (str_starts_with($d, '0092')) $d = substr($d, 2);
    if (str_starts_with($d, '0')) $d = '92' . substr($d, 1);
    if (strlen($d) === 10 && str_starts_with($d, '3')) $d = '92' . $d;
    $display = $d;
    if (strlen($d) === 12 && str_starts_with($d, '92')) {
        $display = '0' . substr($d, 2, 3) . ' ' . substr($d, 5);
    }
    return ['digits' => $d, 'display' => $display];
}

function new_api_key(): string
{
    return bin2hex(random_bytes(24)); // 48 hex chars
}

function money($n): string
{
    return 'Rs ' . number_format((float)$n, 0);
}

/**
 * Format a UTC datetime string (as stored by SQLite datetime('now')) for the
 * Pakistan user: Asia/Karachi. Falls back to the raw string on bad input.
 */
function pk_time(string $utc, string $fmt = 'j M, g:i A'): string
{
    try {
        $dt = new DateTime($utc, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('Asia/Karachi'));
        return $dt->format($fmt);
    } catch (Throwable $e) {
        return $utc;
    }
}

function new_orders_count(int $ownerId): int
{
    $st = db()->prepare("SELECT COUNT(*) FROM orders o JOIN stores s ON s.id = o.store_id
                         WHERE s.owner_id = ? AND o.status = 'new'");
    $st->execute([$ownerId]);
    return (int)$st->fetchColumn();
}

/* ---------------- layout ---------------- */

function page_head(string $title, string $desc = ''): void
{
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">';
    echo '<meta name="mobile-web-app-capable" content="yes">';
    echo '<meta name="apple-mobile-web-app-capable" content="yes">';
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">';
    echo '<title>' . e($title) . ' — ' . e(PLATFORM_NAME) . '</title>';
    if ($desc !== '') {
        echo '<meta name="description" content="' . e($desc) . '">';
        echo '<meta property="og:title" content="' . e($title) . ' — ' . e(PLATFORM_NAME) . '">';
        echo '<meta property="og:description" content="' . e($desc) . '">';
        echo '<meta property="og:type" content="website">';
    }
    echo '<meta name="theme-color" content="#C8102E">';
    echo '<link rel="icon" href="data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="#C8102E"/><text x="32" y="44" font-family="Arial,sans-serif" font-size="36" font-weight="800" fill="white" text-anchor="middle">A</text></svg>') . '">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="assets/style.css"></head><body>';
}

function flash_html(): void
{
    $f = take_flash();
    if ($f) echo '<div class="msg ' . ($f['ok'] ? 'ok' : 'err') . '">' . e($f['msg']) . '</div>';
}

function page_foot(): void
{
    echo '</body></html>';
}

/** Small centered footer for auth pages (© 2026 binding). */
function rp_auth_mini_foot(): void
{
    echo '<div class="rp-auth-foot"><span>© 2026 ' . e(PLATFORM_NAME) . '</span>'
        . '<span>' . rp_icon('lock', 14) . ' Bank-grade SSL</span><span>Built for Pakistan</span></div>';
}

/* ---------------- store file generation ---------------- */

function rcopy(string $src, string $dst): void
{
    if (!is_dir($dst)) mkdir($dst, 0775, true);
    foreach (scandir($src) as $entry) {
        if ($entry === '.' || $entry === '..' || $entry === '.git') continue;
        $s = $src . '/' . $entry;
        $d = $dst . '/' . $entry;
        if (is_dir($s)) rcopy($s, $d);
        else copy($s, $d);
    }
}

function rrmdir(string $dir): void
{
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $p = $dir . '/' . $entry;
        is_dir($p) ? rrmdir($p) : unlink($p);
    }
    rmdir($dir);
}

// Relative path from one absolute dir to an absolute file (for generated files
// that must find the platform DB from inside a store folder).
function rel_path(string $fromDir, string $toFile): string
{
    $from = explode('/', trim($fromDir, '/'));
    $to   = explode('/', trim($toFile, '/'));
    $i = 0;
    while (isset($from[$i], $to[$i]) && $from[$i] === $to[$i]) $i++;
    return implode('/', array_merge(array_fill(0, count($from) - $i, '..'), array_slice($to, $i)));
}

// Source of the store-side SSO endpoint dropped into <store>/admin/ at
// generation time. Self-contained: it opens the platform SQLite DB directly
// (relative path baked in), atomically consumes the one-time token, and —
// when hit directly by the owner's browser — starts the store admin session.
// The store's patched admin/save.php also accepts the token via this file's
// rp_sso_consume() for its "login" action.
function rp_platform_sso_source(array $store): string
{
    $adminDir = RP_STORES_DIR . '/' . $store['slug'] . '/admin';
    $dbRel    = rel_path($adminDir, RP_DB_FILE);
    $tpl = <<<'PHP'
<?php
// Assanio SSO endpoint — GENERATED at store creation; do not edit by hand.
// Validates one-time "Edit Website" sign-in tokens issued by the platform.
// Marker: Assanio SSO
declare(strict_types=1);

function rp_sso_consume(string $raw): bool
{
    if ($raw === '') return false;
    $dbFile = __DIR__ . '/' . __RP_DB_REL__;
    if (!file_exists($dbFile)) return false;
    try {
        $pdo = new PDO('sqlite:' . $dbFile, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('PRAGMA busy_timeout = 3000');
        // Atomic single-use consume: succeeds only for an unused, unexpired
        // token issued for THIS store (slug baked in at generation time).
        $up = $pdo->prepare('UPDATE sso_tokens SET used_at = :now
                             WHERE token_hash = :h AND used_at IS NULL AND expires_at >= :now
                               AND store_id = (SELECT id FROM stores WHERE slug = :slug)');
        $up->execute([':now' => time(), ':h' => hash('sha256', $raw), ':slug' => __RP_SLUG__]);
        return $up->rowCount() === 1;
    } catch (Throwable $e) {
        return false;
    }
}

// Direct browser entry: platform redirects here with ?t=<token>.
if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    $token = (string)($_GET['t'] ?? '');
    if (!rp_sso_consume($token)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'This sign-in link is invalid, expired or already used. Go back to ' . __RP_PLATFORM_NAME__ . ' and click "Edit Website" again.';
        exit;
    }
    require __DIR__ . '/config.php'; // CH_SESSION_KEY (store admin config)
    session_start();
    $_SESSION[CH_SESSION_KEY] = true;
    header('Location: index.html');
    exit;
}
PHP;
    return strtr($tpl, [
        // JSON_UNESCAPED_SLASHES matters: "\/" is NOT a PHP string escape, so a
        // slash-escaped path baked into the generated file would be broken.
        '__RP_DB_REL__'        => json_encode($dbRel, JSON_UNESCAPED_SLASHES),
        '__RP_SLUG__'          => json_encode($store['slug'], JSON_UNESCAPED_SLASHES),
        '__RP_PLATFORM_NAME__' => json_encode(PLATFORM_NAME, JSON_UNESCAPED_SLASHES),
    ]);
}

// Storefront suspension snippet injected before </body> at generation time.
// If platform_status.json says "suspended", the page is replaced with a
// polite unavailable screen. Missing file / any error → store stays live.
function rp_suspension_snippet(): string
{
    $name = json_encode(PLATFORM_NAME);
    // Re-skinned 2026-10-20 to the platform design tokens (Inter, red R mark).
    // Logic unchanged: fetch platform_status.json (no-store); suspended → swap body.
    $html = '<div id="rp-suspended" style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#F7F7F5;font-family:Inter,system-ui,-apple-system,sans-serif;padding:1.5rem">'
        . '<div style="background:#fff;border:1px solid #E7E5E1;border-radius:16px;padding:2.5rem 2rem;max-width:440px;text-align:center;box-shadow:0 1px 2px rgba(20,20,20,.04)">'
        . '<div style="display:inline-flex;align-items:center;gap:.55rem;margin-bottom:1rem">'
        . '<span style="width:34px;height:34px;border-radius:10px;background:#C8102E;color:#fff;font-weight:800;font-size:19px;display:inline-flex;align-items:center;justify-content:center">R</span>'
        . '<span style="font-weight:800;font-size:16px;color:#141414;letter-spacing:-.02em">' . PLATFORM_NAME . '</span></div>'
        . '<h1 style="font-size:24px;margin:0 0 .6rem;color:#141414;letter-spacing:-.02em">Temporarily unavailable</h1>'
        . '<p style="margin:0;color:#686868;font-size:15px;line-height:1.6">This restaurant is temporarily unavailable — please check back soon.</p>'
        . '</div></div>';
    $jsHtml = json_encode($html);
    return <<<HTML
<!-- Assanio suspension check (patched at store generation; do not edit by hand) -->
<script>
(function(){
  var SCREEN = $jsHtml;
  function show(){
    if (!document.body || document.getElementById('rp-suspended')) return;
    document.title = 'Temporarily unavailable';
    document.body.innerHTML = SCREEN;
  }
  fetch('platform_status.json', {cache:'no-store'}).then(function(r){ return r.ok ? r.json() : null; }).then(function(s){
    if (!s || s.status !== 'suspended') return;
    show();
    var n = 0, iv = setInterval(function(){ show(); if (++n >= 12) clearInterval(iv); }, 500);
    var mo = new MutationObserver(show);
    if (document.documentElement) mo.observe(document.documentElement, {childList:true, subtree:true});
    setTimeout(function(){ mo.disconnect(); clearInterval(iv); show(); }, 8000);
  }).catch(function(){});
})();
</script>
HTML;
}

/**
 * Generate a store's website folder from its template.
 * Returns the store folder path. Throws RuntimeException on failure.
 */
function generateStoreFiles(array $store): string
{
    $template = $store['template'];
    if (!isset(RP_TEMPLATES[$template])) throw new RuntimeException('Unknown template.');
    $src = RP_TEMPLATES_DIR . '/' . $template;
    if (!is_dir($src)) throw new RuntimeException('Template folder missing: ' . $template);

    // Phase-2 patches are mandatory: fail LOUDLY (log + exception) if a
    // template ever loses a patch anchor, rather than silently shipping a
    // store without one-login editing / suspension support.
    $ssoAnchor = "if (\$action === 'login') {";
    $tplSave = (string)@file_get_contents($src . '/admin/save.php');
    if (!str_contains($tplSave, $ssoAnchor)) {
        $msg = "SSO patch anchor missing in template '$template' (admin/save.php login action). Store '{$store['slug']}' NOT generated.";
        gen_log($msg);
        throw new RuntimeException($msg);
    }
    $tplIndex = (string)@file_get_contents($src . '/index.html');
    if (!str_contains($tplIndex, '</body>')) {
        $msg = "Suspension patch anchor missing in template '$template' (index.html </body>). Store '{$store['slug']}' NOT generated.";
        gen_log($msg);
        throw new RuntimeException($msg);
    }

    $dst = RP_STORES_DIR . '/' . $store['slug'];
    if (is_dir($dst)) rrmdir($dst);
    rcopy($src, $dst);

    // Fresh order inbox for the new store (template ships demo-free, be safe).
    file_put_contents($dst . '/api/orders.json', "[]\n");
    // Customer data must never be web-readable: block *.json in the store's
    // api folder while keeping order.php reachable.
    file_put_contents($dst . '/api/.htaccess',
        "# Customer order data is private; the checkout endpoint stays public.\n<Files \"*.json\">\nRequire all denied\n</Files>\n");

    // Seed content.json with the restaurant's own details.
    $cjPath = $dst . '/content.json';
    $cj = json_decode((string)file_get_contents($cjPath), true);
    if (!is_array($cj)) throw new RuntimeException('Template content.json is unreadable.');
    if (!isset($cj['site']) || !is_array($cj['site'])) $cj['site'] = [];
    $phone = normalize_phone((string)($store['whatsapp'] ?? ''));
    $cj['site']['name'] = $store['name'];
    // Clear template-default header logo text so it falls back to the store name
    if (isset($cj['header']['logoText'])) {
        unset($cj['header']['logoText']);
    }
    if ($phone['digits'] !== '') {
        $cj['site']['whatsappDigits'] = $phone['digits'];
        $cj['site']['phoneDigits'] = $phone['digits'];
        $cj['site']['phone'] = $phone['display'];
    }
    if (!empty($store['city'])) {
        $cj['site']['coverage'] = $store['city'] . ' & surroundings';
        $cj['site']['mapQuery'] = $store['name'] . ' ' . $store['city'];
    }
    $cj['site']['_demoNote'] = 'Generated by ' . PLATFORM_NAME . ' for "' . $store['name'] . '" on ' . gmdate('Y-m-d') . '. Numbers below are the owner\'s own.';
    file_put_contents($cjPath, json_encode($cj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

    // Platform link file: how this store talks back to the central orders API.
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base  = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/create-store.php'), '/');
    $meta = [
        'platform'   => PLATFORM_NAME,
        'storeSlug'  => $store['slug'],
        'apiUrl'     => $proto . '://' . $host . $base . '/api/order.php',
        'apiKey'     => $store['api_key'],
        'generated'  => gmdate('c'),
    ];
    file_put_contents($dst . '/platform.json', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

    // Patch the store's own api/order.php: after the local save, forward a copy
    // to the platform (best-effort — never blocks the customer's checkout).
    $apiFile = $dst . '/api/order.php';
    $apiSrc  = (string)file_get_contents($apiFile);
    $anchor  = "echo json_encode(['ok' => true, 'id' => \$order['id']]);";
    if (str_contains($apiSrc, $anchor)) {
        $forward = <<<'PHP'
// --- Assanio: forward a copy to the central orders dashboard (best-effort) ---
(function () use ($order) {
    $metaFile = __DIR__ . '/../platform.json';
    if (!file_exists($metaFile) || !function_exists('curl_init')) return;
    $meta = json_decode((string)@file_get_contents($metaFile), true);
    if (!is_array($meta) || empty($meta['apiUrl']) || empty($meta['apiKey'])) return;
    $payload = json_encode([
        'customer' => $order['customer'], 'items' => $order['items'],
        'subtotal' => $order['subtotal'], 'discount' => $order['discount'],
        'coupon' => $order['coupon'], 'total' => $order['total'],
    ]);
    $ch = curl_init($meta['apiUrl']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Store-Key: ' . $meta['apiKey']],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3,
    ]);
    @curl_exec($ch);
    @curl_close($ch);
})();

PHP;
        file_put_contents($apiFile, str_replace($anchor, $forward . $anchor, $apiSrc));
    }

    // --- Phase 2: one-login editing (SSO) ---
    // 1) Drop the store-side token validator into admin/.
    file_put_contents($dst . '/admin/platform_sso.php', rp_platform_sso_source($store));
    // 2) Patch the copied admin/save.php so its "login" action also accepts a
    //    platform SSO token (idempotent via the marker comment).
    $saveFile = $dst . '/admin/save.php';
    $saveSrc  = (string)file_get_contents($saveFile);
    if (!str_contains($saveSrc, 'Assanio SSO')) {
        $ssoBlock = <<<'PHP'
    // --- Assanio SSO (patched at store generation; do not edit by hand) ---
    if (!empty($_POST['sso_token']) && file_exists(__DIR__ . '/platform_sso.php')) {
        require_once __DIR__ . '/platform_sso.php';
        if (rp_sso_consume((string)$_POST['sso_token'])) {
            $_SESSION[CH_SESSION_KEY] = true;
            echo json_encode(['ok' => true, 'sso' => true]);
        } else {
            fail('Invalid or expired platform sign-in link.', 401);
        }
        exit;
    }
PHP;
        if (!str_contains($saveSrc, $ssoAnchor)) { // anchor verified pre-copy; this is belt-and-braces
            $msg = "SSO patch failed on generated copy for store '{$store['slug']}'.";
            gen_log($msg);
            throw new RuntimeException($msg);
        }
        file_put_contents($saveFile, str_replace($ssoAnchor, $ssoAnchor . "\n" . $ssoBlock, $saveSrc));
    }

    // --- Phase 2: storefront suspension support ---
    $indexFile = $dst . '/index.html';
    $indexSrc  = (string)file_get_contents($indexFile);
    if (!str_contains($indexSrc, 'Assanio suspension check')) {
        $pos = strpos($indexSrc, '</body>');
        if ($pos === false) {
            $msg = "Suspension patch failed on generated copy for store '{$store['slug']}'.";
            gen_log($msg);
            throw new RuntimeException($msg);
        }
        file_put_contents($indexFile, substr_replace($indexSrc, rp_suspension_snippet() . "\n", $pos, 0));
    }
    // Honour a suspension that was already in force (e.g. regeneration).
    if (($store['status'] ?? 'active') === 'suspended') {
        file_put_contents($dst . '/platform_status.json', json_encode(['status' => 'suspended']) . "\n");
    }
    return $dst;
}

/* ==================== MLM REFERRAL SYSTEM ==================== */

/**
 * Commission rates per plan.
 * Returns [closing_bonus, monthly_recurring]
 */
function mlm_rates(string $plan): array {
    return match($plan) {
        'business' => [1500, 300],
        'pro'      => [2000, 500],
        default    => [1000, 200], // starter
    };
}

/**
 * Create a referral link between caller and client.
 * Called when admin assigns a client to a caller, or via referral code.
 */
function mlm_create_referral(PDO $pdo, int $callerId, int $clientId, string $plan): int {
    // Verify caller has caller role
    $st = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $st->execute([$callerId]);
    if ($st->fetchColumn() !== 'caller') {
        throw new RuntimeException('User is not a caller');
    }
    // Prevent duplicate
    $st = $pdo->prepare("SELECT id FROM referrals WHERE caller_id = ? AND client_id = ?");
    $st->execute([$callerId, $clientId]);
    if ($st->fetchColumn()) {
        throw new RuntimeException('Referral already exists');
    }
    $st = $pdo->prepare("INSERT INTO referrals (caller_id, client_id, plan) VALUES (?, ?, ?)");
    $st->execute([$callerId, $clientId, $plan]);
    $referralId = (int)$pdo->lastInsertId();

    // NOTE: No auto-commission. Owner verifies manually on WhatsApp.
    // Commissions are handled outside the system.

    return $referralId;
}

/**
 * Calculate monthly recurring commissions for all active referrals.
 * Run on the 1st of each month (cron or admin trigger).
 * Returns number of commissions created.
 */
function mlm_calculate_monthly(PDO $pdo, ?string $month = null): int {
    $month = $month ?? date('Y-m');
    // Validate month format (YYYY-MM)
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        throw new RuntimeException('Invalid month format');
    }
    $count = 0;

    $st = $pdo->prepare("
        SELECT r.id, r.plan FROM referrals r
        WHERE r.status = 'active'
        AND NOT EXISTS (
            SELECT 1 FROM commissions c
            WHERE c.referral_id = r.id AND c.month = ? AND c.type = 'recurring'
        )
    ");
    $st->execute([$month]);

    foreach ($st as $row) {
        [, $recurring] = mlm_rates($row['plan']);
        if ($recurring > 0) {
            $pdo->prepare("INSERT INTO commissions (referral_id, month, amount, type)
                           VALUES (?, ?, ?, 'recurring')")
                ->execute([$row['id'], $month, $recurring]);
            $count++;
        }
    }
    return $count;
}

/**
 * Get caller's commission summary.
 */
function mlm_caller_stats(PDO $pdo, int $callerId): array {
    $st = $pdo->prepare("
        SELECT COUNT(*) as clients,
               SUM(CASE WHEN c.paid = 0 THEN c.amount ELSE 0 END) as pending,
               SUM(CASE WHEN c.paid = 1 THEN c.amount ELSE 0 END) as paid
        FROM referrals r
        LEFT JOIN commissions c ON c.referral_id = r.id
        WHERE r.caller_id = ? AND r.status = 'active'
    ");
    $st->execute([$callerId]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: ['clients' => 0, 'pending' => 0, 'paid' => 0];
}

/**
 * Mark commissions as paid (admin only).
 */
function mlm_mark_paid(PDO $pdo, array $commissionIds): int {
    if (empty($commissionIds)) return 0;
    $placeholders = implode(',', array_fill(0, count($commissionIds), '?'));
    $st = $pdo->prepare("UPDATE commissions SET paid = 1, paid_at = datetime('now')
                         WHERE id IN ($placeholders) AND paid = 0");
    $st->execute($commissionIds);
    return $st->rowCount();
}

/* ==================== CUSTOMER DATABASE ==================== */

/**
 * Update or create customer record when an order is placed.
 * Called automatically from order API.
 */
function customer_upsert(PDO $pdo, int $storeId, string $name, string $phone, string $address, float $total): void {
    $phone = trim($phone);
    if ($phone === '') return;

    $st = $pdo->prepare("SELECT id, total_orders, total_spent FROM customers WHERE store_id = ? AND phone = ?");
    $st->execute([$storeId, $phone]);
    $existing = $st->fetch();

    if ($existing) {
        $pdo->prepare("UPDATE customers SET
            name = CASE WHEN ? != '' THEN ? ELSE name END,
            address = CASE WHEN ? != '' THEN ? ELSE address END,
            total_orders = total_orders + 1,
            total_spent = total_spent + ?,
            last_order = datetime('now')
            WHERE id = ?")
            ->execute([$name, $name, $address, $address, $total, $existing['id']]);
    } else {
        $pdo->prepare("INSERT INTO customers (store_id, name, phone, address, total_orders, total_spent)
                       VALUES (?, ?, ?, ?, 1, ?)")
            ->execute([$storeId, $name, $phone, $address, $total]);
    }
}

/**
 * Get customers for a store (for dashboard/export).
 */
function get_store_customers(PDO $pdo, int $storeId, int $limit = 500): array {
    $st = $pdo->prepare("SELECT * FROM customers WHERE store_id = ? ORDER BY total_spent DESC LIMIT ?");
    $st->execute([$storeId, $limit]);
    return $st->fetchAll();
}
