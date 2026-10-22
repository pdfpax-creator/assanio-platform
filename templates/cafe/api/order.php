<?php
// Public order intake: receives cart checkout, appends to orders.json.
// Orders carry a 'source': 'website' (web checkout, Cash on Delivery) or
// 'whatsapp' (checkout handed off to WhatsApp). Payloads without a source
// default to 'whatsapp' — every pre-web-checkout order arrived that way.
// No login required (like a contact form). The restaurant is notified via
// the WhatsApp message the customer's browser opens; this file keeps a
// server-side copy visible in the admin panel's Orders tab.
require __DIR__ . '/../admin/config.php';
header('Content-Type: application/json');

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

$raw = file_get_contents('php://input');
$d = json_decode($raw, true);
if (!is_array($d)) fail('Invalid JSON.');

$cust = isset($d['customer']) && is_array($d['customer']) ? $d['customer'] : [];
$items = $d['items'] ?? [];
$total = $d['total'] ?? null;

$name  = trim((string)($cust['name'] ?? ''));
$phone = trim((string)($cust['phone'] ?? ''));
$addr  = trim((string)($cust['address'] ?? ''));
$notes = trim((string)($cust['notes'] ?? ''));
$subtotal = isset($d['subtotal']) && is_numeric($d['subtotal']) ? max(0, (float)$d['subtotal']) : null;
$discount = isset($d['discount']) && is_numeric($d['discount']) ? max(0, (float)$d['discount']) : 0;
$coupon   = substr(trim((string)($d['coupon'] ?? '')), 0, 24);
$fee = isset($d['deliveryFee']) && is_numeric($d['deliveryFee']) ? max(0, (float)$d['deliveryFee']) : 0;
$pm = [];
if (isset($d['paymentMethods']) && is_array($d['paymentMethods'])) {
    foreach ($d['paymentMethods'] as $m) { $m = substr(trim((string)$m), 0, 40); if ($m !== '') $pm[] = $m; }
    $pm = array_values(array_unique($pm));
    if (count($pm) > 5) $pm = array_slice($pm, 0, 5);
}
$source   = isset($d['source']) && $d['source'] === 'website' ? 'website' : 'whatsapp';

if ($name === '' || $phone === '' || $addr === '') fail('Name, phone and address are required.');
if (!is_array($items) || count($items) === 0) fail('Cart is empty.');
if (count($items) > 50) fail('Too many items.');
if (!is_numeric($total) || $total < 0) fail('Invalid total.');

$clean = [];
foreach ($items as $it) {
    if (!is_array($it)) fail('Invalid item.');
    $nm = trim(substr((string)($it['name'] ?? ''), 0, 120));
    $q  = (int)($it['qty'] ?? 0);
    $p  = (float)($it['price'] ?? 0);
    if ($nm === '' || $q < 1 || $q > 99 || $p < 0 || $p > 1000000) fail('Invalid item.');
    $clean[] = ['name' => $nm, 'qty' => $q, 'price' => $p];
}

$orders = [];
if (file_exists(CH_ORDERS_FILE)) {
    $old = json_decode(@file_get_contents(CH_ORDERS_FILE), true);
    if (is_array($old)) $orders = $old;
}

$order = [
    'id' => 'ORD-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
    'ts' => gmdate('c'),
    'status' => 'new',
    'customer' => [
        'name'    => substr($name, 0, 80),
        'phone'   => substr($phone, 0, 30),
        'address' => substr($addr, 0, 300),
        'notes'   => substr($notes, 0, 300),
    ],
    'items' => $clean,
    'total' => (float)$total,
    'subtotal' => $subtotal,
    'discount' => $discount,
    'coupon' => $coupon,
    'deliveryFee' => $fee,
    'paymentMethods' => $pm,
    'source' => $source,
];
$orders[] = $order;

$tmp = CH_ORDERS_FILE . '.tmp';
if (file_put_contents($tmp, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false) {
    fail('Could not store order.', 500);
}
if (!rename($tmp, CH_ORDERS_FILE)) fail('Could not store order.', 500);

echo json_encode(['ok' => true, 'id' => $order['id']]);
