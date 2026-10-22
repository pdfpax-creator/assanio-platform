<?php
// One-login editing: mint a one-time SSO token and hand the owner's browser
// to the store's own Admin 2.0 — no separate store password needed.
require_once __DIR__ . '/lib.php';
$user = require_login();
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$isSuper = ($user['role'] ?? 'owner') === 'super';

if ($isSuper) {
    // Super admin can edit ANY store
    $st = $pdo->prepare('SELECT * FROM stores WHERE id = ?');
    $st->execute([$id]);
} else {
    $st = $pdo->prepare('SELECT * FROM stores WHERE id = ? AND owner_id = ?');
    $st->execute([$id, (int)$user['id']]);
}
$store = $st->fetch();
if (!$store) rp_error_page(404, 'Restaurant not found', 'This restaurant does not exist or belongs to a different account.');

if (!file_exists(RP_STORES_DIR . '/' . $store['slug'] . '/admin/platform_sso.php')) {
    flash('This website was built before one-click editing. Regenerate the website files in Settings first.', false);
    header('Location: store-settings.php?id=' . $id);
    exit;
}

$raw = create_sso_token((int)$user['id'], (int)$store['id']);
header('Location: stores/' . $store['slug'] . '/admin/platform_sso.php?t=' . urlencode($raw));
exit;
