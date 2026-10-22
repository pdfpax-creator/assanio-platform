<?php
// Assanio — Central Orders API.
// A store's website POSTs a copy of every checkout here so the owner sees all
// orders in one dashboard. Auth: per-store key in the X-Store-Key header.
// Payload shape matches the templates' own api/order.php exactly:
//   { customer:{name,phone,address,notes}, items:[{name,qty,price}],
//     subtotal, discount, coupon, total }
require __DIR__ . '/../lib.php';
header('Content-Type: application/json');

function api_fail(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_fail('POST required.', 405);

$key = (string)($_SERVER['HTTP_X_STORE_KEY'] ?? '');
if ($key === '') api_fail('Missing X-Store-Key header.', 401);

$pdo = db();
$st = $pdo->prepare('SELECT id FROM stores WHERE api_key = ?');
$st->execute([$key]);
$store = $st->fetch();
if (!$store) api_fail('Invalid store key.', 401);

$d = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($d)) api_fail('Invalid JSON.');

$cust  = isset($d['customer']) && is_array($d['customer']) ? $d['customer'] : [];
$items = $d['items'] ?? [];
$name  = trim((string)($cust['name'] ?? ''));
$phone = trim((string)($cust['phone'] ?? ''));
$addr  = trim((string)($cust['address'] ?? ''));
$notes = trim((string)($cust['notes'] ?? ''));
$subtotal = isset($d['subtotal']) && is_numeric($d['subtotal']) ? max(0, (float)$d['subtotal']) : null;
$discount = isset($d['discount']) && is_numeric($d['discount']) ? max(0, (float)$d['discount']) : 0;
$coupon   = substr(trim((string)($d['coupon'] ?? '')), 0, 24);
$total    = $d['total'] ?? null;
// Order origin — same contract as the store templates' own api/order.php:
// only an exact 'website' counts; everything else (incl. absent) is 'whatsapp'.
$source   = (isset($d['source']) && $d['source'] === 'website') ? 'website' : 'whatsapp';

if ($name === '' || $phone === '' || $addr === '') api_fail('Name, phone and address are required.');
if (!is_array($items) || count($items) === 0) api_fail('Cart is empty.');
if (count($items) > 50) api_fail('Too many items.');
if (!is_numeric($total) || $total < 0) api_fail('Invalid total.');

$clean = [];
foreach ($items as $it) {
    if (!is_array($it)) api_fail('Invalid item.');
    $nm = trim(substr((string)($it['name'] ?? ''), 0, 120));
    $q  = (int)($it['qty'] ?? 0);
    $p  = (float)($it['price'] ?? 0);
    if ($nm === '' || $q < 1 || $q > 99 || $p < 0 || $p > 1000000) api_fail('Invalid item.');
    $clean[] = ['name' => $nm, 'qty' => $q, 'price' => $p];
}

$id = 'ORD-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
$ins = $pdo->prepare('INSERT INTO orders
    (id, store_id, status, customer_name, customer_phone, customer_address, customer_notes,
     items_json, subtotal, discount, coupon, total, source)
    VALUES (?, ?, \'new\', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$ins->execute([
    $id, (int)$store['id'],
    substr($name, 0, 80), substr($phone, 0, 30), substr($addr, 0, 300), substr($notes, 0, 300),
    json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    $subtotal, $discount, $coupon, (float)$total,
    $source,
]);

// Auto-collect customer data for marketing
customer_upsert($pdo, (int)$store['id'], $name, $phone, $addr, (float)$total);

echo json_encode(['ok' => true, 'id' => $id]);
