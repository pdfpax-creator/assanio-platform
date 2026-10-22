<?php
// Assanio — shared UI fragments (Stitch "Modern Hospitality SaaS" reskin).
// Binding: red rounded-square white "R" + "Assanio" Inter 800 logo, one mark everywhere.
require_once __DIR__ . '/config.php';

/** Logo: red rounded square, white "A", Inter 800 wordmark. $pk adds the "Pakistan" pill (public/auth pages). */
function rp_logo(bool $pk = false, bool $small = false): string
{
    $cls = $small ? 'rp-mark sm' : 'rp-mark';
    $h = '<a class="rp-logo" href="' . 'index.php' . '" aria-label="' . e(PLATFORM_NAME) . ' home">'
        . '<span class="' . $cls . '">A</span>'
        . '<span class="rp-word">' . e(PLATFORM_NAME) . '</span>';
    if ($pk) $h .= '<span class="rp-pk">Pakistan</span>';
    return $h . '</a>';
}

function rp_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $in = '';
    foreach ((array)$parts as $p) { if ($p !== '') { $in .= strtoupper(substr($p, 0, 1)); if (strlen($in) >= 2) break; } }
    return $in !== '' ? $in : '?';
}

/** Time-of-day greeting for the dashboard. */
function rp_greet(): string
{
    $h = (int)date('G', time() + 5*3600); // Asia/Karachi (UTC+5)
    if ($h < 12) return 'Good morning';
    if ($h < 17) return 'Good afternoon';
    return 'Good evening';
}

