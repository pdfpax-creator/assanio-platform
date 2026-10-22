<?php
/**
 * Custom Domain Handler for Assanio
 *
 * When a restaurant connects their own domain (e.g. br9cafe.pk),
 * this file serves their website directly.
 *
 * Setup (done once per domain by Mujtaba in Hostinger):
 * 1. hPanel → Domains → Addon Domain → enter customer's domain
 *    → Document Root: public_html/app (same as Assanio)
 * 2. Customer's DNS: A record → server IP (or CNAME → app.pdfpax.com)
 * 3. In Assanio: store-settings → Custom Domain → enter domain → Save
 * 4. Hostinger Auto-SSL will cover the new domain within hours
 */

function assanio_handle_custom_domain(string $platformDomain = 'app.pdfpax.com'): void
{
    $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));

    // Skip platform's own domain
    $platformHosts = [$platformDomain, 'www.' . $platformDomain];
    if ($host === '' || in_array($host, $platformHosts, true)) {
        return;
    }

    try {
        $pdo = db();
    } catch (Throwable $e) {
        return;
    }

    // Find store by custom domain (try with and without www)
    $hosts = [$host];
    $hosts[] = str_starts_with($host, 'www.') ? substr($host, 4) : 'www.' . $host;

    $store = null;
    foreach ($hosts as $h) {
        $st = $pdo->prepare("SELECT * FROM stores WHERE LOWER(custom_domain) = ? AND status = 'active' LIMIT 1");
        $st->execute([$h]);
        $store = $st->fetch(PDO::FETCH_ASSOC);
        if ($store) break;
    }

    if (!$store) return; // Not a custom domain, show normal landing

    $slug = $store['slug'];
    $storeDir = __DIR__ . '/stores/' . $slug;
    if (!is_dir($storeDir)) return;

    // Map request path to store directory
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $uri = ltrim($uri, '/');

    // Default to index.html
    if ($uri === '' || $uri === 'index.php' || substr($uri, -1) === '/') {
        $uri = rtrim($uri, '/');
        $file = $uri === '' ? 'index.html' : $uri . '/index.html';
    } else {
        $file = $uri;
    }

    // Security: prevent directory traversal
    $file = str_replace(['..', "\0"], '', $file);
    $fullPath = $storeDir . '/' . $file;

    // If it's a PHP file, execute it
    if (str_ends_with($file, '.php') && file_exists($fullPath)) {
        // Make store context available
        $GLOBALS['assanio_custom_store'] = $store;
        include $fullPath;
        exit;
    }

    // If it's a static file, serve it
    if (file_exists($fullPath) && is_file($fullPath)) {
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimes = [
            'html' => 'text/html', 'css' => 'text/css', 'js' => 'application/javascript',
            'json' => 'application/json', 'png' => 'image/png', 'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp',
            'svg' => 'image/svg+xml', 'ico' => 'image/x-icon', 'woff' => 'font/woff',
            'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'mp4' => 'video/mp4',
        ];
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($fullPath));
        // For HTML files, we need to fix relative asset paths
        if ($ext === 'html' || $ext === 'htm') {
            $content = file_get_contents($fullPath);
            // Inject base tag so relative URLs resolve correctly
            $content = preg_replace(
                '/<head([^>]*)>/i',
                '<head$1><base href="/stores/' . htmlspecialchars($slug, ENT_QUOTES) . '/">',
                $content,
                1
            );
            echo $content;
        } else {
            readfile($fullPath);
        }
        exit;
    }

    // File not found in store — show store's 404 or generic
    http_response_code(404);
    echo '<h1>Page not found</h1><p>The page you requested does not exist on ' . htmlspecialchars($store['name']) . '.</p>';
    exit;
}