/** Inline SVG icon set (stroke=currentColor). Zero emoji in the UI. */
function rp_icon(string $name, int $size = 18): string
{
    $s = $size;
    $p = [
        'globe'   => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.9 5.7 3.9 9S14.5 18.4 12 21c-2.5-2.6-3.9-5.7-3.9-9S9.5 5.6 12 3z"/>',
        'pencil'  => '<path d="M17 3a2.8 2.8 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/>',
        'chat'    => '<path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5z"/>',
        'chart'   => '<path d="M3 3v18h18"/><path d="M7 15v3M12 10v8M17 6v12"/>',
        'receipt' => '<path d="M5 3h14v18l-2.3-1.5L14.4 21l-2.4-1.5L9.6 21l-2.3-1.5L5 21z"/><path d="M9 8h6M9 12h6"/>',
        'shield'  => '<path d="M12 2 4 5.5V11c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V5.5z"/><path d="m9 11.5 2 2 4-4.5"/>',
        'pin'     => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'note'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h4"/>',
        'cal'     => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'gear'    => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.2a1.7 1.7 0 0 0 1 1.5h.1a1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9v.1a1.7 1.7 0 0 0 1.5 1h.2a2 2 0 1 1 0 4h-.2a1.7 1.7 0 0 0-1.5 1z"/>',
        'grid'    => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'logout'  => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'check'   => '<path d="M20 6 9 17l-5-5"/>',
        'lock'    => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'phone'   => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.7a2 2 0 0 1-.5 2.1L8 9.7a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.7 2z"/>',
    ];
    $body = $p[$name] ?? $p['grid'];
    return '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}

/** White-glove setup panel. */
function rp_pilot_panel(): string
{
    return '<div class="rp-quote rp-pilotp"><div class="qlbl">White-glove setup</div>'
        . '<p><b>We set up everything for you.</b> Our team configures your menu, deals and WhatsApp ordering — and the Rs 9,999 setup fee is waived with yearly billing.</p></div>';
}

/** Dynamic date chip, e.g. "Today, 20 Oct 2026". */
function rp_date_chip(): string
{
    return '<span class="rp-datechip">' . rp_icon('cal', 15) . ' Today, ' . e(date('j M Y')) . '</span>';
}

/** Status pill with dot indicator (binding colors). */
function rp_pill(string $status): string
{
    $s = strtolower($status);
    $label = ucfirst($s);
    return '<span class="rp-pill pill-' . e($s) . '"><span class="dot"></span>' . e($label) . '</span>';
}

/** Source chip from the order's `source` field. Missing/unknown → no chip (never fabricate). */
function rp_source_chip($source): string
{
    if ($source === 'website') return '<span class="rp-chip chip-web">● Website Direct</span>';
    if ($source === 'whatsapp') return '<span class="rp-chip chip-wa">● WhatsApp</span>';
    return '';
}

/** Sidebar. $active: dashboard|orders|templates|settings|admin */
function rp_sidebar(array $user, string $active): string
{
    $pdo = db();
    $st = $pdo->prepare('SELECT id FROM stores WHERE owner_id = ? ORDER BY created_at DESC, id DESC LIMIT 1');
    $st->execute([(int)$user['id']]);
    $firstStore = $st->fetchColumn();
    $isSuper = ($user['role'] ?? 'owner') === 'super';
    $isCaller = ($user['role'] ?? 'owner') === 'caller';
    $new = $isCaller ? 0 : new_orders_count((int)$user['id']);

    $item = function (string $key, string $href, string $icon, string $label, string $extra = '') use ($active) {
        return '<a href="' . $href . '" class="' . ($active === $key ? 'on' : '') . '">'
            . '<span class="ic">' . $icon . '</span>' . e($label) . $extra . '</a>';
    };
    $h = '<aside class="rp-side"><div class="rp-side-top">' . rp_logo() . '</div><nav class="rp-nav">';
    if ($isCaller) {
        $h .= $item('crm', 'caller-crm.php', rp_icon('phone', 18), 'My Leads');
    } else {
        $h .= $item('dashboard', 'dashboard.php', rp_icon('grid', 18), 'Dashboard');
        $h .= $item('orders', 'orders.php', rp_icon('receipt', 18), 'Orders', $new ? '<span class="cnt">' . $new . '</span>' : '');
        $h .= $item('customers', 'customers.php', rp_icon('users', 18), 'Customers');
        $h .= $item('templates', 'templates.php', rp_icon('globe', 18), 'Templates');
        if ($firstStore) $h .= $item('settings', 'store-settings.php?id=' . (int)$firstStore, rp_icon('gear', 18), 'Settings');
    }
    if ($isSuper) {
        $h .= '<div class="sec">Admin</div>';
        $h .= $item('admin', 'admin.php', rp_icon('shield', 18), 'Super Admin');
        $h .= $item('referrals', 'admin-referrals.php', rp_icon('users', 18), 'Referrals');
        $h .= $item('leads', 'admin-leads.php', rp_icon('phone', 18), 'Leads');
        $h .= $item('resets', 'admin-resets.php', rp_icon('key', 18), 'Password Resets');
        $h .= $item('crm', 'caller-crm.php', rp_icon('phone', 18), 'Caller CRM');
    }
    $roleLabel = $isSuper ? 'Super Admin' : ($isCaller ? 'Sales Caller' : 'Restaurant Owner');
    $h .= '</nav><div class="rp-side-foot"><div class="rp-avatar">' . e(rp_initials($user['name'] ?? '?')) . '</div>'
        . '<div class="who"><b>' . e($user['name'] ?? '') . '</b><span>' . $roleLabel . '</span></div>'
        . '<form method="post" action="logout.php" class="rp-inline">' . csrf_field()
        . '<button class="rp-logout" type="submit" title="Log out" aria-label="Log out">' . rp_icon('logout', 17) . '</button></form></div></aside>';
    return $h;
}

/**
 * App shell open: sidebar + top header + content wrapper.
 * $opts: title, sub, crumb (default "Assanio / Merchant Workspace"), actions (HTML), hideDate.
 */
function rp_app_open(array $user, string $active, array $opts = []): void
{
    $title = $opts['title'] ?? '';
    $sub = $opts['sub'] ?? '';
    $crumb = $opts['crumb'] ?? null;
    $actions = $opts['actions'] ?? '';
    echo '<div class="rp-app">';
    echo rp_sidebar($user, $active);
    echo '<div class="rp-main"><div class="rp-top">';
    if ($crumb !== false) {
        $crumbHtml = $opts['crumb_html'] ?? ('<b>' . e($crumb ?? 'Merchant Workspace') . '</b>');
        echo '<div class="rp-crumb">' . e(PLATFORM_NAME) . ' &nbsp;/&nbsp; ' . $crumbHtml . '</div>';
    }
    echo '<div class="rp-top-row"><div style="display:flex;gap:.6rem;align-items:center">'
        . '<button class="rp-burger" onclick="document.body.classList.toggle(\'nav-open\')" aria-label="Menu">☰</button>'
        . '<div><h1>' . ($opts['title_html'] ?? e($title)) . (isset($opts['title_badge']) ? ' ' . $opts['title_badge'] : '') . '</h1>' . ($sub !== '' ? '<p class="sub">' . $sub . '</p>' : '') . '</div></div>'
        . '<div class="rp-top-actions">' . (empty($opts['hideDate']) ? rp_date_chip() : '') . $actions . '</div></div>';
    echo '</div><div class="rp-content">';
    flash_html();
}

function rp_app_close(): void
{
    echo '</div>'; // .rp-content
    echo '<footer class="rp-foot">© 2026 ' . e(PLATFORM_NAME) . ' — restaurant websites &amp; online ordering for Pakistan.</footer>';
    echo '</div></div>'; // .rp-main .rp-app
}

/** Public (logged-out) top nav for landing + auth pages. */
function rp_public_nav(): void
{
    echo '<div class="rp-pubnav"><div class="rp-pubnav-in">' . rp_logo(true)
        . '<nav><a href="index.php#templates">Templates</a><a href="index.php#how">How it works</a><a href="index.php#faq">FAQ</a></nav>'
        . '<span class="sp"></span><a href="login.php" style="font-weight:600;font-size:14px">Log in</a>'
        . '<a class="rp-btn rp-btn-red rp-btn-sm" href="register.php">Start Free</a>'
        . '</div></div>';
}

/** Stat card. Keeps the `num` class (selftest2 asserts `num">Rs 1,026<`). */
function rp_stat(string $label, string $num, string $foot = ''): string
{
    return '<div class="rp-stat"><div class="lbl">' . e($label) . '</div><div class="num">' . $num . '</div>'
        . ($foot !== '' ? '<div class="ft">' . $foot . '</div>' : '') . '</div>';
}

/** Empty state block. */
function rp_empty(string $title, string $text, string $cta = '', string $icon = '◫'): string
{
    return '<div class="rp-card"><div class="rp-empty"><div class="e-ic">' . $icon . '</div>'
        . '<h2>' . e($title) . '</h2><p>' . $text . '</p>' . $cta . '</div></div>';
}

/** Money cell helper: tabular numbers. */
function rp_money($n): string
{
    return '<span class="t-num">' . e(money($n)) . '</span>';
}

/** Full website URL of a store folder (for the read-only subdomain row + copy button). */
function rp_store_url(array $store): string
{
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/store-settings.php'), '/');
    return $proto . '://' . $host . $base . '/' . $store['slug'] . '/';
}

/** Copy-to-clipboard button (tiny inline JS). */
function rp_copy_btn(string $text, string $label = 'Copy'): string
{
    return '<button type="button" class="rp-btn rp-btn-line rp-btn-sm" data-copy="' . e($text) . '">⧉ ' . e($label) . '</button>';
}

function rp_copy_script(): string
{
    return '<script>(function(){document.addEventListener("click",function(ev){var b=ev.target.closest("[data-copy]");'
        . 'if(!b)return;var t=b.getAttribute("data-copy");function done(){var o=b.textContent;b.textContent="✓ Copied";'
        . 'setTimeout(function(){b.textContent=o},1400)}'
        . 'if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(t).then(done,done)}'
        . 'else{var ta=document.createElement("textarea");ta.value=t;document.body.appendChild(ta);ta.select();'
        . 'try{document.execCommand("copy")}catch(e){}document.body.removeChild(ta);done()}});})();</script>';
}

/* rp_testimonial() removed 2026-10-20: invented "Tariq Mansoor" quote deleted.
   Honest pilot framing (rp_pilot_panel()) is used instead — never fake social proof. */

/** Past-tense, user-friendly status phrase for flash messages. */
function rp_status_past(string $status): string
{
    return [
        'new'       => 'new',
        'accepted'  => 'accepted',
        'preparing' => 'being prepared',
        'delivered' => 'delivered',
        'cancelled' => 'cancelled',
    ][$status] ?? $status;
}

/** Branded error page (standalone — safe to call before any output). */
function rp_error_page(int $code, string $title, string $msg, string $backUrl = 'dashboard.php', string $backLabel = 'Back to dashboard'): void
{
    http_response_code($code);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>' . e($title) . ' — ' . e(PLATFORM_NAME) . '</title>'
        . '<link rel="preconnect" href="https://fonts.googleapis.com">'
        . '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">'
        . '<style>body{margin:0;background:#F7F7F5;font-family:Inter,system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:1.5rem;color:#141414}'
        . '.e{background:#fff;border:1px solid #E7E5E1;border-radius:16px;padding:2.5rem 2rem;max-width:440px;text-align:center}'
        . '.m{display:inline-flex;align-items:center;gap:.5rem;margin-bottom:1.2rem;text-decoration:none}'
        . '.mk{width:36px;height:36px;border-radius:10px;background:#C8102E;color:#fff;font-weight:800;font-size:20px;display:inline-flex;align-items:center;justify-content:center}'
        . '.w{font-weight:800;font-size:17px;letter-spacing:-.02em;color:#141414}'
        . 'h1{font-size:22px;margin:0 0 .6rem;letter-spacing:-.02em}'
        . 'p{color:#686868;font-size:15px;line-height:1.6;margin:0 0 1.4rem}'
        . 'a.b{display:inline-block;background:#C8102E;color:#fff;font-weight:700;font-size:14.5px;padding:.7rem 1.4rem;border-radius:10px;text-decoration:none}</style>'
        . '</head><body><div class="e"><a class="m" href="index.php"><span class="mk">A</span><span class="w">' . e(PLATFORM_NAME) . '</span></a>'
        . '<h1>' . e($title) . '</h1><p>' . e($msg) . '</p>'
        . '<a class="b" href="' . e($backUrl) . '">' . e($backLabel) . '</a></div></body></html>';
    exit;
}
